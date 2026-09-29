<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureDatabaseIsReachable;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackStorefrontPageView;
use App\Providers\DadiServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

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

        $middleware->validateCsrfTokens(except: ['razorpay/webhook', 'shipmojo/webhook']);

        $middleware->appendToGroup('web', [
            SecurityHeaders::class,
            TrackStorefrontPageView::class,
        ]);

        // Runs before EncryptCookies/StartSession so a dead database surfaces as
        // a friendly 503 page instead of a raw 500 thrown from the session store.
        $middleware->prependToGroup('web', [
            EnsureDatabaseIsReachable::class,
        ]);

        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        // Host allow-list: default to the host of the configured app URL so
        // host-header poisoning is rejected out of the box, while still allowing
        // an explicit TRUSTED_HOSTS override.
        $trustedHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))));
        $appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
        if ($appHost !== null && $appHost !== '') {
            $trustedHosts[] = $appHost;
        }
        $trustedHosts = array_values(array_unique($trustedHosts));

        if ($trustedHosts !== []) {
            $middleware->trustHosts(at: $trustedHosts, subdomains: true);
        } else {
            $middleware->trustHosts(subdomains: true);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle rate limit exceeded for forgot password and OTP routes
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('forgot-password', 'forgot-password/*', 'otp/send', 'otp/verify')) {
                return redirect()->route('password.request')
                    ->with('error', 'Limit exceeded for OTP request. You can retry after 30 minutes.');
            }
        });

        // Second safety net for a dead database: the session, cache and queue
        // stores are all database-backed, so a redirect with a flash message is
        // impossible once the server is unreachable. Render the session-free
        // 503 page instead of a raw 500 (and never leak SQL to the customer).
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $e instanceof QueryException) {
                return;
            }

            if ($request->is('admin', 'admin/*')) {
                return;
            }

            if (! $request->is('forgot-password', 'forgot-password/*', 'otp/send', 'otp/verify', 'password/*')) {
                return;
            }

            Log::error('database.query_failed', [
                'path' => $request->path(),
                'sqlstate' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            return response()->view('errors.503', [], 503);
        });

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
