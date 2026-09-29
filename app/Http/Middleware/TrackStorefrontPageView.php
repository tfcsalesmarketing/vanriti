<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsEventRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a first-party page_view for storefront GET responses only.
 *
 * Admin routes (mounted under the same web group) are excluded, as are the
 * health endpoint, non-GET requests, non-2xx responses and bot user agents.
 * Recording is best-effort and never affects the response.
 */
class TrackStorefrontPageView
{
    public function __construct(
        protected AnalyticsEventRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            $this->recorder->pageView($request);
        }

        return $response;
    }

    protected function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->is('admin', 'admin/*') || $request->is('up')) {
            return false;
        }

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
}
