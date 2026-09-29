@extends('admin.layouts.app')

@section('title', 'Analytics Command Center')

@section('content')
@php
    $sections = ['overview', 'traffic', 'product', 'revenue', 'diagnostics'];
    $currentSection = in_array((string) request()->query('section', 'overview'), $sections, true)
        ? (string) request()->query('section', 'overview')
        : 'overview';
@endphp

@include('admin.analytics.partials._toolbar')

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

<div id="analytics-overview" class="{{ $currentSection === 'overview' ? '' : 'd-none' }}">
    @include('admin.analytics.partials._kpis')
    @include('admin.analytics.partials._visitors')
    @include('admin.analytics.partials._funnel')
</div>

<div id="analytics-traffic" class="{{ $currentSection === 'traffic' ? '' : 'd-none' }}">
    @include('admin.analytics.partials._traffic')
</div>

<div id="analytics-product" class="{{ $currentSection === 'product' ? '' : 'd-none' }}">
    @include('admin.analytics.partials._product')
</div>

<div id="analytics-revenue" class="{{ $currentSection === 'revenue' ? '' : 'd-none' }}">
    @include('admin.analytics.partials._revenue')
</div>

<div id="analytics-diagnostics" class="{{ $currentSection === 'diagnostics' ? '' : 'd-none' }}">
    @include('admin.analytics.partials._diagnostics')
</div>

@php
    $excludedNote = 'Qualifying = order not cancelled/failed AND payment not refunded/partially-refunded AND (payment_method = COD OR payment_status = paid).';
@endphp
<p class="text-muted small mb-3">{{ $excludedNote }} An aggregate event funnel is directional, not a same-user journey. Ledger counts are internal delivery records, not external confirmations. No customer PII is shown on this page.</p>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce }}">
(function () {
    const preset = document.getElementById('preset');
    const customWrap = document.getElementById('customRangeWrap');
    const customWrapTo = document.getElementById('customRangeWrapTo');
    if (preset) {
        const toggle = function () {
            const show = preset.value === 'custom';
            customWrap.classList.toggle('d-none', !show);
            customWrapTo.classList.toggle('d-none', !show);
        };
        toggle();
        preset.addEventListener('change', toggle);
    }

    const visitorWrap = document.getElementById('analytics-overview');
    if (visitorWrap && !visitorWrap.classList.contains('d-none')) {
        const series = @json($report['visitors']['series'] ?? []);
        const canvas = document.getElementById('visitorTrendChart');
        if (canvas && window.Chart && series.length) {
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: series.map(function (row) { return row.date.slice(5); }),
                    datasets: [
                        {
                            label: 'Website Visitors',
                            data: series.map(function (row) { return row.visitors; }),
                            borderColor: '#263D25',
                            backgroundColor: 'rgba(38, 61, 37, 0.1)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: series.length > 60 ? 0 : 3
                        },
                        {
                            label: 'New Visitors',
                            data: series.map(function (row) { return row.new; }),
                            borderColor: '#6F8736',
                            backgroundColor: 'rgba(111, 135, 54, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: series.length > 60 ? 0 : 3
                        },
                        {
                            label: 'Returning Visitors',
                            data: series.map(function (row) { return row.returning; }),
                            borderColor: '#c98b2b',
                            backgroundColor: 'rgba(201, 139, 43, 0.08)',
                            borderDash: [5, 4],
                            fill: false,
                            tension: 0.35,
                            pointRadius: series.length > 60 ? 0 : 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('en-IN');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: '#f0ede6' }
                        }
                    }
                }
            });
        }
    }

    const sectionWrap = document.getElementById('analytics-revenue');
    if (sectionWrap && !sectionWrap.classList.contains('d-none')) {
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
    }
})();
</script>
@endpush