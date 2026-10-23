@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Row 1: Executive KPI Metrics --}}
<div class="row g-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Total Revenue</span>
                <span class="stat-icon" style="background:#ECFDF5; color:#047857;"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="stat-value">{{ format_price($stats['total_sales']) }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <i class="bi bi-shield-check text-success"></i>
                <span>Lifetime store earnings</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Today's Sales</span>
                <span class="stat-icon" style="background:#EEF4FF; color:#3538CD;"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <div class="stat-value">{{ format_price($stats['today_sales']) }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1.5" style="font-size:0.65rem;">LIVE</span>
                <span>Active 24h performance</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Monthly Sales</span>
                <span class="stat-icon" style="background:#F0FDF4; color:#15803D;"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="stat-value">{{ format_price($stats['monthly_sales']) }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <i class="bi bi-clock-history text-muted"></i>
                <span>Current calendar month</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Total Orders</span>
                <span class="stat-icon" style="background:#FFFBEB; color:#B45309;"><i class="bi bi-bag-check"></i></span>
            </div>
            <div class="stat-value">{{ number_format($stats['total_orders']) }}</div>
            <div class="mt-1">
                <a href="{{ route('admin.orders.index') }}" class="text-decoration-none small fw-semibold text-primary d-inline-flex align-items-center gap-1">
                    <span>Manage all orders</span><i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Operational Fulfillment Pipeline & Store Health --}}
<div class="row g-3 mt-1">
    {{-- Fulfillment Pipeline Queue --}}
    <div class="col-12 col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-truck text-muted"></i>
                    <span>Order Fulfillment Queue</span>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="small text-decoration-none text-muted">View Pipeline &rarr;</a>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-6 col-sm-4 col-md">
                        <a href="{{ route('admin.orders.index', ['tab' => 'pending']) }}" class="text-decoration-none d-block">
                            <div class="p-2.5 rounded-3 border bg-light-subtle text-center hover-lift">
                                <div class="text-muted small" style="font-size:0.75rem;">Pending</div>
                                <div class="fs-5 fw-bold text-warning">{{ $stats['pending_orders'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md">
                        <a href="{{ route('admin.orders.index', ['order_status' => 'processing']) }}" class="text-decoration-none d-block">
                            <div class="p-2.5 rounded-3 border bg-light-subtle text-center hover-lift">
                                <div class="text-muted small" style="font-size:0.75rem;">Processing</div>
                                <div class="fs-5 fw-bold text-info">{{ $stats['processing_orders'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md">
                        <a href="{{ route('admin.orders.index', ['tab' => 'shipped']) }}" class="text-decoration-none d-block">
                            <div class="p-2.5 rounded-3 border bg-light-subtle text-center hover-lift">
                                <div class="text-muted small" style="font-size:0.75rem;">Shipped</div>
                                <div class="fs-5 fw-bold text-primary">{{ $stats['shipped_orders'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md">
                        <a href="{{ route('admin.orders.index', ['tab' => 'delivered']) }}" class="text-decoration-none d-block">
                            <div class="p-2.5 rounded-3 border bg-light-subtle text-center hover-lift">
                                <div class="text-muted small" style="font-size:0.75rem;">Delivered</div>
                                <div class="fs-5 fw-bold text-success">{{ $stats['delivered_orders'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md">
                        <a href="{{ route('admin.orders.index', ['tab' => 'cancelled']) }}" class="text-decoration-none d-block">
                            <div class="p-2.5 rounded-3 border bg-light-subtle text-center hover-lift">
                                <div class="text-muted small" style="font-size:0.75rem;">Cancelled</div>
                                <div class="fs-5 fw-bold text-danger">{{ $stats['cancelled_orders'] }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Store Inventory & Customer Radar --}}
    <div class="col-12 col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2 text-muted"></i>
                    <span>Store Radar</span>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-4">
                        <a href="{{ route('admin.customers.index') }}" class="text-decoration-none d-block">
                            <div class="p-2 rounded-3 border bg-light-subtle text-center">
                                <div class="text-muted small" style="font-size:0.72rem;">Customers</div>
                                <div class="fs-5 fw-bold text-dark">{{ $stats['customers'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="{{ route('admin.products.index') }}" class="text-decoration-none d-block">
                            <div class="p-2 rounded-3 border bg-light-subtle text-center">
                                <div class="text-muted small" style="font-size:0.72rem;">Products</div>
                                <div class="fs-5 fw-bold text-dark">{{ $stats['products'] }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="{{ route('admin.inventory.index') }}" class="text-decoration-none d-block">
                            <div class="p-2 rounded-3 border bg-light-subtle text-center">
                                <div class="text-muted small" style="font-size:0.72rem;">Low Stock</div>
                                <div class="fs-5 fw-bold {{ $stats['low_stock'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $stats['low_stock'] }}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Sales Trend & Top Selling Products --}}
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <span class="fw-bold">Revenue & Order Volume</span>
                    <small class="text-muted d-block" style="font-size:0.75rem;">Last 14 days performance trend</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Sales</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Orders</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-bold">Top Products</span>
                <a href="{{ route('admin.products.index') }}" class="small text-decoration-none text-muted">All Products &rarr;</a>
            </div>
            <div class="card-body p-0">
                @if (count($charts['top_products']))
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width: 32px;">#</th>
                                <th>Product</th>
                                <th class="text-center">Units</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($charts['top_products'] as $idx => $product)
                                <tr>
                                    <td class="text-muted fw-bold small text-center">{{ $idx + 1 }}</td>
                                    <td class="text-truncate small fw-semibold" style="max-width: 140px;" title="{{ $product->product_name }}">
                                        {{ $product->product_name }}
                                    </td>
                                    <td class="text-center small"><span class="badge bg-light text-dark border">{{ $product->qty }}</span></td>
                                    <td class="text-end small fw-semibold text-dark">{{ format_price($product->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state py-4">
                        <div class="empty-state-icon" style="width:48px; height:48px; font-size:1.4rem;"><i class="bi bi-bag"></i></div>
                        <div class="empty-state-title" style="font-size:0.95rem;">No Sales Data Yet</div>
                        <p class="empty-state-desc" style="font-size:0.8rem;">Top performing products will appear here once orders are placed.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce }}">
(function () {
    const saleData = @json($charts['sales_by_day']);
    const orderData = @json($charts['orders_by_day']);

    const dates = [];
    const today = new Date();
    for (let i = 13; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(today.getDate() - i);
        dates.push(d.toISOString().slice(0, 10));
    }

    const sales = dates.map(d => Math.round((saleData[d] || 0) * 100) / 100);
    const orders = dates.map(d => orderData[d] || 0);

    const ctx = document.getElementById('salesChart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates.map(d => {
                    const p = d.split('-');
                    return p[2] + '/' + p[1];
                }),
                datasets: [
                    {
                        label: 'Sales (₹)',
                        data: sales,
                        backgroundColor: 'rgba(30, 54, 32, 0.78)',
                        hoverBackgroundColor: '#1E3620',
                        borderRadius: 6,
                        yAxisID: 'y',
                        order: 1
                    },
                    {
                        label: 'Orders Count',
                        data: orders,
                        type: 'line',
                        borderColor: '#6F8736',
                        backgroundColor: 'rgba(111, 135, 54, 0.12)',
                        pointBackgroundColor: '#6F8736',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.35,
                        yAxisID: 'y1',
                        order: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#19251B',
                        titleColor: '#FFFFFF',
                        bodyColor: '#E6ECE8',
                        padding: 10,
                        cornerRadius: 8,
                        boxPadding: 4
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: '#627467' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F1F4F1' },
                        ticks: { font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: '#627467' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1, font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }, color: '#627467' }
                    }
                }
            }
        });
    }
})();
</script>
@endpush