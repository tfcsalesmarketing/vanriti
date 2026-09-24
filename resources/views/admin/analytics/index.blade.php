@extends('admin.layouts.app')

@section('title', 'Analytics Command Center')

@section('content')
@php
    $hasActivity = (($report['executive']['orders'] ?? 0) > 0) || ((float) ($report['revenue']['revenue'] ?? 0) > 0);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h5 class="fw-bold mb-1">Analytics Command Center</h5>
        <p class="text-muted small mb-0">
            Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong> ({{ $report['period']['days'] }} days).
            Aggregates are cached for up to 5 minutes &middot; generated {{ $report['period']['generated_at'] }} ({{ config('app.timezone') }}).
        </p>
    </div>

    <form method="GET" action="{{ route('admin.analytics') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div>
            <label class="form-label small text-muted mb-1" for="preset">Period</label>
            <select name="preset" id="preset" class="form-select form-select-sm" style="min-width:150px;">
                @foreach ([
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7' => 'Last 7 Days',
                    'last_30' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'custom' => 'Custom Range',
                ] as $value => $label)
                    <option value="{{ $value }}" {{ $preset === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div id="customRangeWrap" class="{{ $preset === 'custom' ? '' : 'd-none' }}">
            <label class="form-label small text-muted mb-1" for="from">From</label>
            <input type="date" name="from" id="from" class="form-control form-control-sm" value="{{ request('from', $from) }}">
        </div>
        <div id="customRangeWrapTo" class="{{ $preset === 'custom' ? '' : 'd-none' }}">
            <label class="form-label small text-muted mb-1" for="to">To</label>
            <input type="date" name="to" id="to" class="form-control form-control-sm" value="{{ request('to', $to) }}">
        </div>
        <input type="hidden" name="page" value="1">
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Recomputes with the current filters (aggregates cached 5 minutes)">Refresh</button>
    </form>
</div>

@if ($errors->any())
    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
@endif

@if ($report['alerts'])
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header">Alerts &amp; Warnings</div>
            <div class="card-body py-3">
                @foreach ($report['alerts'] as $alert)
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <span class="badge {{ match ($alert['severity']) {
                            'critical' => 'text-bg-danger',
                            'warning' => 'text-bg-warning',
                            default => 'text-bg-info',
                        } }}">{{ strtoupper($alert['severity']) }}</span>
                        <div>
                            <div class="fw-semibold small">{{ $alert['title'] }}</div>
                            <div class="text-muted small">{{ $alert['detail'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-3">
    @if (($report['executive']['error'] ?? false))
        <div class="col-12"><div class="alert alert-warning py-2 small">{{ $report['executive']['message'] }}</div></div>
    @else
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="text-muted small">Revenue (qualifying)</div>
                    <div class="stat-value text-dark">{{ format_price($report['executive']['revenue']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-bag-check"></i></span>
                <div>
                    <div class="text-muted small">Orders (qualifying)</div>
                    <div class="stat-value text-dark">{{ $report['executive']['orders'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up-arrow"></i></span>
                <div>
                    <div class="text-muted small">Avg Order Value</div>
                    <div class="stat-value text-dark">{{ format_price($report['executive']['avg_order_value']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-people"></i></span>
                <div>
                    <div class="text-muted small">Customers</div>
                    <div class="stat-value text-dark">{{ $report['executive']['customers'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-arrow-counterclockwise"></i></span>
                <div>
                    <div class="text-muted small">Completed Refunds</div>
                    <div class="stat-value text-dark">{{ format_price($report['executive']['refund_value']) }}</div>
                    <div class="text-muted small">{{ $report['executive']['refund_count'] }} refund(s)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-radar"></i></span>
                <div>
                    <div class="text-muted small">Tracking Coverage</div>
                    <div class="stat-value text-dark">
                        {{ $report['executive']['coverage_percent'] === null ? '—' : $report['executive']['coverage_percent'].'%' }}
                    </div>
                    <div class="text-muted small">{{ $report['executive']['tracked_orders'] }}/{{ $report['executive']['orders'] }} tracked</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row g-3 mt-1">
    @if (($report['revenue']['error'] ?? false))
        <div class="col-12"><div class="alert alert-warning py-2 small">{{ $report['revenue']['message'] }}</div></div>
    @else
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
    @endif
</div>

<div class="row g-3 mt-1">
    @if (($report['products']['error'] ?? false))
        <div class="col-12"><div class="alert alert-warning py-2 small">{{ $report['products']['message'] }}</div></div>
    @else
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
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header">Product Report <span class="text-muted fw-normal">(revenue from immutable order snapshots; stock/status are current)</span></div>
            <div class="card-body p-0">
                @if (count($report['products']['report']['items']))
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">Product</th>
                                    <th class="small">SKU</th>
                                    <th class="text-end small">Units</th>
                                    <th class="text-end small">Revenue</th>
                                    <th class="text-end small">Orders</th>
                                    <th class="text-end small">Stock</th>
                                    <th class="text-end small">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['products']['report']['items'] as $item)
                                    <tr>
                                        <td class="small text-truncate" style="max-width:240px" title="{{ $item['product_name'] }}">{{ $item['product_name'] }}</td>
                                        <td class="small"><code>{{ $item['sku'] ?: '&mdash;' }}</code></td>
                                        <td class="text-end small">{{ $item['units'] }}</td>
                                        <td class="text-end small fw-semibold">{{ format_price($item['revenue']) }}</td>
                                        <td class="text-end small">{{ $item['orders'] }}</td>
                                        <td class="text-end small">{{ $item['stock'] ?? '&mdash;' }}</td>
                                        <td class="text-end small">
                                            @if ($item['status'])
                                                <span class="badge {{ $item['status'] === 'active' ? 'text-bg-success' : ($item['status'] === 'draft' ? 'text-bg-secondary' : 'text-bg-danger') }}">{{ $item['status'] }}</span>
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($report['products']['report']['last_page'] > 1)
                    <div class="d-flex justify-content-between align-items-center p-2 small border-top">
                        <span class="text-muted">Page {{ $report['products']['report']['current_page'] }} of {{ $report['products']['report']['last_page'] }} ({{ $report['products']['report']['total'] }} products)</span>
                        <span>
                            @if ($report['products']['report']['current_page'] > 1)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.analytics', array_merge(request()->query(), ['page' => $report['products']['report']['current_page'] - 1])) }}">&larr; Prev</a>
                            @endif
                            @if ($report['products']['report']['current_page'] < $report['products']['report']['last_page'])
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.analytics', array_merge(request()->query(), ['page' => $report['products']['report']['current_page'] + 1])) }}">Next &rarr;</a>
                            @endif
                        </span>
                    </div>
                    @endif
                @else
                    <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row g-3 mt-1">
    @if (($report['dadi']['error'] ?? false))
        <div class="col-12"><div class="alert alert-warning py-2 small">{{ $report['dadi']['message'] }}</div></div>
    @else
    <div class="col-12">
        <div class="card table-card">
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
    </div>
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
    @endif
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card table-card">
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
        <div class="card table-card">
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

<div class="row g-3 mt-1 mb-3">
    @if (($report['tracking']['error'] ?? false))
        <div class="col-12"><div class="alert alert-warning py-2 small">{{ $report['tracking']['message'] }}</div></div>
    @else
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header">Tracking &amp; Data Diagnostics</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th class="small">Check</th><th class="small">Status</th><th class="small">Detail</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="small">Currency</td>
                                <td><span class="badge text-bg-success">CONFIGURED</span></td>
                                <td class="small text-muted">{{ $report['tracking']['currency'] }} ({{ currency() }})</td>
                            </tr>
                            <tr>
                                <td class="small">GA4 / GTM browser tracking</td>
                                <td>
                                    @if ($report['tracking']['gtm_configured'])
                                        <span class="badge text-bg-success">CONFIGURED</span>
                                    @else
                                        <span class="badge text-bg-secondary">NOT CONFIGURED</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['gtm_configured'] ? 'GTM '.$report['tracking']['gtm_container_id'] : 'No GTM container ID configured; GA4/GTM may be inactive.' }}</td>
                            </tr>
                            <tr>
                                <td class="small">Meta Pixel</td>
                                <td>
                                    @if ($report['tracking']['pixel_configured'])
                                        <span class="badge text-bg-success">CONFIGURED</span>
                                    @else
                                        <span class="badge text-bg-danger">MISSING</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['pixel_configured'] ? 'Pixel ID '.$report['tracking']['pixel_masked'] : 'Not set (Settings &rarr; SEO).' }}</td>
                            </tr>
                            <tr>
                                <td class="small">Meta Conversions API (CAPI)</td>
                                <td>
                                    @if ($report['tracking']['capi_enabled'])
                                        <span class="badge text-bg-success">ENABLED</span>
                                    @else
                                        <span class="badge text-bg-secondary">DISABLED</span>
                                    @endif
                                </td>
                                <td class="small text-muted">Server-side delivery disabled in this Phase 0 build. Expected until consent/privacy readiness is complete.</td>
                            </tr>
                            <tr>
                                <td class="small">Conversion ledger (internal record)</td>
                                <td><span class="badge text-bg-info">INFO</span></td>
                                <td class="small text-muted">
                                    {{ $report['tracking']['ledger_total'] }} purchase record(s) in period
                                    @foreach ($report['tracking']['ledger_by_state'] as $state => $cnt)
                                        &middot; {{ $state === 'null' ? 'null' : $state }}: {{ $cnt }}
                                    @endforeach
                                    &middot; failed (7 day): {{ $report['tracking']['ledger_failures_7d'] }}
                                </td>
                            </tr>
                            <tr>
                                <td class="small">Tracking coverage (qualifying)</td>
                                <td>
                                    @if (($report['tracking']['tracked_orders'] ?? 0) + ($report['tracking']['untracked_orders'] ?? 0) === 0)
                                        <span class="badge text-bg-secondary">N/A</span>
                                    @elseif ($report['tracking']['coverage_percent'] === 100)
                                        <span class="badge text-bg-success">FULL</span>
                                    @else
                                        <span class="badge text-bg-warning">PARTIAL</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['tracked_orders'] }} tracked / {{ $report['tracking']['untracked_orders'] }} untracked qualifying order(s) this period.</td>
                            </tr>
                            <tr>
                                <td class="small">Products missing SKU</td>
                                <td>
                                    @if ($report['tracking']['products_without_sku'] > 0)
                                        <span class="badge text-bg-warning">WARNING</span>
                                    @else
                                        <span class="badge text-bg-success">OK</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['products_without_sku'] }} product(s) without a SKU.</td>
                            </tr>
                            <tr>
                                <td class="small">Duplicate SKUs</td>
                                <td>
                                    @if ($report['tracking']['duplicate_skus'] > 0)
                                        <span class="badge text-bg-danger">CRITICAL</span>
                                    @else
                                        <span class="badge text-bg-success">OK</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['duplicate_skus'] }} duplicated SKU(s).</td>
                            </tr>
                            <tr>
                                <td class="small">Order items missing SKU (period)</td>
                                <td>
                                    @if ($report['tracking']['orders_missing_item_sku'] > 0)
                                        <span class="badge text-bg-warning">WARNING</span>
                                    @else
                                        <span class="badge text-bg-success">OK</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['orders_missing_item_sku'] }} qualifying item line(s) without a SKU.</td>
                            </tr>
                            <tr>
                                <td class="small">Active stock health</td>
                                <td>
                                    @if ($report['tracking']['active_out_of_stock'] > 0)
                                        <span class="badge text-bg-warning">WARNING</span>
                                    @else
                                        <span class="badge text-bg-success">OK</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $report['tracking']['active_out_of_stock'] }} out of stock, {{ $report['tracking']['active_low_stock'] }} low on stock (active products).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@php
    $excludedNote = 'Qualifying = order not cancelled/failed AND payment not refunded/partially-refunded AND (payment_method = COD OR payment_status = paid).';
@endphp
<p class="text-muted small mb-3">{{ $excludedNote }} Ledger counts are internal delivery records, not external confirmations. No customer PII is shown on this page.</p>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const preset = document.getElementById('preset');
    const customWrap = document.getElementById('customRangeWrap');
    const customWrapTo = document.getElementById('customRangeWrapTo');
    if (preset) {
        customWrap.classList.toggle('d-none', preset.value !== 'custom');
        customWrapTo.classList.toggle('d-none', preset.value !== 'custom');
        preset.addEventListener('change', function () {
            customWrap.classList.toggle('d-none', preset.value !== 'custom');
            customWrapTo.classList.toggle('d-none', preset.value !== 'custom');
        });
    }

    const series = @json($report['revenue']['series']);
    const dates = series.map(function (row) { return row.date; });
    const revenue = series.map(function (row) { return Math.round(row.revenue * 100) / 100; });
    const orders = series.map(function (row) { return row.orders; });

    const canvas = document.getElementById('revenueChart');
    if (canvas && window.Chart) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: dates.map(function (d) { return d.slice(5); }),
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenue,
                        backgroundColor: 'rgba(111, 135, 54, 0.7)',
                        borderColor: '#6F8736',
                        borderWidth: 1,
                        yAxisID: 'y0'
                    },
                    {
                        label: 'Orders',
                        data: orders,
                        type: 'line',
                        borderColor: '#263D25',
                        backgroundColor: 'rgba(38, 61, 37, 0.1)',
                        tension: 0.35,
                        yAxisID: 'y1',
                        pointRadius: dates.length > 60 ? 0 : 3
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.dataset.label === 'Revenue') {
                                    return 'Revenue: ' + ctx.parsed.y.toLocaleString('en-IN');
                                }
                                return ctx.dataset.label + ': ' + ctx.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y0: { beginAtZero: true, position: 'left', grid: { color: '#f0ede6' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }
})();
</script>
@endpush