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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        $middleware->validateCsrfTokens(except: ['checkout/verify', 'razorpay/webhook', 'shipmojo/webhook']);

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
        // Admin panel gets its own set of error pages (resources/views/admin/errors).
        // Any other request (storefront) falls through to Laravel's default error
        // views (resources/views/errors), which stay untouched.
        $exceptions->render(function (Throwable $e, Request $request) {
            // Validation exceptions must be handled by Laravel's default renderer
            // (which redirects back with the error bag), not the admin error-page
            // renderer below. Otherwise admin form validation failures produce a
            // blank 500 instead of inline field errors.
            if ($e instanceof ValidationException) {
                return;
            }
            if (! $request->is('admin', 'admin/*')) {
                return;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $status = in_array($status, [400, 401, 402, 403, 404, 405, 419, 429, 500, 503], true)
                ? $status
                : 500;

            $view = view()->exists("admin.errors.{$status}")
                ? "admin.errors.{$status}"
                : 'admin.errors.generic';

            return response()->view($view, ['exception' => $e, 'status' => $status], $status);
        });
    })
    ->withProviders([
        DadiServiceProvider::class,
    ])->create();
