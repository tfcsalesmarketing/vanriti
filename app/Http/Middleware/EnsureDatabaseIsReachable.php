<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Fails fast with a session-free 503 page when the database is unreachable.
 *
 * The session, cache and queue stores all default to the database, so a dead
 * MySQL server turns every web request into a raw 500 page — the failure often
 * happens inside StartSession or the throttle middleware, before any controller
 * or exception renderer can attach a friendly message. This check is prepended
 * to the web group so it runs before the session is touched, and it returns
 * early, which also keeps session writes and analytics inserts from running
 * against a dead connection.
 */
class EnsureDatabaseIsReachable
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCheck($request) && ! $this->isReachable()) {
            return response()->view('errors.503', [], 503);
        }

        return $next($request);
    }

    protected function shouldCheck(Request $request): bool
    {
        if ($request->is('up')) {
            return false;
        }

        $default = app()->runningUnitTests() ? false : true;

        return filter_var(env('DB_HEALTH_CHECK', $default), FILTER_VALIDATE_BOOLEAN);
    }

    protected function isReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable $e) {
            Log::error('database.unreachable', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
