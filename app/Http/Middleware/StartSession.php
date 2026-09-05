<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StartSession extends BaseStartSession
{
    public function handle($request, Closure $next)
    {
        return parent::handle($request, function ($req) use ($next) {
            if ($req->hasSession()) {
                app()->instance('session.store', $req->session());
            }

            $response = $next($req);
            if (! $response instanceof SymfonyResponse) {
                if ($response instanceof \Illuminate\Contracts\Support\Renderable) {
                    $response = new Response($response->render());
                } else {
                    $response = new Response($response);
                }
            }
            return $response;
        });
    }
}
