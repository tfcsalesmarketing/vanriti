<?php

namespace App\Services\Analytics;

use App\Models\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Internal-only (Phase 0) Analytics Command Center aggregations.
 *
 * The report is entirely database driven: no external analytics APIs are
 * called and no customer personally-identifiable information is returned.
 * All monetary values are derived from the historically immutable order /
 * order_items snapshots, mirroring {@see EcommerceDataService::purchaseEligible()}.
 */
class AnalyticsCommandCenterService
{
    public const VERSION = '16';

    protected const CACHE_TTL = 300;

    protected const CURRENCY = 'INR';

    protected const MAX_RANGE_DAYS = 730;

    /** Order statuses that are not counted as monetarily qualifying. */
    protected const EXCLUDED_ORDER_STATUSES = ['cancelled', 'failed'];

    /** Payment statuses that are not counted as monetarily qualifying. */
    protected const EXCLUDED_PAYMENT_STATUSES = ['refunded', 'partially_refunded'];

    public function report(string $from, string $to): array
    {
        $key = $this->cacheKey($from, $to);

        try {
            return Cache::remember($key, self::CACHE_TTL, fn () => $this->build($from, $to));
        } catch (\Throwable $e) {
            Log::warning('[analytics] cache failure, computing report directly', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return $this->build($from, $to);
        }
    }

    public function productReport(string $from, string $to, int $page = 1): array
    {
        $page = max(1, min((int) $page, 100000));
        $key = $this->cacheKey($from, $to, 'products', $page);

        try {
            return Cache::remember($key, self::CACHE_TTL, fn () => $this->buildProductReport($from, $to, $page));
        } catch (\Throwable $e) {
            Log::warning('[analytics] product report cache failure', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return $this->buildProductReport($from, $to, $page);
        }
    }

    public function cacheKey(string $from, string $to, string $suffix = '', int|string $page = ''): string
    {
        return 'analytics.command-center.'.self::VERSION.'.'.$from.'.'.$to
            .($suffix !== '' ? '.'.$suffix : '')
            .($page !== '' ? '.'.$page : '');
    }

    public function maxRangeDays(): int
    {
        return self::MAX_RANGE_DAYS;
    }

    protected function build(string $from, string $to): array
    {
        $bounds = $this->bounds($from, $to);

        $executive = $this->section('executive', fn () => $this->executive($bounds), $this->emptyExecutive());
        $revenue = $this->section('revenue', fn () => $this->revenue($bounds), $this->emptyRevenue());
        $funnel = $this->section('funnel', fn () => $this->funnel($bounds), $this->emptyFunnel());
        $dadi = $this->section('dadi', fn () => $this->dadi($bounds), $this->emptyDadi());
        $products = $this->section('products', fn () => $this->products($bounds), $this->emptyProducts());
        $tracking = $this->section('tracking', fn () => $this->tracking($bounds, $executive), $this->emptyTracking());

        $alerts = $this->alerts($tracking);

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
                'days' => $bounds['from']->copy()->startOfDay()->diffInDays($bounds['to']->copy()->startOfDay()) + 1,
                'generated_at' => Carbon::now()->toDateTimeString(),
            ],
            'executive' => $executive,
            'revenue' => $revenue,
            'funnel' => $funnel,
            'dadi' => $dadi,
            'products' => $products,
            'tracking' => $tracking,
            'alerts' => $alerts,
        ];
    }

    /**
     * Section guard: a failing section surfaces a readable "temporarily unavailable"
     * state (with safe empty values) instead of taking down the whole command center.
     */
    protected function section(string $name, Closure $cb, array $empty): array
    {
        try {
            return $cb();
        } catch (\Throwable $e) {
            Log::warning('[analytics] report section failed', [
                'section' => $name,
                'message' => $e->getMessage(),
            ]);

            return $empty + ['error' => true, 'message' => 'Analytics temporarily unavailable. Try again shortly.'];
        }
    }

    protected function bounds(string $from, string $to): array
    {
        return [
            'from' => Carbon::parse($from.' 00:00:00'),
            'to' => Carbon::parse($to.' 23:59:59'),
        ];
    }

    /**
     * Mirrors EcommerceDataService::purchaseEligible(): cancelled/failed orders,
     * refunded/partially-refunded payments and non-paid online orders are excluded.
     * Allows COD regardless of payment status.
     */
    protected function qualifying(Builder $query): Builder
    {
        return $query
            ->whereNotIn('o.order_status', self::EXCLUDED_ORDER_STATUSES)
            ->whereNotIn('o.payment_status', self::EXCLUDED_PAYMENT_STATUSES)
            ->where(function ($q) {
                $q->where('o.payment_method', 'cod')
                    ->orWhere('o.payment_status', 'paid');
            });
    }

    protected function ordersBetween(Builder $query, array $bounds): Builder
    {
        return $query->whereBetween('o.created_at', [$bounds['from'], $bounds['to']]);
    }

    protected function executive(array $bounds): array
    {
        $revenue = $this->qualifying($this->ordersBetween(DB::table('orders as o'), $bounds))->sum('o.grand_total');

        $ordersQ = $this->qualifying($this->ordersBetween(DB::table('orders as o'), $bounds));
        $orders = (clone $ordersQ)->count();
        $customers = (clone $ordersQ)->distinct('o.user_id')->count('o.user_id');

        $refunds = DB::table('refunds')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as refund_amount')
            ->first();

        $tracked = $this->trackedOrders($bounds);
        $coverage = $orders > 0 ? round(($tracked / $orders) * 100, 1) : null;

        return [
            'revenue' => round((float) $revenue, 2),
            'orders' => $orders,
            'avg_order_value' => $orders > 0 ? round((float) $revenue / $orders, 2) : 0.0,
            'customers' => $customers,
            'refund_value' => round((float) ($refunds->refund_amount ?? 0), 2),
            'refund_count' => (int) ($refunds->cnt ?? 0),
            'tracked_orders' => $tracked,
            'untracked_orders' => max(0, $orders - $tracked),
            'coverage_percent' => $coverage,
        ];
    }

    protected function revenue(array $bounds): array
    {
        $base = fn () => $this->qualifying($this->ordersBetween(DB::table('orders as o'), $bounds));

        $revenue = (float) $base()->sum('o.grand_total');
        $orders = $base()->count();
        $paid = (clone $base())->where('o.payment_status', 'paid')->count();
        $cod = (clone $base())->where('o.payment_method', 'cod')->count();

        $all = $this->ordersBetween(DB::table('orders as o'), $bounds);
        $cancelled = (clone $all)->where('o.order_status', 'cancelled')->count();
        $failed = (clone $all)->where('o.order_status', 'failed')->count();
        $refunded = (clone $all)->where('o.payment_status', 'refunded')->count();
        $partiallyRefunded = (clone $all)->where('o.payment_status', 'partially_refunded')->count();

        $coupons = DB::table('orders as o')
            ->whereBetween('o.created_at', [$bounds['from'], $bounds['to']])
            ->where('o.coupon_discount', '>', 0)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(o.coupon_discount), 0) as discount_total')
            ->first();

        $series = $this->series($bounds);

        return [
            'revenue' => round($revenue, 2),
            'orders' => $orders,
            'avg_order_value' => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
            'paid_orders' => $paid,
            'cod_orders' => $cod,
            'cancelled_orders' => $cancelled,
            'failed_orders' => $failed,
            'refunded_orders' => $refunded,
            'partially_refunded_orders' => $partiallyRefunded,
            'coupon_orders' => (int) ($coupons->cnt ?? 0),
            'coupon_discount' => round((float) ($coupons->discount_total ?? 0), 2),
            'series' => $series,
        ];
    }

    protected function series(array $bounds): array
    {
        $rows = $this->qualifying($this->ordersBetween(DB::table('orders as o'), $bounds))
            ->select(DB::raw('DATE(o.created_at) as date'), DB::raw('SUM(o.grand_total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy(DB::raw('DATE(o.created_at)'))
            ->orderBy('date')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->date] = $row;
        }

        $series = [];
        $cursor = $bounds['from']->copy()->startOfDay();
        $end = $bounds['to']->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $row = $byDate[$date] ?? null;
            $series[] = [
                'date' => $date,
                'revenue' => round((float) ($row->revenue ?? 0), 2),
                'orders' => (int) ($row->orders ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    protected function funnel(array $bounds): array
    {
        $all = $this->ordersBetween(DB::table('orders as o'), $bounds);
        $allOrders = (clone $all)->count();
        $qualifying = $this->qualifying((clone $all))->count();
        $revenue = $this->qualifying($this->ordersBetween(DB::table('orders as o'), $bounds))->sum('o.grand_total');

        $cancelled = (clone $all)->where('o.order_status', 'cancelled')->count();
        $failed = (clone $all)->where('o.order_status', 'failed')->count();
        $refunded = (clone $all)->where('o.payment_status', 'refunded')->count();
        $partiallyRefunded = (clone $all)->where('o.payment_status', 'partially_refunded')->count();

        $abandonedUsers = $this->cartUsers($bounds);
        $qualifiedUserIds = $this->qualifying(
            $this->ordersBetween(DB::table('orders as o'), $bounds)
        )->pluck('o.user_id')->all();

        $activeCarts = DB::table('carts as c')
            ->join('cart_items as ci', 'ci.cart_id', '=', 'c.id')
            ->where('c.owner_type', User::class)
            ->whereBetween('c.updated_at', [$bounds['from'], $bounds['to']])
            ->distinct('c.id')
            ->count('c.id');

        return [
            'all_orders' => $allOrders,
            'qualifying_orders' => $qualifying,
            'qualifying_revenue' => round((float) $revenue, 2),
            'cancelled' => $cancelled,
            'failed' => $failed,
            'refunded' => $refunded,
            'partially_refunded' => $partiallyRefunded,
            'abandoned_cart_estimate' => count(array_diff($abandonedUsers, $qualifiedUserIds)),
            'active_cart_owners' => count($abandonedUsers),
            'active_carts' => $activeCarts,
        ];
    }

    protected function cartUsers(array $bounds): array
    {
        return DB::table('carts as c')
            ->join('cart_items as ci', 'ci.cart_id', '=', 'c.id')
            ->where('c.owner_type', User::class)
            ->whereNotNull('c.owner_id')
            ->whereBetween('c.updated_at', [$bounds['from'], $bounds['to']])
            ->distinct('c.owner_id')
            ->pluck('c.owner_id')
            ->all();
    }

    protected function products(array $bounds): array
    {
        $topByRevenue = $this->productAggregates($bounds)
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $topSkus = $this->productAggregates($bounds, 'sku')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return [
            'top_by_revenue' => $topByRevenue,
            'top_skus' => $topSkus,
        ];
    }

    protected function productAggregates(array $bounds, string $mode = 'product'): Builder
    {
        $query = $this->qualifying(
            $this->ordersBetween(DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id'), $bounds)
        );

        if ($mode === 'sku') {
            return $query
                ->select(
                    DB::raw('COALESCE(oi.sku, \'\') as sku'),
                    DB::raw('SUM(oi.quantity) as units'),
                    DB::raw('SUM(oi.total_price) as revenue'),
                    DB::raw('COUNT(DISTINCT oi.order_id) as orders')
                )
                ->whereNotNull('oi.sku')
                ->where('oi.sku', '!=', '')
                ->groupBy(DB::raw('COALESCE(oi.sku, \'\')'));
        }

        return $query
            ->select(
                'oi.product_id as product_id',
                DB::raw('COALESCE(p.name, oi.product_name) as product_name'),
                DB::raw('COALESCE(p.sku, oi.sku) as sku'),
                DB::raw('SUM(oi.quantity) as units'),
                DB::raw('SUM(oi.total_price) as revenue'),
                DB::raw('COUNT(DISTINCT oi.order_id) as orders'),
                DB::raw('COALESCE(p.status, \'\') as status'),
                DB::raw('COALESCE(p.stock, 0) as stock')
            )
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->groupBy('oi.product_id', DB::raw('COALESCE(p.name, oi.product_name)'), DB::raw('COALESCE(p.sku, oi.sku)'), DB::raw('COALESCE(p.status, \'\')'), DB::raw('COALESCE(p.stock, 0)'));
    }

    protected function dadi(array $bounds): array
    {
        $ev = DB::table('dadi_recommendation_events as e');
        $events = $ev->whereBetween('e.created_at', [$bounds['from'], $bounds['to']]);

        $counts = [
            'impressions' => (clone $events)->where('e.action', 'impression')->count(),
            'clicks' => (clone $events)->where('e.action', 'click')->count(),
            'add_to_cart' => (clone $events)->where('e.action', 'add_to_cart')->count(),
            'buy_now' => (clone $events)->where('e.action', 'buy_now')->count(),
        ];

        $purchases = (clone $events)
            ->where('e.action', 'purchase')
            ->whereNotNull('e.order_number')
            ->distinct('e.order_number')
            ->count('e.order_number');

        $attributedRevenue = $this->qualifying(
            DB::table('orders as o')
                ->whereBetween('o.created_at', [$bounds['from'], $bounds['to']])
                ->whereIn('o.order_number', DB::table('dadi_recommendation_events as d')
                    ->whereBetween('d.created_at', [$bounds['from'], $bounds['to']])
                    ->where('d.action', 'purchase')
                    ->whereNotNull('d.order_number')
                    ->distinct()
                    ->select('d.order_number'))
        )->sum('o.grand_total');

        $topImpressions = DB::table('dadi_recommendation_events as e')
            ->join('products as p', 'p.id', '=', 'e.product_id')
            ->whereBetween('e.created_at', [$bounds['from'], $bounds['to']])
            ->where('e.action', 'impression')
            ->select('p.name as product_name', DB::raw('COUNT(*) as count'))
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $topConverting = DB::table('dadi_recommendation_events as e')
            ->join('products as p', 'p.id', '=', 'e.product_id')
            ->whereBetween('e.created_at', [$bounds['from'], $bounds['to']])
            ->where('e.action', 'purchase')
            ->select('p.name as product_name', DB::raw('COUNT(*) as count'))
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $conversations = DB::table('dadi_conversations')->whereBetween('created_at', [$bounds['from'], $bounds['to']]);

        $convTotal = (clone $conversations)->count();
        $convGuest = (clone $conversations)->whereNull('user_id')->count();
        $convAuthenticated = (clone $conversations)->whereNotNull('user_id')->count();

        $convByStatus = (clone $conversations)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $convByLocale = (clone $conversations)
            ->select('locale', DB::raw('COUNT(*) as count'))
            ->whereNotNull('locale')
            ->groupBy('locale')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->pluck('count', 'locale')
            ->toArray();

        return [
            'impressions' => $counts['impressions'],
            'clicks' => $counts['clicks'],
            'add_to_cart' => $counts['add_to_cart'],
            'buy_now' => $counts['buy_now'],
            'purchases' => $purchases,
            'attributed_revenue' => round((float) $attributedRevenue, 2),
            'ctr' => $counts['impressions'] > 0 ? round(($counts['clicks'] / $counts['impressions']) * 100, 1) : null,
            'cvr' => $counts['clicks'] > 0 ? round(($purchases / $counts['clicks']) * 100, 1) : null,
            'top_impressions' => $topImpressions,
            'top_converting' => $topConverting,
            'conversations' => [
                'total' => $convTotal,
                'guest' => $convGuest,
                'authenticated' => $convAuthenticated,
                'by_status' => $convByStatus,
                'by_locale' => $convByLocale,
            ],
        ];
    }

    protected function tracking(array $bounds, array $executive): array
    {
        $pixelId = (string) setting('meta_pixel_id', '');
        $pixelConfigured = $pixelId !== '';

        $gtmConfigured = (string) config('analytics.gtm_container_id', '') !== '';
        $capiEnabled = (bool) config('meta.enabled');

        $tracked = $this->trackedOrders($bounds);

        $coverage = null;
        if (! isset($executive['error']) && ($executive['orders'] ?? 0) > 0) {
            $coverage = round(($tracked / $executive['orders']) * 100, 1);
        }

        $missing = $this->qualifying($this->ordersBetween(DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id'), $bounds));

        $missingSkuOrders = (clone $missing)
            ->where(function ($q) {
                $q->whereNull('oi.sku')->orWhere('oi.sku', '=', '');
            })
            ->distinct('oi.order_id')
            ->count('oi.order_id');

        $productsWithoutSku = DB::table('products')
            ->whereNull('sku')
            ->orWhere('sku', '=', '')
            ->count();

        $duplicateSkus = DB::table('products')
            ->select('sku')
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('sku')
            ->count();

        $activeOutOfStock = DB::table('products')
            ->where('status', 'active')
            ->where('stock', '<=', 0)
            ->count();

        $activeLowStock = DB::table('products')
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();

        $ledgerTotal = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']])
            ->count();

        $ledgerFailed = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->where('created_at', '>', Carbon::now()->subDays(7))
            ->whereIn('meta_state', ['failed'])
            ->count();

        $purchases = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->whereBetween('created_at', [$bounds['from'], $bounds['to']]);

        $ledgerByState = [];
        foreach ($purchases->select('meta_state', DB::raw('COUNT(*) as count'))->groupBy('meta_state')->get() as $row) {
            $ledgerByState[$row->meta_state ?? 'null'] = (int) $row->count;
        }

        return [
            'currency' => self::CURRENCY,
            'gtm_configured' => $gtmConfigured,
            'gtm_container_id' => (string) config('analytics.gtm_container_id', ''),
            'ga4_configured' => $gtmConfigured,
            'pixel_configured' => $pixelConfigured,
            'pixel_masked' => $pixelConfigured ? 'xxxx'.substr($pixelId, -4) : '',
            'capi_enabled' => $capiEnabled,
            'products_without_sku' => $productsWithoutSku,
            'duplicate_skus' => $duplicateSkus,
            'orders_missing_item_sku' => $missingSkuOrders,
            'active_out_of_stock' => $activeOutOfStock,
            'active_low_stock' => $activeLowStock,
            'tracked_orders' => $tracked,
            'untracked_orders' => max(0, ($executive['orders'] ?? 0) - $tracked),
            'coverage_percent' => $coverage,
            'ledger_total' => $ledgerTotal,
            'ledger_by_state' => $ledgerByState,
            'ledger_failures_7d' => $ledgerFailed,
        ];
    }

    protected function trackedOrders(array $bounds): int
    {
        return DB::table('orders as o')
            ->whereBetween('o.created_at', [$bounds['from'], $bounds['to']])
            ->whereNotIn('o.order_status', self::EXCLUDED_ORDER_STATUSES)
            ->whereNotIn('o.payment_status', self::EXCLUDED_PAYMENT_STATUSES)
            ->where(function ($q) {
                $q->where('o.payment_method', 'cod')
                    ->orWhere('o.payment_status', 'paid');
            })
            ->whereIn('o.order_number', DB::table('analytics_conversions as ac')
                ->whereBetween('ac.created_at', [$bounds['from'], $bounds['to']])
                ->where('ac.event_type', 'purchase')
                ->distinct()
                ->select('ac.order_number'))
            ->distinct('o.order_number')
            ->count('o.order_number');
    }

    protected function alerts(array $tracking): array
    {
        $alerts = [];

        if ($tracking['error'] ?? false) {
            return [
                ['severity' => 'warning', 'title' => 'Tracking diagnostics unavailable', 'detail' => 'Diagnostic data could not be computed for this period.'],
            ];
        }

        if ($tracking['pixel_configured']) {
            $alerts[] = ['severity' => 'info', 'title' => 'Meta Pixel configured', 'detail' => 'Browser-side pixel is configured (ID ending '.$tracking['pixel_masked'].').'];
        } else {
            $alerts[] = ['severity' => 'critical', 'title' => 'Meta Pixel is not configured', 'detail' => 'No pixel ID is set in Settings → SEO; storefront Meta tracking will not fire.'];
        }

        if ($tracking['capi_enabled']) {
            $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API is enabled', 'detail' => 'Server-side delivery is active. Verify token validity and monitor the conversion ledger.'];
        } else {
            $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API is disabled', 'detail' => 'Intentionally disabled. CAPI activation is deferred until consent/privacy readiness is complete.'];
        }

        if ($tracking['gtm_configured']) {
            $alerts[] = ['severity' => 'info', 'title' => 'GTM container configured', 'detail' => 'GTM ('.$tracking['gtm_container_id'].') is configured in the application.'];
        } else {
            $alerts[] = ['severity' => 'warning', 'title' => 'No GTM container configured', 'detail' => 'GA4/GTM browser tracking may be inactive; no container ID is set.'];
        }

        if ($tracking['duplicate_skus'] > 0) {
            $alerts[] = ['severity' => 'critical', 'title' => 'Duplicate product SKUs detected', 'detail' => $tracking['duplicate_skus'].' duplicated SKU(s) found in the catalogue — fix before relying on SKU-level metrics.'];
        }

        if ($tracking['products_without_sku'] > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Products missing SKU', 'detail' => $tracking['products_without_sku'].' active product(s) have no SKU; they are excluded from SKU aggregation.'];
        }

        if ($tracking['orders_missing_item_sku'] > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Order items missing SKU', 'detail' => $tracking['orders_missing_item_sku'].' qualifying order item(s) in this period lack a SKU.'];
        }

        if ($tracking['ledger_failures_7d'] > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Recent Meta delivery failures', 'detail' => $tracking['ledger_failures_7d'].' conversion ledger records failed in the last 7 days.'];
        }

        if ($tracking['active_out_of_stock'] > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Active products out of stock', 'detail' => $tracking['active_out_of_stock'].' active product(s) have zero stock.'];
        }

        if ($tracking['active_low_stock'] > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Active products low on stock', 'detail' => $tracking['active_low_stock'].' active product(s) are at or below their low-stock threshold.'];
        }

        if (($tracking['untracked_orders'] ?? 0) > 0 && ($tracking['tracked_orders'] ?? 0) + ($tracking['untracked_orders'] ?? 0) > 0) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Incomplete conversion tracking coverage', 'detail' => ($tracking['untracked_orders'] ?? 0).' qualifying order(s) have no purchase conversion record in the ledger this period.'];
        }

        return $alerts;
    }

    protected function buildProductReport(string $from, string $to, int $page): array
    {
        $bounds = $this->bounds($from, $to);

        $query = $this->productAggregates($bounds)->orderByDesc('revenue');

        $paginator = $query->paginate(15, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        return [
            'items' => $items,
            'total' => (int) $paginator->total(),
            'per_page' => (int) $paginator->perPage(),
            'current_page' => (int) $paginator->currentPage(),
            'last_page' => (int) $paginator->lastPage(),
        ];
    }

    protected function emptyExecutive(): array
    {
        return [
            'revenue' => 0.0,
            'orders' => 0,
            'avg_order_value' => 0.0,
            'customers' => 0,
            'refund_value' => 0.0,
            'refund_count' => 0,
            'tracked_orders' => 0,
            'untracked_orders' => 0,
            'coverage_percent' => null,
        ];
    }

    protected function emptyRevenue(): array
    {
        return [
            'revenue' => 0.0,
            'orders' => 0,
            'avg_order_value' => 0.0,
            'paid_orders' => 0,
            'cod_orders' => 0,
            'cancelled_orders' => 0,
            'failed_orders' => 0,
            'refunded_orders' => 0,
            'partially_refunded_orders' => 0,
            'coupon_orders' => 0,
            'coupon_discount' => 0.0,
            'series' => [],
        ];
    }

    protected function emptyFunnel(): array
    {
        return [
            'all_orders' => 0,
            'qualifying_orders' => 0,
            'qualifying_revenue' => 0.0,
            'cancelled' => 0,
            'failed' => 0,
            'refunded' => 0,
            'partially_refunded' => 0,
            'abandoned_cart_estimate' => 0,
            'active_cart_owners' => 0,
            'active_carts' => 0,
        ];
    }

    protected function emptyDadi(): array
    {
        return [
            'impressions' => 0,
            'clicks' => 0,
            'add_to_cart' => 0,
            'buy_now' => 0,
            'purchases' => 0,
            'attributed_revenue' => 0.0,
            'ctr' => null,
            'cvr' => null,
            'top_impressions' => collect(),
            'top_converting' => collect(),
            'conversations' => [
                'total' => 0,
                'guest' => 0,
                'authenticated' => 0,
                'by_status' => [],
                'by_locale' => [],
            ],
        ];
    }

    protected function emptyProducts(): array
    {
        return [
            'top_by_revenue' => collect(),
            'top_skus' => collect(),
        ];
    }

    protected function emptyTracking(): array
    {
        return [
            'currency' => self::CURRENCY,
            'gtm_configured' => false,
            'gtm_container_id' => '',
            'ga4_configured' => false,
            'pixel_configured' => false,
            'pixel_masked' => '',
            'capi_enabled' => false,
            'products_without_sku' => 0,
            'duplicate_skus' => 0,
            'orders_missing_item_sku' => 0,
            'active_out_of_stock' => 0,
            'active_low_stock' => 0,
            'tracked_orders' => 0,
            'untracked_orders' => 0,
            'coverage_percent' => null,
            'ledger_total' => 0,
            'ledger_by_state' => [],
            'ledger_failures_7d' => 0,
        ];
    }
}
