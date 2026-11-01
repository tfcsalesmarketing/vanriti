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
     * Query the official OpenAI Advertiser API if configured.
     * Never throws; marks 'available' => false when missing or failing.
     */
    public function platformMetrics(array $bounds): array
    {
        $apiKey = trim((string) (secret_setting('openai_advertiser_api_key') ?: config('openai_ads.advertiser_api_key', '')));
        $accountId = trim((string) (setting('openai_advertiser_account_id') ?: config('openai_ads.advertiser_account_id', '')));

        if ($apiKey === '' || $accountId === '') {
            return [
                'available' => false,
                'reason' => 'OpenAI Advertiser API not configured. To display platform ad impressions, clicks and spend, configure OPENAI_ADVERTISER_API_KEY and OPENAI_ADVERTISER_ACCOUNT_ID.',
                'metrics' => null,
            ];
        }

        try {
            $base = rtrim(config('openai_ads.advertiser_api_endpoint', 'https://api.ads.openai.com/v1'), '/');
            $url = "{$base}/accounts/{$accountId}/insights";

            $response = Http::withToken($apiKey)
                ->timeout((int) config('openai_ads.timeout', 5))
                ->get($url, [
                    'start_date' => $bounds['from'],
                    'end_date' => $bounds['to'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'available' => true,
                    'reason' => null,
                    'metrics' => [
                        'impressions' => (int) ($data['impressions'] ?? 0),
                        'clicks' => (int) ($data['clicks'] ?? 0),
                        'spend' => (float) ($data['spend'] ?? 0.0),
                    ],
                ];
            }

            return [
                'available' => false,
                'reason' => 'OpenAI Advertiser API returned HTTP '.$response->status().'.',
                'metrics' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[analytics] OpenAI Advertiser API request failed', ['message' => $e->getMessage()]);

            return [
                'available' => false,
                'reason' => 'Could not connect to OpenAI Advertiser API: '.$e->getMessage(),
                'metrics' => null,
            ];
        }
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

