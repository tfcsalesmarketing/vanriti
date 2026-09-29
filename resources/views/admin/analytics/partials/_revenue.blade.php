@php
    $hasActivity = (($report['executive']['orders'] ?? 0) > 0) || ((float) ($report['revenue']['revenue'] ?? 0) > 0);
@endphp

@if (($report['revenue']['error'] ?? false))
    <div class="alert alert-warning py-2 small">{{ $report['revenue']['message'] }}</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header">Revenue &amp; Orders by Day</div>
                <div class="card-body">
                    @if ($hasActivity)
                        <canvas id="revenueChart" height="120"></canvas>
                    @else
                        <div class="empty-state py-5"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card table-card">
                <div class="card-header">Order &amp; Payment Mix</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <tbody>
                            <tr><td class="small">All orders (period)</td><td class="text-end fw-semibold">{{ $report['funnel']['all_orders'] }}</td></tr>
                            <tr><td class="small">Qualifying orders</td><td class="text-end fw-semibold">{{ $report['funnel']['qualifying_orders'] }}</td></tr>
                            <tr><td class="small">Paid online</td><td class="text-end fw-semibold">{{ $report['revenue']['paid_orders'] }}</td></tr>
                            <tr><td class="small">Cash on Delivery</td><td class="text-end fw-semibold">{{ $report['revenue']['cod_orders'] }}</td></tr>
                            <tr><td class="small">Cancelled</td><td class="text-end fw-semibold text-muted">{{ $report['revenue']['cancelled_orders'] }}</td></tr>
                            <tr><td class="small">Failed</td><td class="text-end fw-semibold text-muted">{{ $report['revenue']['failed_orders'] }}</td></tr>
                            <tr><td class="small">Refunded / Partially refunded</td><td class="text-end fw-semibold text-muted">{{ $report['revenue']['refunded_orders'] }} / {{ $report['revenue']['partially_refunded_orders'] }}</td></tr>
                            <tr><td class="small">Coupon orders</td><td class="text-end fw-semibold">{{ $report['revenue']['coupon_orders'] }} <span class="text-muted">({{ format_price($report['revenue']['coupon_discount']) }} off)</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header">Database Commerce Funnel</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th class="small">Stage</th><th class="text-end small">Count</th><th style="width:40%"></th></tr>
                    </thead>
                    <tbody>
                        @php
                            $funnelMax = max(1, $report['funnel']['all_orders']);
                            $funnelStages = [
                                ['All orders (period)', $report['funnel']['all_orders'], 'bg-primary'],
                                ['Qualifying orders', $report['funnel']['qualifying_orders'], 'bg-success'],
                                ['Cancelled', $report['funnel']['cancelled'], 'bg-danger'],
                                ['Failed', $report['funnel']['failed'], 'bg-danger'],
                                ['Refunded / partially refunded', $report['funnel']['refunded'].' / '.$report['funnel']['partially_refunded'], 'bg-danger'],
                            ];
                        @endphp
                        @foreach ($funnelStages as [$label, $count, $color])
                            <tr>
                                <td class="small">{{ $label }}</td>
                                <td class="text-end small fw-semibold">{{ $count }}</td>
                                <td>
                                    <div class="progress" style="height:14px;">
                                        <div class="progress-bar {{ $color }}" style="width:{{ round(((float) $count / $funnelMax) * 100, 1) }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="small">Qualifying revenue</td>
                            <td class="text-end small fw-semibold" colspan="2">{{ format_price($report['funnel']['qualifying_revenue']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header">Cart Abandonment Estimate</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Authenticated carts with items (activity)</span><span class="fw-semibold small">{{ $report['funnel']['active_carts'] }}</span></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Active cart owners (period)</span><span class="fw-semibold small">{{ $report['funnel']['active_cart_owners'] }}</span></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted small">Est. owners with no qualifying order</span><span class="fw-semibold small text-danger">{{ $report['funnel']['abandoned_cart_estimate'] }}</span></div>
                <p class="small text-muted mb-0 mt-2">Estimate from cart activity only; guest carts without an account are excluded. Directional, not authoritative.</p>
            </div>
        </div>
    </div>
</div>

@if (($report['products']['error'] ?? false))
    <div class="alert alert-warning py-2 small">{{ $report['products']['message'] }}</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card table-card">
                <div class="card-header">Top Products by Revenue</div>
                <div class="card-body p-0">
                    @if (count($report['products']['top_by_revenue']))
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th class="small">Product</th><th class="text-end small">Units</th><th class="text-end small">Revenue</th></tr></thead>
                            <tbody>
                                @foreach ($report['products']['top_by_revenue'] as $p)
                                    <tr>
                                        <td class="text-truncate small" style="max-width:220px" title="{{ $p->product_name }}">{{ $p->product_name }}</td>
                                        <td class="text-end small text-muted">{{ $p->units }}</td>
                                        <td class="text-end small fw-semibold">{{ format_price($p->revenue) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card table-card">
                <div class="card-header">Top SKUs by Revenue</div>
                <div class="card-body p-0">
                    @if (count($report['products']['top_skus']))
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th class="small">SKU</th><th class="text-end small">Units</th><th class="text-end small">Revenue</th></tr></thead>
                            <tbody>
                                @foreach ($report['products']['top_skus'] as $p)
                                    <tr>
                                        <td class="small"><code>{{ $p->sku }}</code></td>
                                        <td class="text-end small text-muted">{{ $p->units }}</td>
                                        <td class="text-end small fw-semibold">{{ format_price($p->revenue) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

@if (($report['dadi']['error'] ?? false))
    <div class="alert alert-warning py-2 small">{{ $report['dadi']['message'] }}</div>
@else
    <div class="card table-card mb-4">
        <div class="card-header">Dadi Recommendation Analytics</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-dark">{{ $report['dadi']['impressions'] }}</div>
                        <div class="text-muted small">Impressions</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-dark">{{ $report['dadi']['clicks'] }}</div>
                        <div class="text-muted small">Clicks</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-dark">{{ $report['dadi']['add_to_cart'] }}</div>
                        <div class="text-muted small">Add to Cart</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-dark">{{ $report['dadi']['buy_now'] }}</div>
                        <div class="text-muted small">Buy Now</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-success">{{ $report['dadi']['purchases'] }}</div>
                        <div class="text-muted small">Purchases (orders)</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="border rounded p-2 text-center">
                        <div class="stat-value text-success">{{ format_price($report['dadi']['attributed_revenue']) }}</div>
                        <div class="text-muted small">Attributed revenue</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-6 col-md-3">
                    <div class="text-muted small">CTR (clicks / impressions)</div>
                    <div class="fw-semibold">{{ $report['dadi']['ctr'] === null ? '&mdash;' : $report['dadi']['ctr'].'%' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">CVR (purchases / clicks)</div>
                    <div class="fw-semibold">{{ $report['dadi']['cvr'] === null ? '&mdash;' : $report['dadi']['cvr'].'%' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Conversations</div>
                    <div class="fw-semibold">{{ $report['dadi']['conversations']['total'] }}
                        <span class="text-muted small">({{ $report['dadi']['conversations']['guest'] }} guest, {{ $report['dadi']['conversations']['authenticated'] }} registered)</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted small">Conversations by status</div>
                    <div class="fw-semibold small">
                        @forelse ($report['dadi']['conversations']['by_status'] as $status => $count)
                            <span class="me-2">{{ $status }}: {{ $count }}</span>
                        @empty
                            <span class="text-muted">&mdash;</span>
                        @endforelse
                    </div>
                </div>
            </div>
            @if ($report['dadi']['conversations']['by_locale'])
            <div class="text-muted small mt-2">Top locales:
                @foreach ($report['dadi']['conversations']['by_locale'] as $locale => $count)
                    <span class="me-2">{{ $locale }}: {{ $count }}</span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header">Most Recommended Products</div>
                <div class="card-body p-0">
                    @if (count($report['dadi']['top_impressions']))
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($report['dadi']['top_impressions'] as $r)
                                    <tr><td class="text-truncate small" style="max-width:260px" title="{{ $r->product_name }}">{{ $r->product_name }}</td><td class="text-end small text-muted">{{ $r->count }} impressions</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header">Top Converting Recommended Products</div>
                <div class="card-body p-0">
                    @if (count($report['dadi']['top_converting']))
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach ($report['dadi']['top_converting'] as $r)
                                    <tr><td class="text-truncate small" style="max-width:260px" title="{{ $r->product_name }}">{{ $r->product_name }}</td><td class="text-end small text-muted">{{ $r->count }} purchases</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif