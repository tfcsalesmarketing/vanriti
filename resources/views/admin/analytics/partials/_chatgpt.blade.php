@php
    $chatgpt = $report['chatgpt'] ?? [];
    $summary = $chatgpt['summary'] ?? [];
    $behavior = $chatgpt['behavior'] ?? [];
    $orders = $chatgpt['orders'] ?? [];
    $platform = $chatgpt['platform'] ?? [];
    $campaigns = $chatgpt['campaigns'] ?? [];
    $products = $chatgpt['products'] ?? [];
    $series = $chatgpt['series'] ?? [];

    $platformAvailable = (bool) ($platform['available'] ?? false);
@endphp

{{-- Platform Integration Status Banner --}}
@if (! $platformAvailable)
    <div class="alert alert-light border shadow-xs d-flex align-items-center gap-3 mb-4 p-3 rounded-3" role="alert">
        <div class="rounded-circle bg-secondary bg-opacity-10 p-2 text-secondary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-info-circle-fill fs-5"></i>
        </div>
        <div class="flex-grow-1 small">
            <div class="fw-semibold text-dark">OpenAI Advertiser API Status: <span class="badge text-bg-light border text-muted">Offline / Not Configured</span></div>
            <div class="text-muted">{{ $platform['reason'] ?? 'Platform metrics like ad spend and platform impressions require connection to the OpenAI Advertiser API. First-party website attribution, conversion tracking and order metrics are actively measured below.' }}</div>
        </div>
    </div>
@else
    <div class="alert alert-success border-success-subtle bg-success bg-opacity-10 d-flex align-items-center gap-3 mb-4 p-3 rounded-3" role="alert">
        <div class="rounded-circle bg-success bg-opacity-20 p-2 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-check-circle-fill fs-5"></i>
        </div>
        <div class="flex-grow-1 small">
            <div class="fw-semibold text-success">OpenAI Advertiser API: <span class="badge text-bg-success">Connected</span></div>
            <div class="text-success-emphasis">Ad campaign spend and impression insights are synchronized with first-party storefront conversions.</div>
        </div>
    </div>
@endif

{{-- Active OpenAI Tracking Pipeline Badges --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <div class="badge bg-white border text-dark p-2 px-3 d-inline-flex align-items-center gap-2 shadow-xs rounded-2">
        <i class="bi bi-bullseye text-primary fs-6"></i>
        <span><strong>Measurement Pixel:</strong> <code>{{ config('openai_ads.pixel_id', 'BBWekc1tuk8CcLkcNrLBzk') }}</code></span>
    </div>
    <div class="badge bg-white border text-dark p-2 px-3 d-inline-flex align-items-center gap-2 shadow-xs rounded-2">
        <i class="bi bi-cloud-arrow-up text-success fs-6"></i>
        <span><strong>Conversions API:</strong> {{ config('openai_ads.validate_only') ? 'Active (Validate-Only Mode)' : 'Active (Live Production Mode)' }}</span>
    </div>
    <div class="badge bg-white border text-dark p-2 px-3 d-inline-flex align-items-center gap-2 shadow-xs rounded-2">
        <i class="bi bi-shield-check text-info fs-6"></i>
        <span><strong>Event Type:</strong> <code>{{ config('openai_ads.event_type', 'order_created') }}</code> ({{ config('openai_ads.event_setting_name', 'VANRITI Purchases') }})</span>
    </div>
    <div class="badge bg-white border text-dark p-2 px-3 d-inline-flex align-items-center gap-2 shadow-xs rounded-2">
        <i class="bi bi-diagram-3 text-secondary fs-6"></i>
        <span><strong>Source ID:</strong> <code>{{ config('openai_ads.source_id', 'cds_6ae63e02f93c819795162116d99676e4') }}</code></span>
    </div>
</div>

{{-- Section Title --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h6 class="fw-bold mb-0">OpenAI Ads &mdash; ChatGPT Ads Campaign Performance</h6>
        <span class="text-muted small">Real-time first-party measurement for traffic, attribution, and server-side Conversions API delivery for OpenAI ChatGPT Ads (click reference <code>oppref</code>).</span>
    </div>
</div>

{{-- Top Row: Core Performance KPIs --}}
<div class="row g-3 mb-4">
    {{-- Impressions --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Ad impressions reported by OpenAI Ads Manager.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-eye"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Ad Impressions</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['impressions'] !== null ? number_format($summary['impressions']) : 'Unavailable' }}
                </div>
                <div class="small text-muted">
                    {{ $summary['impressions'] !== null ? 'from OpenAI Ads' : 'Requires Ads API' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Ad Clicks --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Ad clicks reported by OpenAI Ads Manager.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-cursor"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Ad Clicks</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['ad_clicks'] !== null ? number_format($summary['ad_clicks']) : 'Unavailable' }}
                </div>
                <div class="small text-muted">
                    {{ $summary['ad_clicks'] !== null ? 'from OpenAI Ads' : 'Requires Ads API' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Website Visits --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Storefront landing page visits with ChatGPT Ads attribution (oppref or chatgpt source).">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-globe"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Website Visits</span>
                </div>
                <div class="fs-4 fw-bold">{{ number_format($summary['website_visits'] ?? 0) }}</div>
                <div class="small text-muted">Attributed sessions</div>
            </div>
        </div>
    </div>

    {{-- Ad Spend --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Advertising spend reported by OpenAI Ads Manager.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-wallet2"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Ad Spend</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['spend'] !== null ? format_price($summary['spend']) : 'Unavailable' }}
                </div>
                <div class="small text-muted">
                    {{ $summary['spend'] !== null ? 'Platform spend' : 'Requires Ads API' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Conversions (Orders Created) --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Total orders created originating from ChatGPT Ads traffic.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-bag-check"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Conversions</span>
                </div>
                <div class="fs-4 fw-bold">{{ number_format($summary['conversions'] ?? 0) }}</div>
                <div class="small text-muted">Order creations</div>
            </div>
        </div>
    </div>

    {{-- Conversion Rate --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Conversion rate: Conversions ÷ Website Visits.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Conversion Rate</span>
                </div>
                <div class="fs-4 fw-bold">{{ number_format($summary['conversion_rate'] ?? 0, 2) }}%</div>
                <div class="small text-muted">Orders / Visits</div>
            </div>
        </div>
    </div>

    {{-- Cost Per Acquisition (CPA) --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Spend ÷ Conversions.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-tag"></i></span>
                    <span class="text-muted small fw-medium text-truncate">CPA</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['cost_per_acquisition'] !== null ? format_price($summary['cost_per_acquisition']) : 'Unavailable' }}
                </div>
                <div class="small text-muted">Cost per order</div>
            </div>
        </div>
    </div>

    {{-- Paid Revenue --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Confirmed paid revenue from ChatGPT Ads traffic (verified online payments + collected COD).">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></span>
                    <span class="text-muted small fw-medium text-truncate">Paid Revenue</span>
                </div>
                <div class="fs-4 fw-bold text-success">{{ format_price($summary['paid_revenue'] ?? 0) }}</div>
                <div class="small text-muted">Excludes uncollected COD</div>
            </div>
        </div>
    </div>

    {{-- ROAS --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Return on Ad Spend: Paid Revenue ÷ Spend.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-trophy"></i></span>
                    <span class="text-muted small fw-medium text-truncate">ROAS</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['roas'] !== null ? number_format($summary['roas'], 2).'x' : 'Unavailable' }}
                </div>
                <div class="small text-muted">Revenue / Spend</div>
            </div>
        </div>
    </div>

    {{-- CTR --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Click-through rate: Ad Clicks ÷ Ad Impressions.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-percent"></i></span>
                    <span class="text-muted small fw-medium text-truncate">CTR</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['ctr'] !== null ? number_format($summary['ctr'], 2).'%' : 'Unavailable' }}
                </div>
                <div class="small text-muted">Clicks / Impr.</div>
            </div>
        </div>
    </div>

    {{-- CPC --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Cost per click: Spend ÷ Clicks.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-coin"></i></span>
                    <span class="text-muted small fw-medium text-truncate">CPC</span>
                </div>
                <div class="fs-4 fw-bold">
                    {{ $summary['cpc'] !== null ? format_price($summary['cpc']) : 'Unavailable' }}
                </div>
                <div class="small text-muted">Cost per click</div>
            </div>
        </div>
    </div>

    {{-- Conversions API Delivery --}}
    <div class="col-6 col-md-3 col-xxl-2">
        <div class="card stat-card h-100" title="Server-side Conversions API delivery status for order_created events.">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-dark bg-opacity-10 text-dark"><i class="bi bi-cloud-arrow-up"></i></span>
                    <span class="text-muted small fw-medium text-truncate">CAPI Delivered</span>
                </div>
                <div class="fs-4 fw-bold">{{ number_format($orders['capi']['sent'] ?? 0) }}</div>
                <div class="small text-muted">
                    @if (($orders['capi']['failed'] ?? 0) > 0)
                        <span class="text-danger fw-semibold">{{ $orders['capi']['failed'] }} failed</span>
                    @elseif (($orders['capi']['expired'] ?? 0) > 0 || ($orders['capi']['rejected'] ?? 0) > 0)
                        <span class="text-warning fw-semibold">{{ ($orders['capi']['expired'] ?? 0) + ($orders['capi']['rejected'] ?? 0) }} terminal</span>
                    @elseif (($orders['capi']['pending'] ?? 0) > 0)
                        <span class="text-info fw-semibold">{{ $orders['capi']['pending'] }} pending</span>
                    @else
                        <span class="text-success">0 failed</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Middle Row: Behaviour Funnel & Revenue Breakdown --}}
<div class="row g-3 mb-4">
    {{-- ChatGPT Ads Funnel --}}
    <div class="col-12 col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Customer Journey Funnel</span>
                <span class="badge text-bg-light border text-muted">First-Party Behavioral Log</span>
            </div>
            <div class="card-body py-3">
                @php
                    $funnelSteps = [
                        ['name' => 'Landing Page Visits', 'count' => (int) ($behavior['visits'] ?? 0), 'icon' => 'bi-box-arrow-in-right'],
                        ['name' => 'Product Views', 'count' => (int) ($behavior['product_views'] ?? 0), 'icon' => 'bi-eye'],
                        ['name' => 'Add to Cart Events', 'count' => (int) ($behavior['add_to_carts'] ?? 0), 'icon' => 'bi-cart-plus'],
                        ['name' => 'Checkout Starts', 'count' => (int) ($behavior['checkout_starts'] ?? 0), 'icon' => 'bi-credit-card'],
                        ['name' => 'Orders Placed (Conversions)', 'count' => (int) ($orders['orders_created'] ?? 0), 'icon' => 'bi-bag-check'],
                        ['name' => 'Confirmed Paid Orders', 'count' => (int) ($orders['paid_orders'] ?? 0), 'icon' => 'bi-check-all'],
                    ];
                    $maxCount = max(1, $behavior['visits'] ?? 1);
                @endphp

                <div class="d-flex flex-column gap-3">
                    @foreach ($funnelSteps as $index => $step)
                        @php
                            $pct = round(($step['count'] / $maxCount) * 100, 1);
                        @endphp
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold">
                                    <i class="bi {{ $step['icon'] }} me-1.5 text-muted"></i>
                                    {{ $step['name'] }}
                                </span>
                                <span class="small fw-bold">{{ number_format($step['count']) }}</span>
                            </div>
                            <div class="progress" style="height: 7px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, max(2, $pct)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue & Order State Breakdown --}}
    <div class="col-12 col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Revenue &amp; Payment Distinction</span>
                <span class="badge text-bg-light border text-muted">COD vs Prepaid</span>
            </div>
            <div class="card-body py-3">
                <div class="p-2.5 rounded-3 border bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Prepaid Orders (Online)</span>
                        <span class="badge text-bg-primary">{{ $orders['prepaid_orders'] ?? 0 }} orders</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="small text-muted">Verified Paid Revenue:</span>
                        <span class="fw-bold text-success">{{ format_price($orders['prepaid_paid_revenue'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="small text-muted">Total Placed Value:</span>
                        <span class="small text-muted">{{ format_price($orders['prepaid_order_value'] ?? 0) }}</span>
                    </div>
                </div>

                <div class="p-2.5 rounded-3 border bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Cash on Delivery (COD)</span>
                        <span class="badge text-bg-secondary">{{ $orders['cod_orders'] ?? 0 }} orders</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="small text-muted">Cash Collected Revenue:</span>
                        <span class="fw-bold text-success">{{ format_price($orders['cod_collected_revenue'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="small text-muted">Pending / Uncollected COD Value:</span>
                        <span class="small text-warning fw-semibold">{{ format_price(max(0, ($orders['cod_order_value'] ?? 0) - ($orders['cod_collected_revenue'] ?? 0))) }}</span>
                    </div>
                </div>

                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="p-2 border rounded-2">
                            <span class="small text-muted d-block">Cancelled Orders</span>
                            <span class="fw-bold text-danger">{{ $orders['cancelled_orders'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-2">
                            <span class="small text-muted d-block">Refunded Orders</span>
                            <span class="fw-bold text-danger">{{ $orders['refunded_orders'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Daily Trend Chart --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daily Traffic &amp; Revenue Trends</span>
                <span class="badge text-bg-light border text-muted">ChatGPT Ads Attribution</span>
            </div>
            <div class="card-body py-3">
                <canvas id="chatGptTrendChart" height="70"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Bottom Row: Campaign & Product Breakdown Tables --}}
<div class="row g-3 mb-4">
    {{-- Campaign-wise Performance --}}
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">Campaign-wise Breakdown</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Campaign</th>
                            <th class="text-end">Total Orders</th>
                            <th class="text-end">Paid Orders</th>
                            <th class="text-end">Paid Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $camp)
                            <tr>
                                <td class="fw-medium">{{ $camp['campaign'] }}</td>
                                <td class="text-end">{{ number_format($camp['orders']) }}</td>
                                <td class="text-end">{{ number_format($camp['paid_orders']) }}</td>
                                <td class="text-end fw-semibold text-success">{{ format_price($camp['revenue']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No campaign-attributed orders recorded in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Product-wise Performance --}}
    <div class="col-12 col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header">Product Performance (ChatGPT Ads)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-end">Units Sold</th>
                            <th class="text-end">Paid Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $prod)
                            <tr>
                                <td class="fw-medium text-truncate" style="max-width: 220px;" title="{{ $prod['product_name'] }}">
                                    {{ $prod['product_name'] }}
                                </td>
                                <td><code>{{ $prod['sku'] }}</code></td>
                                <td class="text-end">{{ number_format($prod['units_sold']) }}</td>
                                <td class="text-end fw-semibold text-success">{{ format_price($prod['revenue']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No product purchases recorded from ChatGPT Ads traffic in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

