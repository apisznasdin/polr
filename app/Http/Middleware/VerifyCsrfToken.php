<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyCsrfToken
{
    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->shouldPassThrough($request) ||
            $this->tokensMatch($request)
        ) {
            return $this->addCookieToResponse($request, $next($request));
        }

        throw new HttpException(419, 'Page Expired');
    }

    protected function shouldPassThrough($request)
    {
        return $request->is('api/v*/action/*') || $request->is('api/v*/data/*');
    }

    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->token()) &&
               is_string($token) &&
               hash_equals($request->session()->token(), $token);
    }

    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            try {
                $token = $this->encrypter->decrypt($header, static::serialized());
            } catch (\Exception $e) {
                $token = '';
            }
        }

        return $token;
    }

    protected static function serialized()
    {
        return false;
    }

    protected function isReading($request)
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    protected function runningUnitTests()
    {
        return app()->runningInConsole() && (app()->environment() === 'testing' || env('APP_ENV') === 'testing');
    }

    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($request->hasSession() && $request->session()->isStarted()) {
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                $response->headers->setCookie(
                    new Cookie(
                        'XSRF-TOKEN',
                        $request->session()->token(),
                        time() + 60 * ($config['lifetime'] ?? 120),
                        $config['path'] ?? '/',
                        $config['domain'] ?? null,
                        $config['secure'] ?? false,
                        false,
                        false,
                        $config['same_site'] ?? null
                    )
                );
            }
        }

        return $response;
    }
}

