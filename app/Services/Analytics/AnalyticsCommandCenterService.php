<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Internal-only Analytics Command Center aggregations.
 *
 * The report is entirely database driven: no external analytics APIs are
 * called and no customer personally-identifiable information is returned.
 * All monetary values are derived from the historically immutable order /
 * order_items snapshots, mirroring {@see EcommerceDataService::purchaseEligible()}.
 *
 * Behavioral event counts (visitors / page views / product views / cart and
 * checkout events) come from the first-party analytics_events log. They are
 * aggregate measurements only — the database remains the single authoritative
 * source for orders, revenue and payments.
 */
class AnalyticsCommandCenterService
{
    public const VERSION = '19';

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

            try {
                return $this->buildProductReport($from, $to, $page);
            } catch (\Throwable $inner) {
                Log::warning('[analytics] product report failed', ['message' => $inner->getMessage()]);

                return [
                    'items' => [],
                    'total' => 0,
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 1,
                    'error' => true,
                    'message' => 'Product report temporarily unavailable.',
                ];
            }
        }
    }

    /**
     * Paginated, SKU-merged product analytics: first-party event behavior
     * (views / add-to-cart / checkout) combined with authoritative order-item
     * snapshots (purchases / units / revenue). Filters by product name and by
     * category (product_category pivot) are applied before pagination.
     */
    public function productEventReport(string $from, string $to, ?string $product = null, ?int $categoryId = null, int $page = 1): array
    {
        $page = max(1, min((int) $page, 100000));

        $suffix = 'products-event';
        if ($product !== null && trim($product) !== '') {
            $suffix .= '.q='.sha1(mb_strtolower(trim($product)));
        }
        if ($categoryId !== null) {
            $suffix .= '.c='.$categoryId;
        }

        $key = $this->cacheKey($from, $to, $suffix, $page);

        try {
            return Cache::remember($key, self::CACHE_TTL, fn () => $this->buildProductEventReport($from, $to, $product, $categoryId, $page));
        } catch (\Throwable $e) {
            Log::warning('[analytics] product event report cache failure', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            try {
                return $this->buildProductEventReport($from, $to, $product, $categoryId, $page);
            } catch (\Throwable $inner) {
                Log::warning('[analytics] product event report failed', ['message' => $inner->getMessage()]);

                return [
                    'items' => [],
                    'total' => 0,
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 1,
                    'error' => true,
                    'message' => 'Product analytics temporarily unavailable.',
                ];
            }
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
        $visitors = $this->section('visitors', fn () => $this->visitors($bounds), $this->emptyVisitors());
        $dadi = $this->section('dadi', fn () => $this->dadi($bounds), $this->emptyDadi());
        $products = $this->section('products', fn () => $this->products($bounds), $this->emptyProducts());
        $tracking = $this->section('tracking', fn () => $this->tracking($bounds, $executive), $this->emptyTracking());
        $events = $this->section('events', fn () => $this->events($bounds), $this->emptyEvents());
        $traffic = $this->section('traffic', fn () => $this->traffic($bounds), $this->emptyTraffic());
        $paymentStarted = $this->section('payment_started', fn () => ['count' => $this->paymentStarted($bounds)], ['count' => 0]);
        $compare = $this->section('compare', fn () => $this->compare($bounds), $this->emptyCompare());

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
            'visitors' => $visitors,
            'dadi' => $dadi,
            'products' => $products,
            'tracking' => $tracking,
            'events' => $events,
            'traffic' => $traffic,
            'payment_started' => $paymentStarted,
            'compare' => $compare,
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

    /**
     * Aggregate first-party behavioral counts from analytics_events.
     *
     * Visitors is the count of distinct hashed session_ids seen in page_view
     * during the period — it measures unique sessions, not unique people, and
     * excludes bot traffic. Every count here is an event measurement; the
     * database remains authoritative for orders, revenue and payments.
     */
    protected function events(array $bounds): array
    {
        $base = DB::table('analytics_events')
            ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']]);

        $count = fn (string $type) => (clone $base)->where('event_type', $type)->count();

        return [
            'visitors' => (clone $base)->where('event_type', AnalyticsEvent::PAGE_VIEW)
                ->distinct('session_id')
                ->count('session_id'),
            'page_views' => $count(AnalyticsEvent::PAGE_VIEW),
            'product_views' => $count(AnalyticsEvent::VIEW_ITEM),
            'add_to_carts' => $count(AnalyticsEvent::ADD_TO_CART),
            'begin_checkouts' => $count(AnalyticsEvent::BEGIN_CHECKOUT),
            'add_payment_infos' => $count(AnalyticsEvent::ADD_PAYMENT_INFO),
        ];
    }

    /**
     * Persistent anonymous visitor aggregation, counted from page_view events
     * that carry a visitor_id.
     *
     *   Website Visitors = distinct visitor_id with a page_view in the period.
     *   Sessions         = distinct session_id with a page_view in the period.
     *   New Visitors     = visitors whose earliest-ever page_view falls inside
     *                      the period.
     *   Returning        = active visitors whose earliest-ever page_view is
     *                      before the period started.
     *
     * The new/returning split is deliberately historical: the first-seen
     * lookup is not bounded by the period start, so a visitor first seen in
     * August and active in September is counted as returning. Without that
     * lookback every visitor would look new on the first day of any window.
     *
     * Counts are aggregate measurements only. visitor_id is a random anonymous
     * UUID: no PII, and it is never sent to Meta, GA4 or Google Ads.
     */
    protected function visitors(array $bounds): array
    {
        $sessions = $this->visitorPageViews()
            ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->distinct()
            ->count('session_id');

        $firstSeen = $this->visitorFirstSeen($bounds);

        $unique = $firstSeen->count();
        $new = 0;
        $newByDate = [];

        foreach ($firstSeen as $firstSeenAt) {
            $at = Carbon::parse($firstSeenAt);

            if ($at->gte($bounds['from'])) {
                $new++;
                $date = $at->toDateString();
                $newByDate[$date] = ($newByDate[$date] ?? 0) + 1;
            }
        }

        $returning = $unique - $new;

        return [
            'unique' => $unique,
            'new' => $new,
            'returning' => $returning,
            'sessions' => $sessions,
            'new_rate' => $unique > 0 ? round(($new / $unique) * 100, 1) : null,
            'returning_rate' => $unique > 0 ? round(($returning / $unique) * 100, 1) : null,
            'tracked' => $unique > 0,
            'series' => $this->visitorSeries($bounds, $newByDate),
        ];
    }

    /**
     * page_view rows that carry a usable visitor identity. Rows without one are
     * pre-migration history or server-side events and are simply skipped.
     */
    protected function visitorPageViews(): Builder
    {
        return DB::table('analytics_events')
            ->where('event_type', AnalyticsEvent::PAGE_VIEW)
            ->whereNotNull('visitor_id')
            ->where('visitor_id', '!=', '');
    }

    /**
     * Earliest-ever page_view timestamp for every visitor active in the period.
     *
     * The period filter appears only inside the subquery, never in the outer
     * aggregation — that is what makes the classification historical rather
     * than period-relative. The candidate set stays a subquery so it is never
     * materialised in PHP, and the (visitor_id, occurred_at) index serves both
     * the range scan and the MIN() grouping.
     *
     * @return Collection<int, string>
     */
    protected function visitorFirstSeen(array $bounds)
    {
        return DB::table('analytics_events as e')
            ->where('e.event_type', AnalyticsEvent::PAGE_VIEW)
            ->whereNotNull('e.visitor_id')
            ->where('e.visitor_id', '!=', '')
            ->whereIn('e.visitor_id', function ($query) use ($bounds) {
                $query->select('visitor_id')
                    ->from('analytics_events')
                    ->where('event_type', AnalyticsEvent::PAGE_VIEW)
                    ->whereNotNull('visitor_id')
                    ->where('visitor_id', '!=', '')
                    ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
                    ->groupBy('visitor_id');
            })
            ->groupBy('e.visitor_id')
            ->select(DB::raw('MIN(e.occurred_at) as first_seen'))
            ->get()
            ->pluck('first_seen');
    }

    /**
     * Per-day visitor series, gap-filled across the period like revenue/orders.
     *
     * A visitor is counted at most once per day (COUNT DISTINCT), and is "new"
     * only on the single day of their earliest-ever page_view — so daily new
     * plus daily returning always reconciles with daily visitors.
     *
     * @param  array<string, int>  $newByDate
     * @return list<array{date: string, visitors: int, new: int, returning: int}>
     */
    protected function visitorSeries(array $bounds, array $newByDate): array
    {
        $perDay = $this->visitorPageViews()
            ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->select(DB::raw('DATE(occurred_at) as date'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy(DB::raw('DATE(occurred_at)'))
            ->get()
            ->mapWithKeys(fn ($row) => [$row->date => (int) $row->visitors])
            ->all();

        $series = [];
        $cursor = $bounds['from']->copy()->startOfDay();
        $end = $bounds['to']->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $visitors = $perDay[$date] ?? 0;
            $new = $newByDate[$date] ?? 0;

            $series[] = [
                'date' => $date,
                'visitors' => $visitors,
                'new' => $new,
                'returning' => max(0, $visitors - $new),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Internal business "Payment Started" metric: distinct qualifying orders
     * that have at least one payments record created during the period. This
     * deliberately measures qualifying payment activity, not a GA4/Meta
     * add_payment_info event (that event, where recorded, is surfaced
     * separately in the diagnostics section).
     */
    protected function paymentStarted(array $bounds): int
    {
        return $this->qualifying(
            $this->ordersBetween(DB::table('orders as o'), $bounds)
                ->whereExists(function ($q) use ($bounds) {
                    $q->select(DB::raw(1))
                        ->from('payments as p')
                        ->whereColumn('p.order_id', 'o.id')
                        ->whereBetween('p.created_at', [$bounds['from'], $bounds['to']]);
                })
        )->distinct('o.id')->count('o.id');
    }

    /**
     * Traffic acquisition breakdown from page_view events: source, medium,
     * campaign, landing path and device type. Each row reports page views, the
     * distinct sessions that produced them and the distinct visitors behind
     * those sessions. Attribution exists only on page_view, so it is never
     * inferred for other event types.
     */
    protected function traffic(array $bounds): array
    {
        $base = DB::table('analytics_events as e')
            ->where('e.event_type', AnalyticsEvent::PAGE_VIEW)
            ->whereBetween('e.occurred_at', [$bounds['from'], $bounds['to']]);

        $metrics = [
            DB::raw('COUNT(*) as views'),
            DB::raw('COUNT(DISTINCT e.session_id) as sessions'),
            DB::raw('COUNT(DISTINCT e.visitor_id) as visitors'),
        ];

        $map = fn ($row) => [
            'label' => $row->label,
            'views' => (int) $row->views,
            'sessions' => (int) $row->sessions,
            'visitors' => (int) $row->visitors,
        ];

        $grouped = function (string $column) use ($base, $metrics, $map) {
            return (clone $base)
                ->select($column.' as label', ...$metrics)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->groupBy($column)
                ->orderByDesc('views')
                ->limit(10)
                ->get()
                ->map($map)
                ->values()
                ->all();
        };

        return [
            'by_source' => $grouped('e.source'),
            'by_medium' => $grouped('e.medium'),
            'by_campaign' => $grouped('e.campaign'),
            'by_landing' => $grouped('e.landing_path'),
            'by_device' => (clone $base)
                ->select(
                    DB::raw('COALESCE(NULLIF(e.device_type, \'\'), \'unknown\') as label'),
                    ...$metrics
                )
                ->groupBy('device_type')
                ->orderByDesc('views')
                ->get()
                ->map($map)
                ->values()
                ->all(),
        ];
    }

    /**
     * Equal-length previous-period snapshot against which the KPI cards are
     * compared. Monetary and behavioral metrics are both recomputed for the
     * immediately preceding window of the same length.
     */
    protected function compare(array $bounds): array
    {
        $days = $bounds['from']->copy()->startOfDay()->diffInDays($bounds['to']->copy()->startOfDay()) + 1;
        $endPrev = $bounds['from']->copy()->subDay();
        $startPrev = $endPrev->copy()->subDays($days - 1);

        $prev = $this->bounds($startPrev->toDateString(), $endPrev->toDateString());

        return [
            'from' => $startPrev->toDateString(),
            'to' => $endPrev->toDateString(),
            'days' => $days,
            'executive' => $this->executive($prev),
            'events' => $this->events($prev),
            'visitors' => $this->visitors($prev),
            'payment_started' => $this->paymentStarted($prev),
        ];
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
        $capi = $this->capiStatus($bounds);

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

        $recorderByType = DB::table('analytics_events')
            ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type')
            ->toArray();

        $firstEvent = (string) DB::table('analytics_events')->min('occurred_at');
        $lastEvent = (string) DB::table('analytics_events')->max('occurred_at');
        $lastConversion = (string) DB::table('analytics_conversions')->max('created_at');
        $lastServerEventRaw = max($lastEvent, $lastConversion);
        $lastServerEvent = $lastServerEventRaw === '' ? null : Carbon::parse($lastServerEventRaw);

        $recorderTotal = array_sum($recorderByType);

        /*
         * Visitor identity diagnostics.
         *
         * All-time on purpose: the point is to show how much of the stored
         * history carries an identity, which is what bounds the New/Returning
         * classification. Counts only — a raw visitor_id is never surfaced in
         * the dashboard, its payloads, or its HTML.
         */
        $visitorEvents = DB::table('analytics_events')
            ->whereNotNull('visitor_id')
            ->where('visitor_id', '!=', '');

        $visitorWithId = (clone $visitorEvents)->count();
        $visitorDistinct = (clone $visitorEvents)->distinct()->count('visitor_id');
        $visitorWithoutId = max(0, DB::table('analytics_events')->count() - $visitorWithId);
        $visitorFirstEvent = (string) (clone $visitorEvents)->min('occurred_at');
        $visitorLastEvent = (string) (clone $visitorEvents)->max('occurred_at');

        // distinct sessions / visitors that produced a page_view, for cross-check.
        $visitorSessions = $this->visitorPageViews()->distinct()->count('session_id');
        $visitorPageViewed = $this->visitorPageViews()->distinct()->count('visitor_id');

        $visitorStatus = match (true) {
            $visitorWithId === 0 => 'inactive',
            $visitorWithoutId > 0 => 'limited',
            default => 'active',
        };

        $visitorSince = $visitorFirstEvent === '' ? null : Carbon::parse($visitorFirstEvent);

        $visitorCoverageNote = $visitorSince === null
            ? 'No visitor-identified events have been recorded yet, so unique, new and returning visitors cannot be reported.'
            : 'New/Returning visitor classification is based on first-party analytics data available since visitor tracking was enabled ('
                .$visitorSince->toFormattedDateString()
                .'). Visitors active before tracking was enabled cannot be identified and are not counted.';

        return [
            'currency' => self::CURRENCY,
            'gtm_configured' => $gtmConfigured,
            'gtm_container_id' => (string) config('analytics.gtm_container_id', ''),
            'ga4_configured' => $gtmConfigured,
            'pixel_configured' => $pixelConfigured,
            'pixel_masked' => $pixelConfigured ? 'xxxx'.substr($pixelId, -4) : '',
            'capi' => $capi,
            'capi_enabled' => $capi['capi_enabled'],
            'capi_status' => $capi['status'],
            'capi_status_label' => $capi['label'],
            'capi_status_detail' => $capi['detail'],
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
            'recorder_by_type' => $recorderByType,
            'recorder_total' => $recorderTotal,
            'recorder_first_event' => $firstEvent === '' ? null : Carbon::parse($firstEvent),
            'recorder_last_event' => $lastEvent === '' ? null : Carbon::parse($lastEvent),
            'visitor_events_with_id' => $visitorWithId,
            'visitor_events_without_id' => $visitorWithoutId,
            'visitor_unique_all_time' => $visitorDistinct,
            'visitor_page_viewed_unique' => $visitorPageViewed,
            'visitor_sessions' => $visitorSessions,
            'visitor_first_event' => $visitorSince,
            'visitor_last_event' => $visitorLastEvent === '' ? null : Carbon::parse($visitorLastEvent),
            'visitor_status' => $visitorStatus,
            'visitor_coverage_note' => $visitorCoverageNote,
            'last_server_event' => $lastServerEvent,
            'add_payment_info_events' => (int) ($recorderByType[AnalyticsEvent::ADD_PAYMENT_INFO] ?? 0),
            'payment_started' => $this->paymentStarted($bounds),
        ];
    }

    /**
     * Effective Meta CAPI delivery status. Configuration alone is never treated
     * as proof of delivery: the state is derived from the conversion ledger's
     * delivery evidence (meta_state / meta_sent_at in the last 7 days).
     *
     * States: not_configured | active | failed | configured_not_receiving | unknown.
     */
    protected function capiStatus(array $bounds): array
    {
        $configured = app(MetaCapiService::class)->isConfigured();

        $recent = Carbon::now()->subDays(7);

        $allTime = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->count();

        $delivered7d = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->where('meta_state', 'sent')
            ->whereNotNull('meta_sent_at')
            ->where('meta_sent_at', '>', $recent)
            ->count();

        $failed7d = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->where('meta_state', 'failed')
            ->where('created_at', '>', $recent)
            ->count();

        $everDelivered = DB::table('analytics_conversions')
            ->where('event_type', 'purchase')
            ->where('meta_state', 'sent')
            ->whereNotNull('meta_sent_at')
            ->count();

        if (! $configured) {
            $status = 'not_configured';
            $label = 'Not Configured';
            $detail = 'META_CAPI_ENABLED, the Meta pixel ID and the server access token are all required; server-side Purchase delivery is not configured.';
        } elseif ($delivered7d > 0) {
            $status = 'active';
            $label = 'Active / Receiving';
            $detail = 'Receiving server-side Purchase events ('.$delivered7d.' delivered in the last 7 days). Browser Pixel and CAPI share a deterministic event_id, so Meta can deduplicate the two representations.';
        } elseif ($failed7d > 0) {
            $status = 'failed';
            $label = 'Failed';
            $detail = 'Configured, but no delivery succeeded in the last 7 days ('.$failed7d.' failed ledger record(s)). Verify the access token and check the conversion ledger.';
        } elseif ($everDelivered > 0) {
            $status = 'configured_not_receiving';
            $label = 'Configured / Not Receiving';
            $detail = 'Configured with past deliveries, but no event was received in the last 7 days.';
        } elseif ($allTime > 0) {
            $status = 'configured_not_receiving';
            $label = 'Configured / Not Receiving';
            $detail = 'Configured, but conversions are pending and no server-side delivery has been confirmed yet.';
        } else {
            $status = 'unknown';
            $label = 'Unknown';
            $detail = 'Configured, but there is no delivery evidence yet — parsing your data.';
        }

        return [
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
            'capi_enabled' => $configured,
            'delivered_7d' => $delivered7d,
            'failed_7d' => $failed7d,
            'ledger_total_all_time' => $allTime,
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

        switch ($tracking['capi_status'] ?? 'not_configured') {
            case 'active':
                $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API is active', 'detail' => 'Receiving server-side Purchase events; verify token validity and monitor the conversion ledger.'];
                break;
            case 'failed':
                $alerts[] = ['severity' => 'warning', 'title' => 'Meta Conversions API delivery failing', 'detail' => 'No server-side delivery succeeded in the last 7 days; check the access token and the conversion ledger.'];
                break;
            case 'configured_not_receiving':
                $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API configured but not receiving', 'detail' => 'Configured, but there is no recent delivery evidence in the conversion ledger.'];
                break;
            case 'unknown':
                $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API status unknown', 'detail' => 'Configured, but there is no delivery evidence yet — parsing your data.'];
                break;
            default:
                $alerts[] = ['severity' => 'info', 'title' => 'Meta Conversions API is not configured', 'detail' => 'Server-side CAPI delivery is not configured; browser Pixel tracking may still be active.'];
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

    /**
     * SKU-merged product analytics. Order-side numbers (purchases / units /
     * revenue) come from qualifying order-item snapshots; event-side numbers
     * (views / add_to_cart / checkouts) come from analytics_events. Event-only
     * SKUs resolve their product name from the catalogue (falling back to
     * "(unmatched)") so behavioral data is never dropped.
     */
    protected function buildProductEventReport(string $from, string $to, ?string $product, ?int $categoryId, int $page): array
    {
        $bounds = $this->bounds($from, $to);
        $perPage = 15;

        $categorySkus = null;
        if ($categoryId !== null) {
            $categorySkus = DB::table('products as p')
                ->join('product_category as pc', 'pc.product_id', '=', 'p.id')
                ->where('pc.category_id', $categoryId)
                ->whereNotNull('p.sku')
                ->where('p.sku', '!=', '')
                ->distinct()
                ->pluck('p.sku')
                ->all();
        }

        $orderRows = $this->qualifying(
            $this->ordersBetween(DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id'), $bounds)
        )
            ->leftJoin('products as p', fn ($join) => $join->on('p.sku', '=', 'oi.sku'))
            ->select(
                DB::raw('COALESCE(NULLIF(oi.sku, \'\'), \'\') as sku'),
                DB::raw('COALESCE(p.name, oi.product_name) as product_name'),
                DB::raw('SUM(oi.quantity) as units'),
                DB::raw('SUM(oi.total_price) as revenue'),
                DB::raw('COUNT(DISTINCT oi.order_id) as orders'),
                DB::raw('COALESCE(p.status, \'\') as status'),
                DB::raw('COALESCE(p.stock, 0) as stock')
            )
            ->whereNotNull('oi.sku')
            ->where('oi.sku', '!=', '')
            ->groupBy('oi.sku')
            ->groupBy(DB::raw('COALESCE(p.name, oi.product_name)'))
            ->groupBy(DB::raw('COALESCE(p.status, \'\')'))
            ->groupBy(DB::raw('COALESCE(p.stock, 0)'))
            ->get();

        $eventTypes = [
            AnalyticsEvent::VIEW_ITEM => 'views',
            AnalyticsEvent::ADD_TO_CART => 'add_to_carts',
            AnalyticsEvent::BEGIN_CHECKOUT => 'checkouts',
        ];

        $eventBySku = [];
        foreach ($eventTypes as $type => $field) {
            $rows = DB::table('analytics_events')
                ->where('event_type', $type)
                ->whereBetween('occurred_at', [$bounds['from'], $bounds['to']])
                ->whereNotNull('sku')
                ->select('sku', DB::raw('COUNT(*) as count'))
                ->groupBy('sku')
                ->get();

            foreach ($rows as $row) {
                $eventBySku[$row->sku][$field] = (int) $row->count;
            }
        }

        $rows = [];
        foreach ($orderRows as $row) {
            $rows[$row->sku] = [
                'sku' => $row->sku,
                'product_name' => $row->product_name,
                'views' => 0,
                'add_to_carts' => 0,
                'checkouts' => 0,
                'purchases' => (int) $row->orders,
                'units' => (int) $row->units,
                'revenue' => round((float) $row->revenue, 2),
                'status' => $row->status,
                'stock' => (int) $row->stock,
                'views' => 0,
                'add_to_carts' => 0,
                'checkouts' => 0,
            ];
        }

        $eventOnlySkus = array_diff(array_keys($eventBySku), array_keys($rows));
        if ($eventOnlySkus !== []) {
            $names = DB::table('products')->whereIn('sku', $eventOnlySkus)->pluck('name', 'sku')->all();

            foreach ($eventOnlySkus as $sku) {
                $rows[$sku] = [
                    'sku' => $sku,
                    'product_name' => $names[$sku] ?? '(unmatched)',
                    'views' => 0,
                    'add_to_carts' => 0,
                    'checkouts' => 0,
                    'purchases' => 0,
                    'units' => 0,
                    'revenue' => 0.0,
                    'status' => '',
                    'stock' => 0,
                ];
            }
        }

        foreach ($eventBySku as $sku => $counts) {
            foreach ($eventTypes as $field) {
                $rows[$sku][$field] = (int) ($counts[$field] ?? 0);
            }
        }

        if ($categorySkus !== null) {
            $categorySkus = array_flip($categorySkus);
            $rows = array_filter($rows, fn (array $row) => isset($categorySkus[$row['sku']]));
        }

        $search = $product !== null ? mb_strtolower(trim($product)) : '';
        if ($search !== '') {
            $rows = array_filter($rows, fn (array $row) => mb_strpos(mb_strtolower($row['product_name']), $search) !== false);
        }

        foreach ($rows as $sku => &$row) {
            $row['conversion_rate'] = $row['views'] > 0
                ? round(((float) $row['purchases'] / $row['views']) * 100, 1)
                : null;
        }
        unset($row);

        uasort($rows, function (array $a, array $b) {
            return [$b['revenue'], $b['views'], $b['product_name']] <=> [$a['revenue'], $a['views'], $a['product_name']];
        });

        $rows = array_values($rows);
        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $items = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
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

    protected function emptyVisitors(): array
    {
        return [
            'unique' => 0,
            'new' => 0,
            'returning' => 0,
            'sessions' => 0,
            'new_rate' => null,
            'returning_rate' => null,
            'tracked' => false,
            'series' => [],
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
            'capi' => [
                'status' => 'unknown',
                'label' => 'Unknown',
                'detail' => '',
                'capi_enabled' => false,
                'delivered_7d' => 0,
                'failed_7d' => 0,
                'ledger_total_all_time' => 0,
            ],
            'capi_enabled' => false,
            'capi_status' => 'unknown',
            'capi_status_label' => 'Unknown',
            'capi_status_detail' => '',
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
            'recorder_by_type' => [],
            'recorder_total' => 0,
            'recorder_first_event' => null,
            'recorder_last_event' => null,
            'visitor_events_with_id' => 0,
            'visitor_events_without_id' => 0,
            'visitor_unique_all_time' => 0,
            'visitor_page_viewed_unique' => 0,
            'visitor_sessions' => 0,
            'visitor_first_event' => null,
            'visitor_last_event' => null,
            'visitor_status' => 'inactive',
            'visitor_coverage_note' => 'No visitor-identified events have been recorded yet, so unique, new and returning visitors cannot be reported.',
            'last_server_event' => null,
            'add_payment_info_events' => 0,
            'payment_started' => 0,
        ];
    }

    protected function emptyEvents(): array
    {
        return [
            'visitors' => 0,
            'page_views' => 0,
            'product_views' => 0,
            'add_to_carts' => 0,
            'begin_checkouts' => 0,
            'add_payment_infos' => 0,
        ];
    }

    protected function emptyTraffic(): array
    {
        return [
            'by_source' => [],
            'by_medium' => [],
            'by_campaign' => [],
            'by_landing' => [],
            'by_device' => [],
        ];
    }

    protected function emptyCompare(): array
    {
        return [
            'from' => '',
            'to' => '',
            'days' => 0,
            'executive' => $this->emptyExecutive(),
            'events' => $this->emptyEvents(),
            'visitors' => $this->emptyVisitors(),
            'payment_started' => 0,
        ];
    }
}
