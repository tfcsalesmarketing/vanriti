<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Providers\DadiServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => AdminAuthenticate::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'active' => EnsureUserIsActive::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);

        $middleware->validateCsrfTokens(except: ['checkout/verify']);

        $middleware->appendToGroup('web', [
            SecurityHeaders::class,
        ]);

        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $trustedHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))));
        if ($trustedHosts !== []) {
            $middleware->trustHosts(at: $trustedHosts, subdomains: true);
        } else {
            $middleware->trustHosts(subdomains: true);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withProviders([
        DadiServiceProvider::class,
    ])->create();
