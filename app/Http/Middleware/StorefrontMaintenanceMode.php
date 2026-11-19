<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StorefrontMaintenanceMode
{
    /**
     * Cookie name used for preview bypass.
     */
    public const BYPASS_COOKIE = 'vr_maint_bypass';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If storefront maintenance mode is NOT active, proceed normally
        if (! $this->isDownForMaintenance()) {
            return $next($request);
        }

        // 2. Allow unrestricted access to Admin Panel routes
        if ($this->isAdminRoute($request)) {
            return $next($request);
        }

        // 3. Allow essential webhooks, health checks, and static asset routes
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        // 4. Handle Preview Bypass toggle via query string
        if ($request->query('preview') === 'vanriti') {
            $targetUrl = $this->urlWithoutPreviewParam($request);
            return redirect($targetUrl)
                ->withCookie(cookie()->make(self::BYPASS_COOKIE, '1', 60 * 24)); // 24 hours
        }

        if ($request->query('preview') === 'exit') {
            $targetUrl = $this->urlWithoutPreviewParam($request);
            return redirect($targetUrl)
                ->withCookie(cookie()->forget(self::BYPASS_COOKIE));
        }

        // 5. Allow bypass if authenticated as an Admin or if valid bypass cookie exists
        if ($this->hasBypassAccess($request)) {
            $response = $next($request);
            return $response;
        }

        // 6. Return the custom Storefront Maintenance page (503 Service Unavailable)
        return response()
            ->view('storefront.maintenance', [
                'maintenanceData' => $this->getMaintenanceData(),
            ], 503)
            ->header('Retry-After', '300')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
    }

    /**
     * Build clean URL without the 'preview' query parameter.
     */
    protected function urlWithoutPreviewParam(Request $request): string
    {
        $url = $request->url();
        $query = $request->except('preview');

        return ! empty($query) ? $url . '?' . http_build_query($query) : $url;
    }

    /**
     * Determine if storefront maintenance mode is currently active.
     */
    public function isDownForMaintenance(): bool
    {
        // Check file flag first (instant, high performance, persists across requests)
        if (file_exists($this->maintenanceFilePath())) {
            return true;
        }

        // Check environment / config flag
        if (config('app.storefront_maintenance', false)) {
            return true;
        }

        // Check database setting if available
        try {
            if (function_exists('setting') && (bool) setting('storefront_maintenance', false)) {
                return true;
            }
        } catch (\Throwable) {
            // Silently fall through if database is not reachable
        }

        return false;
    }

    /**
     * File path where maintenance status payload is stored.
     */
    public static function maintenanceFilePath(): string
    {
        return storage_path('framework/storefront-maintenance.json');
    }

    /**
     * Check if the request is targeting the admin panel.
     */
    protected function isAdminRoute(Request $request): bool
    {
        return $request->is('admin', 'admin/*');
    }

    /**
     * Check if the request is an exempt service / webhook / asset route.
     */
    protected function isExemptRoute(Request $request): bool
    {
        // Health check endpoint
        if ($request->is('up')) {
            return true;
        }

        // External payment & logistics webhooks must always process orders
        if ($request->is('razorpay/webhook', 'shipmojo/webhook', 'whatsapp/webhook')) {
            return true;
        }

        // TFC marketing domain and preview subpaths
        $tfcDomain = config('tfc.domain', 'tfcsalesmarketing.com');
        if ($request->getHost() === $tfcDomain || $request->getHost() === 'www.' . $tfcDomain) {
            return true;
        }
        if ($request->is('tfc', 'tfc/*')) {
            return true;
        }

        // Static files if served via Laravel router
        if ($request->is('css/*', 'js/*', 'images/*', 'vendor/*', 'build/*', 'favicon.ico', 'site.webmanifest', 'sitemap.xml')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user has bypass privileges (logged-in admin or secret cookie).
     */
    protected function hasBypassAccess(Request $request): bool
    {
        if (Auth::guard('admin')->check()) {
            return true;
        }

        if ($request->cookie(self::BYPASS_COOKIE) === '1') {
            return true;
        }

        return false;
    }

    /**
     * Read maintenance payload from file or defaults.
     *
     * @return array<string, mixed>
     */
    protected function getMaintenanceData(): array
    {
        $path = $this->maintenanceFilePath();
        if (file_exists($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'started_at' => now()->toIso8601String(),
            'message' => "We're currently enhancing our storefront to bring you a refreshed Ayurvedic wellness experience. We'll be back shortly!",
        ];
    }
}
