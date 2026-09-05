<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/helpers.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->withFacades(true, [
    \Illuminate\Support\Facades\Session::class => 'Session',
    \Illuminate\Support\Facades\Cookie::class => 'Cookie',
    \Illuminate\Support\Facades\Mail::class => 'Mail',
    \Illuminate\Support\Facades\Hash::class => 'Hash',
    \Illuminate\Support\Facades\Artisan::class => 'Artisan',
    \Illuminate\Support\Str::class => 'Str',
    \Illuminate\Support\Arr::class => 'Arr',
]);
$app->withEloquent();

$app->configure('app');
$app->configure('database');
$app->configure('session');
$app->configure('mail');
$app->configure('geoip');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    Illuminate\Cookie\Middleware\EncryptCookies::class,
    App\Http\Middleware\StartSession::class,
    Illuminate\View\Middleware\ShareErrorsFromSession::class,
    App\Http\Middleware\VerifyCsrfToken::class,
]);

$app->routeMiddleware([
    'api' => App\Http\Middleware\ApiMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(\Illuminate\Session\SessionServiceProvider::class);
$app->register(\Illuminate\Cookie\CookieServiceProvider::class);
$app->register(\Illuminate\Mail\MailServiceProvider::class);
$app->register(App\Providers\AppServiceProvider::class);
$app->register(\Yajra\DataTables\DataTablesServiceProvider::class);
$app->register(\Torann\GeoIP\GeoIPServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
    $app = $router;
    require __DIR__.'/../app/Http/routes.php';
});


return $app;

