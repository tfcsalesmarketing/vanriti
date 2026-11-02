<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsConversion;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Reporting and intelligence service for ChatGPT Ads campaign and storefront performance.
 *
 * Integrates internal first-party attribution data with the OpenAI Advertiser Reporting API
 * when credentials are configured. Clearly marks unavailable platform metrics rather than
 * displaying fabricated numbers.
 */
class ChatGptAdsReportingService
{
    /**
     * Build the full ChatGPT Ads performance report for the given inclusive date bounds.
     *
     * @param array{from: string, to: string} $bounds
     */
    public function report(array $bounds): array
    {
        $platform = $this->platformMetrics($bounds);
        $behavior = $this->behaviorMetrics($bounds);
        $orders = $this->orderMetrics($bounds);
        $campaigns = $this->campaignBreakdown($bounds);
        $products = $this->productBreakdown($bounds);
        $series = $this->dailySeries($bounds);

        // Compute blended metrics
        $visits = $behavior['visits'];
        $conversions = $orders['orders_created'];
        $paidRevenue = $orders['paid_revenue'];

        $cvr = $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0;

        $spend = $platform['available'] ? (float) ($platform['metrics']['spend'] ?? 0) : null;
        $impressions = $platform['available'] ? (int) ($platform['metrics']['impressions'] ?? 0) : null;
        $adClicks = $platform['available'] ? (int) ($platform['metrics']['clicks'] ?? 0) : null;

        $ctr = ($impressions && $impressions > 0 && $adClicks !== null)
            ? round(($adClicks / $impressions) * 100, 2)
            : null;

        $cpc = ($spend !== null && $adClicks && $adClicks > 0)
            ? round($spend / $adClicks, 2)
            : null;

        $cpa = ($spend !== null && $conversions > 0)
            ? round($spend / $conversions, 2)
            : null;

        $roas = ($spend !== null && $spend > 0)
            ? round($paidRevenue / $spend, 2)
            : null;

        return [
            'platform' => $platform,
            'summary' => [
                'impressions' => $impressions,
                'ad_clicks' => $adClicks,
                'website_visits' => $visits,
                'ctr' => $ctr,
                'spend' => $spend,
                'cpc' => $cpc,
                'conversions' => $conversions,
                'conversion_rate' => $cvr,
                'cost_per_acquisition' => $cpa,
                'paid_revenue' => $paidRevenue,
                'roas' => $roas,
            ],
            'behavior' => $behavior,
            'orders' => $orders,
            'campaigns' => $campaigns,
            'products' => $products,
            'series' => $series,
        ];
    }

    /**
     * Resolve the configured OpenAI Advertiser API key from encrypted settings or config.
     */
    public function apiKey(): string
    {
        return trim((string) (secret_setting('openai_advertiser_api_key') ?: config('openai_ads.advertiser_api_key', '')));
    }

    /**
     * Resolve the optional OpenAI Advertiser account ID from settings or config.
     */
    public function accountId(): string
    {
        return trim((string) (setting('openai_advertiser_account_id') ?: config('openai_ads.advertiser_account_id', '')));
    }

    /**
     * Normalized base URL for the OpenAI Advertiser API.
     * Guarantees https://api.ads.openai.com/v1 format without duplicating or omitting /v1.
     */
    public function baseUrl(): string
    {
        $raw = trim((string) (setting('openai_advertiser_api_endpoint') ?: config('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1')));
        if ($raw === '') {
            $raw = 'https://api.ads.openai.com/v1';
        }

        $clean = rtrim($raw, '/');

        // Strip any trailing /v1 (or multiple duplicate /v1) so only exactly one /v1 remains
        while (preg_match('#/v1$#i', $clean)) {
            $clean = substr($clean, 0, -3);
        }

        return $clean . '/v1';
    }

    /**
     * Build a fully qualified API endpoint URL.
     */
    public function endpoint(string $path = ''): string
    {
        $base = $this->baseUrl();
        $trimmed = ltrim($path, '/');

        return $trimmed === '' ? $base : "{$base}/{$trimmed}";
    }

    /**
     * Verify the configured OpenAI Advertiser API credentials and retrieve ad account context.
     * Request: GET https://api.ads.openai.com/v1/ad_account
     * Headers: Authorization: Bearer {API_KEY}
     */
    public function verifyAdAccount(): array
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return [
                'success' => false,
                'status' => null,
                'error_type' => 'not_configured',
                'error' => 'OpenAI Advertiser API not configured. To display platform ad impressions, clicks and spend, configure OPENAI_ADVERTISER_API_KEY in your environment or Settings.',
                'account' => null,
                'endpoint' => $this->endpoint('ad_account'),
            ];
        }

        $url = $this->endpoint('ad_account');

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout((int) config('openai_ads.timeout', 5))
                ->get($url);

            $status = $response->status();
            $body = $response->body();

            // Secure logging: logs HTTP status, response body, URL, and timestamp (never logs the API key)
            $this->logApiExchange('GET', $url, $status, $body);

            if ($response->successful()) {
                $account = $response->json();

                return [
                    'success' => true,
                    'status' => $status,
                    'error_type' => null,
                    'error' => null,
                    'account' => $account,
                    'endpoint' => $url,
                ];
            }

            $errorInfo = $this->formatApiError($status, $body, $url);

            return [
                'success' => false,
                'status' => $status,
                'error_type' => $errorInfo['error_type'],
                'error' => $errorInfo['message'],
                'account' => null,
                'endpoint' => $url,
            ];
        } catch (\Throwable $e) {
            $this->logApiException('GET', $url, $e);

            return [
                'success' => false,
                'status' => null,
                'error_type' => 'network_error',
                'error' => 'Connection failure: Could not reach OpenAI Ads API (' . $e->getMessage() . '). Check network connectivity and DNS.',
                'account' => null,
                'endpoint' => $url,
            ];
        }
    }

    /**
     * Query the official OpenAI Advertiser API Insights endpoint for delivery metrics.
     * Request: GET https://api.ads.openai.com/v1/ad_account/insights
     * Never throws; marks 'available' => false when missing or failing.
     */
    public function platformMetrics(array $bounds): array
    {
        $apiKey = $this->apiKey();
        $accountId = $this->accountId();

        if ($apiKey === '') {
            return [
                'available' => false,
                'status' => null,
                'error_type' => 'not_configured',
                'reason' => 'OpenAI Advertiser API not configured. To display platform ad impressions, clicks and spend, configure OPENAI_ADVERTISER_API_KEY and OPENAI_ADVERTISER_ACCOUNT_ID.',
                'metrics' => null,
            ];
        }

        // Documented Insights endpoint: GET /v1/ad_account/insights (Account ID is NOT in the path)
        $url = $this->endpoint('ad_account/insights');

        try {
            $since = Carbon::parse($bounds['from'])->toDateString();
            $until = Carbon::parse($bounds['to'])->toDateString();

            // Official OpenAI Ads API specification:
            // time_ranges parameter must be an array of JSON-encoded strings, where each element
            // is a stringified date_range object:
            // e.g. time_ranges[0] = '{"type":"date_range","since":"YYYY-MM-DD","until":"YYYY-MM-DD"}'
            $timeRangeItem = json_encode([
                'type' => 'date_range',
                'since' => $since,
                'until' => $until,
            ]);

            $params = [
                'aggregation_level' => 'ad_account',
                'fields' => 'impressions,clicks,spend',
                'time_ranges' => [$timeRangeItem],
            ];

            // If an account ID is configured, include as an optional query param (never in URL path)
            if ($accountId !== '') {
                $params['account_id'] = $accountId;
            }

            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout((int) config('openai_ads.timeout', 5))
                ->get($url, $params);

            $status = $response->status();
            $body = $response->body();

            // Secure logging: records HTTP status, response body, URL, and timestamp (never logs the API key)
            $this->logApiExchange('GET', $url, $status, $body);

            if ($response->successful()) {
                $payload = $response->json();
                $impressions = 0;
                $clicks = 0;
                $spend = 0.0;

                $rows = $payload['data'] ?? ($payload['insights'] ?? null);

                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            $impressions += (int) ($row['impressions'] ?? 0);
                            $clicks += (int) ($row['clicks'] ?? 0);
                            $spend += (float) ($row['spend'] ?? 0.0);
                        }
                    }
                } else {
                    $impressions = (int) ($payload['impressions'] ?? 0);
                    $clicks = (int) ($payload['clicks'] ?? 0);
                    $spend = (float) ($payload['spend'] ?? 0.0);
                }

                return [
                    'available' => true,
                    'status' => $status,
                    'error_type' => null,
                    'reason' => null,
                    'metrics' => [
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'spend' => round($spend, 2),
                    ],
                ];
            }

            $errorInfo = $this->formatApiError($status, $body, $url);

            return [
                'available' => false,
                'status' => $status,
                'error_type' => $errorInfo['error_type'],
                'reason' => $errorInfo['message'],
                'metrics' => null,
            ];
        } catch (\Throwable $e) {
            $this->logApiException('GET', $url, $e);

            return [
                'available' => false,
                'status' => null,
                'error_type' => 'network_error',
                'reason' => 'Could not connect to OpenAI Advertiser API (' . $e->getMessage() . '). Check network connectivity and DNS.',
                'metrics' => null,
            ];
        }
    }

    /**
     * Format descriptive error messages distinguishing 401, 403, 404, 422, 429, 5xx, and network errors.
     *
     * @return array{error_type: string, message: string}
     */
    public function formatApiError(int $status, string $body, string $url): array
    {
        $json = json_decode($body, true);
        $detail = $json['error']['message'] ?? ($json['message'] ?? null);
        $detailSuffix = $detail ? " Message: {$detail}" : '';

        $errorType = match ($status) {
            400, 422 => 'validation_error',
            401 => 'auth_error',
            403 => 'permission_error',
            404 => 'not_found',
            429 => 'rate_limited',
            default => ($status >= 500) ? 'server_error' : 'http_error',
        };

        $message = match ($status) {
            400 => "Bad request (HTTP 400): OpenAI Ads API rejected the request parameters.{$detailSuffix}",
            401 => "Authentication failed (HTTP 401): Invalid, expired, or missing OpenAI Advertiser API key. Verify OPENAI_ADVERTISER_API_KEY in your environment or Settings.{$detailSuffix}",
            403 => "Access denied (HTTP 403): The API key lacks permission to access this ad account or reporting insights. Verify account permissions in OpenAI Ads Manager.{$detailSuffix}",
            404 => "Endpoint not found (HTTP 404): The requested OpenAI Ads resource ({$url}) was not found. Verify the API base URL and endpoint path (expected: /ad_account or /ad_account/insights). Ensure the account ID is not incorrectly appended to the path.{$detailSuffix}",
            422 => "Validation failed (HTTP 422): OpenAI Ads API rejected the request parameters.{$detailSuffix}",
            429 => "Rate limit exceeded (HTTP 429): OpenAI Ads API request quota exceeded. Please try again later.{$detailSuffix}",
            default => ($status >= 500)
                ? "OpenAI Ads server error (HTTP {$status}): The upstream OpenAI service is temporarily unavailable.{$detailSuffix}"
                : "OpenAI Advertiser API returned HTTP {$status}.{$detailSuffix}",
        };

        return [
            'error_type' => $errorType,
            'message' => $message,
        ];
    }

    /**
     * Log HTTP exchange securely. Never logs the Authorization header or API key.
     */
    protected function logApiExchange(string $method, string $url, int $status, string $body): void
    {
        Log::info('[openai_advertiser_api] HTTP response', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'response_body' => Str::limit($body, 1500),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log request exception securely. Never logs the API key.
     */
    protected function logApiException(string $method, string $url, \Throwable $e): void
    {
        Log::warning('[openai_advertiser_api] Request failed', [
            'method' => $method,
            'url' => $url,
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Storefront behavioral metrics for ChatGPT Ads traffic.
     */
    protected function behaviorMetrics(array $bounds): array
    {
        $sessionIds = $this->chatGptSessionIds($bounds);

        $visits = AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->where('event_type', AnalyticsEvent::PAGE_VIEW)
            ->where(function ($q) {
                $q->where('source', 'like', '%chatgpt%')
                    ->orWhere('source', 'chatgpt')
                    ->orWhereNotNull('payload->oppref');
            })
            ->count();

        $productViews = 0;
        $addToCarts = 0;
        $checkoutStarts = 0;

        if ($sessionIds->isNotEmpty()) {
            $productViews = AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
                ->where('event_type', AnalyticsEvent::VIEW_ITEM)
                ->whereIn('session_id', $sessionIds)
                ->count();

            $addToCarts = AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
                ->where('event_type', AnalyticsEvent::ADD_TO_CART)
                ->whereIn('session_id', $sessionIds)
                ->count();

            $checkoutStarts = AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
                ->where('event_type', AnalyticsEvent::BEGIN_CHECKOUT)
                ->whereIn('session_id', $sessionIds)
                ->count();
        }

        return [
            'visits' => $visits,
            'product_views' => $productViews,
            'add_to_carts' => $addToCarts,
            'checkout_starts' => $checkoutStarts,
        ];
    }

    /**
     * Order and revenue metrics for ChatGPT Ads traffic.
     */
    protected function orderMetrics(array $bounds): array
    {
        $base = Order::query()
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->where(function ($q) {
                $q->whereNotNull('oppref')
                    ->orWhere('attribution_source', 'chatgpt_ads')
                    ->orWhere('attribution_source', 'chatgpt');
            });

        $ordersCreated = (clone $base)->count();

        $codQuery = (clone $base)->where('payment_method', 'cod');
        $codOrders = (clone $codQuery)->count();
        $codOrderValue = round((float) (clone $codQuery)->sum('grand_total'), 2);

        $prepaidQuery = (clone $base)->where('payment_method', '!=', 'cod');
        $prepaidOrders = (clone $prepaidQuery)->count();
        $prepaidOrderValue = round((float) (clone $prepaidQuery)->sum('grand_total'), 2);

        // Paid Orders: Only orders where payment_status is 'paid' (including paid prepaid & collected COD)
        $paidQuery = (clone $base)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'failed']);

        $paidOrders = (clone $paidQuery)->count();
        $paidRevenue = round((float) (clone $paidQuery)->sum('amount_paid'), 2);

        // Break down paid revenue into prepaid vs collected COD
        $prepaidPaidRevenue = round((float) (clone $paidQuery)->where('payment_method', '!=', 'cod')->sum('amount_paid'), 2);
        $codCollectedRevenue = round((float) (clone $paidQuery)->where('payment_method', 'cod')->sum('amount_paid'), 2);

        $cancelledOrders = (clone $base)->where('order_status', 'cancelled')->count();
        $refundedOrders = (clone $base)->whereIn('payment_status', ['refunded', 'partially_refunded'])->count();

        // Conversions API delivery statistics
        $conversions = AnalyticsConversion::query()
            ->where('event_type', 'order_created')
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->get();

        $capiSent = $conversions->where('openai_state', 'sent')->count();
        $capiFailed = $conversions->where('openai_state', 'failed')->count();
        $capiExpired = $conversions->where('openai_state', 'expired')->count();
        $capiRejected = $conversions->where('openai_state', 'rejected')->count();
        $capiPending = $conversions->where(fn ($c) => $c->openai_state === null || $c->openai_state === 'pending')->count();

        return [
            'orders_created' => $ordersCreated,
            'paid_orders' => $paidOrders,
            'paid_revenue' => $paidRevenue,
            'cod_orders' => $codOrders,
            'cod_order_value' => $codOrderValue,
            'prepaid_orders' => $prepaidOrders,
            'prepaid_order_value' => $prepaidOrderValue,
            'prepaid_paid_revenue' => $prepaidPaidRevenue,
            'cod_collected_revenue' => $codCollectedRevenue,
            'cancelled_orders' => $cancelledOrders,
            'refunded_orders' => $refundedOrders,
            'capi' => [
                'sent' => $capiSent,
                'failed' => $capiFailed,
                'expired' => $capiExpired,
                'rejected' => $capiRejected,
                'pending' => $capiPending,
            ],
        ];
    }

    /**
     * Campaign-level performance breakdown.
     */
    protected function campaignBreakdown(array $bounds): array
    {
        $orders = Order::query()
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->where(function ($q) {
                $q->whereNotNull('oppref')
                    ->orWhere('attribution_source', 'chatgpt_ads');
            })
            ->select(
                DB::raw("COALESCE(attribution_campaign, 'Direct / Unspecified') as campaign"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status NOT IN ('cancelled', 'failed') THEN 1 ELSE 0 END) as paid_orders"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status NOT IN ('cancelled', 'failed') THEN amount_paid ELSE 0 END) as revenue")
            )
            ->groupBy('campaign')
            ->orderByDesc('revenue')
            ->get();

        return $orders->map(fn ($row) => [
            'campaign' => (string) $row->campaign,
            'orders' => (int) $row->total_orders,
            'paid_orders' => (int) $row->paid_orders,
            'revenue' => round((float) $row->revenue, 2),
        ])->all();
    }

    /**
     * Product-level performance under ChatGPT Ads traffic.
     */
    protected function productBreakdown(array $bounds): array
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.created_at', [$bounds['from'], $bounds['to']])
            ->where(function ($q) {
                $q->whereNotNull('o.oppref')
                    ->orWhere('o.attribution_source', 'chatgpt_ads');
            })
            ->select(
                'oi.product_name',
                'oi.sku',
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('SUM(oi.quantity) as units_sold'),
                DB::raw("SUM(CASE WHEN o.payment_status = 'paid' AND o.order_status NOT IN ('cancelled', 'failed') THEN oi.total_price ELSE 0 END) as paid_revenue")
            )
            ->groupBy('oi.product_name', 'oi.sku')
            ->orderByDesc('paid_revenue')
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'product_name' => (string) $r->product_name,
            'sku' => (string) ($r->sku ?: '—'),
            'orders_count' => (int) $r->orders_count,
            'units_sold' => (int) $r->units_sold,
            'revenue' => round((float) $r->paid_revenue, 2),
        ])->all();
    }

    /**
     * Daily trend series for charting.
     */
    protected function dailySeries(array $bounds): array
    {
        $start = Carbon::parse($bounds['from'])->startOfDay();
        $end = Carbon::parse($bounds['to'])->endOfDay();

        $days = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $days[$current->toDateString()] = [
                'date' => $current->toDateString(),
                'label' => $current->format('M d'),
                'visits' => 0,
                'add_to_carts' => 0,
                'orders' => 0,
                'revenue' => 0.0,
            ];
            $current->addDay();
        }

        // Visits by date
        $visits = AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->where('event_type', AnalyticsEvent::PAGE_VIEW)
            ->where(function ($q) {
                $q->where('source', 'like', '%chatgpt%')
                    ->orWhereNotNull('payload->oppref');
            })
            ->select(DB::raw('DATE(occurred_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->get();

        foreach ($visits as $v) {
            if (isset($days[$v->date])) {
                $days[$v->date]['visits'] = (int) $v->count;
            }
        }

        // Orders & revenue by date
        $orders = Order::query()
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->where(function ($q) {
                $q->whereNotNull('oppref')
                    ->orWhere('attribution_source', 'chatgpt_ads');
            })
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status NOT IN ('cancelled', 'failed') THEN amount_paid ELSE 0 END) as revenue")
            )
            ->groupBy('date')
            ->get();

        foreach ($orders as $o) {
            if (isset($days[$o->date])) {
                $days[$o->date]['orders'] = (int) $o->total_orders;
                $days[$o->date]['revenue'] = round((float) $o->revenue, 2);
            }
        }

        return array_values($days);
    }

    /**
     * Distinct session IDs associated with ChatGPT Ads traffic.
     */
    protected function chatGptSessionIds(array $bounds)
    {
        return AnalyticsEvent::whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->where('event_type', AnalyticsEvent::PAGE_VIEW)
            ->where(function ($q) {
                $q->where('source', 'like', '%chatgpt%')
                    ->orWhereNotNull('payload->oppref');
            })
            ->distinct()
            ->pluck('session_id')
            ->filter();
    }
}

