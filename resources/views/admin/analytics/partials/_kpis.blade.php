@php
    $events = $report['events'] ?? [];
    $compare = $report['compare'] ?? [];
    $prevEvents = $compare['events'] ?? [];

    // Persistent anonymous visitor metrics (report.visitors). Deliberately
    // separate from $events['visitors'], which is the pre-existing distinct
    // *session* count and stays the conversion-rate denominator.
    $visitorReport = $report['visitors'] ?? [];
    $prevVisitorReport = $compare['visitors'] ?? [];

    $visitors = (int) ($events['visitors'] ?? 0);
    $purchases = (int) ($report['executive']['orders'] ?? 0);
    $prevVisitors = (int) ($prevEvents['visitors'] ?? 0);
    $prevPurchases = (int) ($compare['executive']['orders'] ?? 0);
    $cr = $visitors > 0 ? ($purchases / $visitors) * 100 : null;
    $prevCr = $prevVisitors > 0 ? ($prevPurchases / $prevVisitors) * 100 : null;

    $coverageNote = $report['tracking']['visitor_coverage_note'] ?? '';

    $pct = function ($current, $previous) {
        $cur = (float) $current;
        $prev = (float) $previous;
        if ($prev == 0.0) {
            return $cur == 0.0
                ? ['text' => '—', 'class' => 'text-muted']
                : ['text' => 'new', 'class' => 'text-success'];
        }
        $raw = (($cur - $prev) / abs($prev)) * 100;
        $class = $raw > 0 ? 'text-success' : ($raw < 0 ? 'text-danger' : 'text-muted');
        return ['text' => ($raw > 0 ? '+' : '').number_format($raw, 1).'%', 'class' => $class];
    };

    $kpis = [
        [
            'label' => 'Website Visitors',
            'value' => number_format((int) ($visitorReport['unique'] ?? 0)),
            'current' => (int) ($visitorReport['unique'] ?? 0),
            'prev' => (int) ($prevVisitorReport['unique'] ?? 0),
            'icon' => 'bi-people-fill',
            'tone' => 'primary',
            'title' => 'Distinct anonymous visitors (first-party visitor_id) with a page_view in the period. One visitor is counted once per period regardless of session or page count. '.$coverageNote,
        ],
        [
            'label' => 'New Visitors',
            'value' => number_format((int) ($visitorReport['new'] ?? 0)),
            'current' => (int) ($visitorReport['new'] ?? 0),
            'prev' => (int) ($prevVisitorReport['new'] ?? 0),
            'icon' => 'bi-person-plus-fill',
            'tone' => 'primary',
            'title' => 'Visitors whose first-ever recorded page_view falls inside the period. '.$coverageNote,
        ],
        [
            'label' => 'Returning Visitors',
            'value' => number_format((int) ($visitorReport['returning'] ?? 0)),
            'current' => (int) ($visitorReport['returning'] ?? 0),
            'prev' => (int) ($prevVisitorReport['returning'] ?? 0),
            'icon' => 'bi-person-check-fill',
            'tone' => 'primary',
            'title' => 'Visitors active in the period whose first-ever recorded page_view was before the period started. '.$coverageNote,
        ],
        [
            'label' => 'Sessions',
            'value' => number_format((int) ($visitorReport['sessions'] ?? 0)),
            'current' => (int) ($visitorReport['sessions'] ?? 0),
            'prev' => (int) ($prevVisitorReport['sessions'] ?? 0),
            'icon' => 'bi-clock-history',
            'tone' => 'primary',
            'title' => 'Distinct browser sessions that produced a visitor-identified page_view in the period. Sessions are shorter-lived than visitors, so Sessions is normally higher than Website Visitors.',
        ],
        [
            'label' => 'Unique Sessions (all)',
            'value' => number_format($visitors),
            'current' => $visitors,
            'prev' => $prevVisitors,
            'icon' => 'bi-hash',
            'tone' => 'info',
            'title' => 'Distinct hashed session IDs observed via page_view in the period, including sessions recorded before visitor tracking existed. Historical measure - use Website Visitors above for visitor-based reporting. Bot traffic is excluded.',
        ],
        [
            'label' => 'Page Views',
            'value' => number_format((int) ($events['page_views'] ?? 0)),
            'current' => (int) ($events['page_views'] ?? 0),
            'prev' => (int) ($prevEvents['page_views'] ?? 0),
            'icon' => 'bi-eye-fill',
            'tone' => 'info',
            'title' => 'page_view events recorded on the storefront.',
        ],
        [
            'label' => 'Product Views',
            'value' => number_format((int) ($events['product_views'] ?? 0)),
            'current' => (int) ($events['product_views'] ?? 0),
            'prev' => (int) ($prevEvents['product_views'] ?? 0),
            'icon' => 'bi-bag-heart-fill',
            'tone' => 'info',
            'title' => 'view_item events recorded on product detail pages.',
        ],
        [
            'label' => 'Add to Cart',
            'value' => number_format((int) ($events['add_to_carts'] ?? 0)),
            'current' => (int) ($events['add_to_carts'] ?? 0),
            'prev' => (int) ($prevEvents['add_to_carts'] ?? 0),
            'icon' => 'bi-cart-plus-fill',
            'tone' => 'warning',
            'title' => 'add_to_cart events recorded after successful cart additions.',
        ],
        [
            'label' => 'Initiate Checkout',
            'value' => number_format((int) ($events['begin_checkouts'] ?? 0)),
            'current' => (int) ($events['begin_checkouts'] ?? 0),
            'prev' => (int) ($prevEvents['begin_checkouts'] ?? 0),
            'icon' => 'bi-cart-check-fill',
            'tone' => 'warning',
            'title' => 'begin_checkout events recorded when the checkout page is rendered.',
        ],
        [
            'label' => 'Payment Started',
            'value' => number_format((int) ($report['payment_started']['count'] ?? 0)),
            'current' => (int) ($report['payment_started']['count'] ?? 0),
            'prev' => (int) ($compare['payment_started'] ?? 0),
            'icon' => 'bi-credit-card-fill',
            'tone' => 'warning',
            'title' => 'Distinct qualifying orders with at least one payments-ledger record in the period. Internal business metric - not the browser add_payment_info event.',
        ],
        [
            'label' => 'Purchases',
            'value' => number_format($purchases),
            'current' => $purchases,
            'prev' => $prevPurchases,
            'icon' => 'bi-bag-check-fill',
            'tone' => 'success',
            'title' => 'Qualifying orders created in the period (Laravel DB is authoritative for orders).',
        ],
        [
            'label' => 'Revenue',
            'value' => format_price((float) ($report['executive']['revenue'] ?? 0)),
            'current' => (float) ($report['executive']['revenue'] ?? 0),
            'prev' => (float) ($compare['executive']['revenue'] ?? 0),
            'icon' => 'bi-cash-stack',
            'tone' => 'success',
            'title' => 'Qualifying revenue from order item snapshots (excludes cancelled, failed, refunded and unpaid online orders).',
        ],
        [
            'label' => 'Conversion Rate',
            'value' => $cr === null ? '—' : number_format($cr, 2).'%',
            'current' => $cr,
            'prev' => $prevCr,
            'icon' => 'bi-graph-up-arrow',
            'tone' => 'success',
            'title' => 'Qualifying purchases ÷ Unique Sessions. Denominator is the session-based historical measure, not Website Visitors, so this KPI is unchanged from previous reporting.',
        ],
        [
            'label' => 'Avg Order Value',
            'value' => format_price((float) ($report['executive']['avg_order_value'] ?? 0)),
            'current' => (float) ($report['executive']['avg_order_value'] ?? 0),
            'prev' => (float) ($compare['executive']['avg_order_value'] ?? 0),
            'icon' => 'bi-receipt',
            'tone' => 'primary',
            'title' => 'Qualifying revenue ÷ qualifying orders.',
        ],
    ];
@endphp

<div class="row g-3 mb-4">
    @foreach ($kpis as $kpi)
        @php $delta = $pct($kpi['current'] ?? 0, $kpi['prev'] ?? 0); @endphp
        <div class="col-6 col-md-3 col-xxl-2">
            <div class="card stat-card h-100" title="{{ $kpi['title'] }}">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon bg-{{ $kpi['tone'] }} bg-opacity-10 text-{{ $kpi['tone'] }}"><i class="bi {{ $kpi['icon'] }}"></i></span>
                        <span class="text-muted small fw-medium text-truncate">{{ $kpi['label'] }}</span>
                    </div>
                    <div class="fs-4 fw-bold">{{ $kpi['value'] }}</div>
                    <div class="small">
                        <span class="{{ $delta['class'] }} fw-semibold">{{ $delta['text'] }}</span>
                        <span class="text-muted">vs prev</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>