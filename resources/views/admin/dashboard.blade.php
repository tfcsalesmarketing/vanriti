@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="text-muted small">Total Sales</div>
                    <div class="stat-value text-dark">{{ format_price($stats['total_sales']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-graph-up-arrow"></i></span>
                <div>
                    <div class="text-muted small">Today's Sales</div>
                    <div class="stat-value text-dark">{{ format_price($stats['today_sales']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-month"></i></span>
                <div>
                    <div class="text-muted small">Monthly Sales</div>
                    <div class="stat-value text-dark">{{ format_price($stats['monthly_sales']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <span class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-bag-check"></i></span>
                <div>
                    <div class="text-muted small">Total Orders</div>
                    <div class="stat-value text-dark">{{ $stats['total_orders'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Pending</div>
            <div class="stat-value text-warning">{{ $stats['pending_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Processing</div>
            <div class="stat-value text-info">{{ $stats['processing_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Shipped</div>
            <div class="stat-value text-primary">{{ $stats['shipped_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Delivered</div>
            <div class="stat-value text-success">{{ $stats['delivered_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Cancelled</div>
            <div class="stat-value text-danger">{{ $stats['cancelled_orders'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Customers</div>
            <div class="stat-value text-dark">{{ $stats['customers'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Products</div>
            <div class="stat-value text-dark">{{ $stats['products'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card text-center">
            <div class="text-muted small">Low Stock</div>
            <div class="stat-value text-danger">{{ $stats['low_stock'] }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header">Sales & Orders — Last 14 Days</div>
            <div class="card-body">
                <canvas id="salesChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header">Top Products</div>
            <div class="card-body p-0">
                @if (count($charts['top_products']))
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($charts['top_products'] as $product)
                                <tr>
                                    <td class="text-truncate small">{{ $product->product_name }}</td>
                                    <td class="text-end small text-muted">&times;{{ $product->qty }}</td>
                                    <td class="text-end small fw-semibold">{{ format_price($product->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data yet</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
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
                labels: dates.map(d => d.slice(5)),
                datasets: [
                    {
                        label: 'Sales',
                        data: sales,
                        backgroundColor: 'rgba(111, 135, 54, 0.7)',
                        borderColor: '#6F8736',
                        borderWidth: 1,
                        yAxisID: 'y',
                        order: 1
                    },
                    {
                        label: 'Orders',
                        data: orders,
                        type: 'line',
                        borderColor: '#263D25',
                        backgroundColor: 'rgba(38, 61, 37, 0.1)',
                        tension: 0.35,
                        yAxisID: 'y1',
                        order: 0
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0ede6' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }
})();
</script>
@endpush