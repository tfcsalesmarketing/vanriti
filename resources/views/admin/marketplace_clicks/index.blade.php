@extends('admin.layouts.app')

@section('title', 'Marketplace Outbound Clicks')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Marketplace Outbound Clicks</h5>
        <div class="text-muted small mt-0.5">
            Monitor Amazon and Flipkart buy-button click volume, product-level conversion intent, and SKU breakdown
            <span class="badge bg-light text-secondary border ms-1">{{ $dateRangeLabel }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.marketplace-clicks.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-xs">
            <i class="bi bi-download"></i><span>Export CSV</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-xs">
            <i class="bi bi-box-seam"></i><span>Manage Products</span>
        </a>
    </div>
</div>

{{-- ── KPI Summary Cards ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- Card 1: Total Outbound Clicks --}}
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border-left:4px solid #0f766e !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Outbound Clicks</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#ccfbf1;color:#0f766e;">
                        <i class="bi bi-cursor-fill"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-dark">{{ number_format($kpis['total_clicks']) }}</div>
                <div class="text-muted" style="font-size:0.75rem;">
                    {{ $dateRangeLabel }} across all products
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Amazon Clicks --}}
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border-left:4px solid #f59e0b !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Amazon India Clicks</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fef3c7;color:#d97706;">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-4 fw-bold text-dark">{{ number_format($kpis['amazon_clicks']) }}</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.72rem;">
                        {{ $kpis['amazon_share'] }}%
                    </span>
                </div>
                <div class="text-muted" style="font-size:0.75rem;">Share of total outbound clicks</div>
            </div>
        </div>
    </div>

    {{-- Card 3: Flipkart Clicks --}}
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-left:4px solid #2563eb !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Flipkart Clicks</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#dbeafe;color:#1d4ed8;">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-4 fw-bold text-dark">{{ number_format($kpis['flipkart_clicks']) }}</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle" style="font-size:0.72rem;">
                        {{ $kpis['flipkart_share'] }}%
                    </span>
                </div>
                <div class="text-muted" style="font-size:0.75rem;">Share of total outbound clicks</div>
            </div>
        </div>
    </div>

    {{-- Card 4: SKU Engagement --}}
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left:4px solid #16a34a !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Active Clicked SKUs</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#dcfce7;color:#15803d;">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-4 fw-bold text-dark">{{ number_format($kpis['clicked_skus_count']) }}</span>
                    <span class="text-muted small">/ {{ $kpis['total_with_links'] }} with links</span>
                </div>
                <div class="text-muted" style="font-size:0.75rem;">
                    {{ $kpis['sku_engagement_rate'] }}% catalog link engagement rate
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Comprehensive Filter Bar ─────────────────────────────────────────── --}}
<div class="card filter-card mb-3 border-0 shadow-xs" style="border-radius:12px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.marketplace-clicks.index') }}" id="filterForm">
            {{-- Row 1: Search, Category, Links Configured, Activity --}}
            <div class="row g-2 align-items-center mb-2">
                {{-- Search --}}
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search title or SKU..." value="{{ $filters['search'] }}">
                    </div>
                </div>

                {{-- Category --}}
                <div class="col-6 col-md-4 col-lg-3">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $filters['category_id'] == $cat->id ? 'selected' : '' }}>
                                {{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Marketplace Links Configured --}}
                <div class="col-6 col-md-4 col-lg-3">
                    <select name="marketplace_link" class="form-select form-select-sm">
                        <option value="all" {{ $filters['marketplace_link'] === 'all' ? 'selected' : '' }}>Marketplace Links: All</option>
                        <option value="has_any" {{ $filters['marketplace_link'] === 'has_any' ? 'selected' : '' }}>Has Any Link (Amazon / Flipkart)</option>
                        <option value="has_both" {{ $filters['marketplace_link'] === 'has_both' ? 'selected' : '' }}>Has Both (Amazon &amp; Flipkart)</option>
                        <option value="has_amazon" {{ $filters['marketplace_link'] === 'has_amazon' ? 'selected' : '' }}>Has Amazon Link</option>
                        <option value="has_flipkart" {{ $filters['marketplace_link'] === 'has_flipkart' ? 'selected' : '' }}>Has Flipkart Link</option>
                        <option value="has_none" {{ $filters['marketplace_link'] === 'has_none' ? 'selected' : '' }}>No Marketplace Links</option>
                    </select>
                </div>

                {{-- Click Activity --}}
                <div class="col-6 col-md-4 col-lg-3">
                    <select name="activity" class="form-select form-select-sm">
                        <option value="all" {{ $filters['activity'] === 'all' ? 'selected' : '' }}>Activity: All SKUs</option>
                        <option value="with_clicks" {{ $filters['activity'] === 'with_clicks' ? 'selected' : '' }}>Has Clicks (&gt; 0)</option>
                        <option value="zero_clicks" {{ $filters['activity'] === 'zero_clicks' ? 'selected' : '' }}>Zero Clicks (0)</option>
                        <option value="amazon_only" {{ $filters['activity'] === 'amazon_only' ? 'selected' : '' }}>Has Amazon Clicks</option>
                        <option value="flipkart_only" {{ $filters['activity'] === 'flipkart_only' ? 'selected' : '' }}>Has Flipkart Clicks</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Date Range, Custom Date Pickers, Status, Stock, Sort, Per Page, Actions --}}
            <div class="row g-2 align-items-center">
                {{-- Date Range Presets --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="date_range" id="dateRangeSelect" class="form-select form-select-sm" onchange="toggleCustomDates()">
                        <option value="all" {{ $filters['date_range'] === 'all' ? 'selected' : '' }}>Date: All Time</option>
                        <option value="today" {{ $filters['date_range'] === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $filters['date_range'] === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="7days" {{ $filters['date_range'] === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ $filters['date_range'] === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $filters['date_range'] === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="custom" {{ $filters['date_range'] === 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                    </select>
                </div>

                {{-- Date From (Custom) --}}
                <div class="col-6 col-md-3 col-lg-2" id="customDateFromCol" style="{{ $filters['date_range'] === 'custom' ? '' : 'display:none;' }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted" style="font-size:0.75rem;">From</span>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>

                {{-- Date To (Custom) --}}
                <div class="col-6 col-md-3 col-lg-2" id="customDateToCol" style="{{ $filters['date_range'] === 'custom' ? '' : 'display:none;' }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted" style="font-size:0.75rem;">To</span>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>

                {{-- Sort By --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="sort" class="form-select form-select-sm">
                        <option value="total_desc" {{ $filters['sort'] === 'total_desc' ? 'selected' : '' }}>Sort: Total Clicks (High &rarr; Low)</option>
                        <option value="total_asc" {{ $filters['sort'] === 'total_asc' ? 'selected' : '' }}>Sort: Total Clicks (Low &rarr; High)</option>
                        <option value="amazon_desc" {{ $filters['sort'] === 'amazon_desc' ? 'selected' : '' }}>Sort: Amazon Clicks (High &rarr; Low)</option>
                        <option value="flipkart_desc" {{ $filters['sort'] === 'flipkart_desc' ? 'selected' : '' }}>Sort: Flipkart Clicks (High &rarr; Low)</option>
                        <option value="recent_desc" {{ $filters['sort'] === 'recent_desc' ? 'selected' : '' }}>Sort: Recently Clicked</option>
                        <option value="sku_asc" {{ $filters['sort'] === 'sku_asc' ? 'selected' : '' }}>Sort: SKU (A &rarr; Z)</option>
                        <option value="name_asc" {{ $filters['sort'] === 'name_asc' ? 'selected' : '' }}>Sort: Product Name (A &rarr; Z)</option>
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-6 col-md-2 col-lg-1">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>Status: All</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                {{-- Stock --}}
                <div class="col-6 col-md-2 col-lg-1">
                    <select name="stock" class="form-select form-select-sm">
                        <option value="all" {{ $filters['stock'] === 'all' ? 'selected' : '' }}>Stock: All</option>
                        <option value="in_stock" {{ $filters['stock'] === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="out_of_stock" {{ $filters['stock'] === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>

                {{-- Rows Per Page --}}
                <div class="col-6 col-md-2 col-lg-1">
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="25" {{ $filters['per_page'] == '25' ? 'selected' : '' }}>25 rows</option>
                        <option value="50" {{ $filters['per_page'] == '50' ? 'selected' : '' }}>50 rows</option>
                        <option value="100" {{ $filters['per_page'] == '100' ? 'selected' : '' }}>100 rows</option>
                        <option value="all" {{ $filters['per_page'] == 'all' ? 'selected' : '' }}>All SKUs</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-xs">
                        <i class="bi bi-funnel-fill"></i><span>Apply</span>
                    </button>
                    <a href="{{ route('admin.marketplace-clicks.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise"></i><span>Reset</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── SKU Marketplace Clicks Table ────────────────────────────────────── --}}
<div class="card border-0 shadow-xs" style="border-radius:12px; overflow:hidden;">
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="bg-light text-muted small fw-semibold text-uppercase" style="font-size:0.75rem; letter-spacing:0.5px;">
                <tr>
                    <th style="min-width:280px;">Product &amp; SKU</th>
                    <th style="min-width:140px;">Category</th>
                    <th style="min-width:180px;">Marketplace Links</th>
                    <th class="text-center" style="min-width:130px; background-color:#fffdf5;">
                        <span class="d-inline-flex align-items-center gap-1 text-warning-emphasis">
                            <i class="bi bi-cart"></i> Amazon Clicks
                        </span>
                    </th>
                    <th class="text-center" style="min-width:130px; background-color:#f8faff;">
                        <span class="d-inline-flex align-items-center gap-1 text-primary">
                            <i class="bi bi-bag"></i> Flipkart Clicks
                        </span>
                    </th>
                    <th class="text-center" style="min-width:140px;">
                        <span class="d-inline-flex align-items-center gap-1 text-dark">
                            <i class="bi bi-cursor-fill text-teal"></i> Total Clicks
                        </span>
                    </th>
                    <th style="min-width:150px;">Last Clicked</th>
                    <th class="text-end" style="min-width:90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $primaryImage = $product->getPrimaryImage();
                        $totalClicks = (int) $product->total_clicks_count;
                        $amazonClicks = (int) $product->amazon_clicks_count;
                        $flipkartClicks = (int) $product->flipkart_clicks_count;
                        $hasAmazon = filled($product->amazon_url);
                        $hasFlipkart = filled($product->flipkart_url);
                        $amazonPct = $totalClicks > 0 ? round(($amazonClicks / $totalClicks) * 100) : 0;
                        $flipkartPct = $totalClicks > 0 ? (100 - $amazonPct) : 0;
                    @endphp
                    <tr>
                        {{-- Product & SKU --}}
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="position-relative" style="width:44px; height:44px; flex-shrink:0;">
                                    @if ($primaryImage)
                                        <img src="{{ image_url($primaryImage->image_path) }}" alt="" class="rounded-2 border w-100 h-100" style="object-fit:cover;">
                                    @else
                                        <div class="rounded-2 border bg-light d-flex align-items-center justify-content-center w-100 h-100">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="overflow-hidden" style="max-width:280px;">
                                    <div class="fw-semibold text-truncate small" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-0.5">
                                        @if ($product->sku)
                                            <code class="text-muted fw-semibold" style="font-size:0.75rem;">{{ $product->sku }}</code>
                                        @else
                                            <span class="text-muted" style="font-size:0.72rem;">No SKU</span>
                                        @endif

                                        @if ($product->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem; padding:1px 5px;">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:0.65rem; padding:1px 5px;">{{ ucfirst($product->status) }}</span>
                                        @endif

                                        @if ($product->stock <= 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.65rem; padding:1px 5px;">OOS</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Category --}}
                        <td>
                            @if ($product->categories->isNotEmpty())
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:0.75rem;">
                                    {{ $product->categories->first()->name }}
                                </span>
                                @if ($product->categories->count() > 1)
                                    <span class="badge bg-light text-muted border fw-normal" style="font-size:0.7rem;" title="{{ $product->categories->pluck('name')->implode(', ') }}">
                                        +{{ $product->categories->count() - 1 }}
                                    </span>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        {{-- Marketplace Links Configured --}}
                        <td>
                            <div class="d-flex flex-column gap-1">
                                {{-- Amazon Link Badge --}}
                                @if ($hasAmazon)
                                    <a href="{{ $product->amazon_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-decoration-none d-inline-flex align-items-center gap-1 text-truncate" style="max-width:200px; font-size:0.75rem; color:#b45309;" title="Open Amazon link: {{ $product->amazon_url }}">
                                        <i class="bi bi-check-circle-fill text-warning"></i>
                                        <span class="fw-semibold">Amazon</span>
                                        <i class="bi bi-box-arrow-up-right text-muted" style="font-size:0.68rem;"></i>
                                    </a>
                                @else
                                    <span class="text-muted d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                                        <i class="bi bi-dash-circle text-muted opacity-50"></i>
                                        <span>Amazon (None)</span>
                                    </span>
                                @endif

                                {{-- Flipkart Link Badge --}}
                                @if ($hasFlipkart)
                                    <a href="{{ $product->flipkart_url }}" target="_blank" rel="noopener noreferrer nofollow" class="text-decoration-none d-inline-flex align-items-center gap-1 text-truncate" style="max-width:200px; font-size:0.75rem; color:#1d4ed8;" title="Open Flipkart link: {{ $product->flipkart_url }}">
                                        <i class="bi bi-check-circle-fill text-primary"></i>
                                        <span class="fw-semibold">Flipkart</span>
                                        <i class="bi bi-box-arrow-up-right text-muted" style="font-size:0.68rem;"></i>
                                    </a>
                                @else
                                    <span class="text-muted d-inline-flex align-items-center gap-1" style="font-size:0.75rem;">
                                        <i class="bi bi-dash-circle text-muted opacity-50"></i>
                                        <span>Flipkart (None)</span>
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Amazon Clicks --}}
                        <td class="text-center" style="background-color:#fffdf5;">
                            @if ($amazonClicks > 0)
                                <div class="badge px-2.5 py-1.5 fs-6 fw-bold" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                                    {{ number_format($amazonClicks) }}
                                </div>
                                @if ($totalClicks > 0)
                                    <div class="text-muted mt-0.5" style="font-size:0.7rem;">
                                        {{ $amazonPct }}%
                                    </div>
                                @endif
                            @else
                                <span class="text-muted small fw-normal opacity-50">0</span>
                            @endif
                        </td>

                        {{-- Flipkart Clicks --}}
                        <td class="text-center" style="background-color:#f8faff;">
                            @if ($flipkartClicks > 0)
                                <div class="badge px-2.5 py-1.5 fs-6 fw-bold" style="background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;">
                                    {{ number_format($flipkartClicks) }}
                                </div>
                                @if ($totalClicks > 0)
                                    <div class="text-muted mt-0.5" style="font-size:0.7rem;">
                                        {{ $flipkartPct }}%
                                    </div>
                                @endif
                            @else
                                <span class="text-muted small fw-normal opacity-50">0</span>
                            @endif
                        </td>

                        {{-- Total Clicks --}}
                        <td class="text-center">
                            @if ($totalClicks > 0)
                                <div class="badge bg-dark px-2.5 py-1.5 fs-6 fw-bold shadow-xs">
                                    {{ number_format($totalClicks) }}
                                </div>
                                {{-- Visual Proportion Bar --}}
                                <div class="progress mt-1.5 mx-auto" style="height:4px; max-width:80px; background:#e2e8f0;" title="Amazon {{ $amazonPct }}% | Flipkart {{ $flipkartPct }}%">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $amazonPct }}%"></div>
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $flipkartPct }}%"></div>
                                </div>
                            @else
                                <span class="badge bg-light text-muted border fw-normal" style="font-size:0.75rem;">
                                    0 clicks
                                </span>
                            @endif
                        </td>

                        {{-- Last Clicked --}}
                        <td>
                            @if ($product->last_clicked_at)
                                @php
                                    $lastDate = \Carbon\Carbon::parse($product->last_clicked_at);
                                @endphp
                                <div class="small fw-semibold text-dark" title="{{ $lastDate->format('d M Y, h:i A') }}">
                                    {{ $lastDate->diffForHumans() }}
                                </div>
                                <div class="text-muted" style="font-size:0.72rem;">
                                    {{ $lastDate->format('d M Y, h:i A') }}
                                </div>
                            @else
                                <span class="text-muted small">Never</span>
                            @endif
                        </td>

                        {{-- Quick Actions --}}
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                <a href="{{ route('product.show', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Storefront PDP" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-outline-primary p-1 px-2" title="Edit Product Links in Admin">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state py-5 text-center">
                                <div class="empty-state-icon fs-1 text-muted mb-2">
                                    <i class="bi bi-cursor"></i>
                                </div>
                                <h6 class="fw-bold">No Marketplace Clicks Recorded</h6>
                                <p class="text-muted small">No products matched your active search, category, link, activity, or date filters.</p>
                                <a href="{{ route('admin.marketplace-clicks.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset All Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between p-3 bg-light">
            <span class="small text-muted">
                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} SKUs
            </span>
            <div>{{ $products->links() }}</div>
        </div>
    @endif
</div>

<script>
function toggleCustomDates() {
    const select = document.getElementById('dateRangeSelect');
    const fromCol = document.getElementById('customDateFromCol');
    const toCol = document.getElementById('customDateToCol');
    if (!select || !fromCol || !toCol) return;

    if (select.value === 'custom') {
        fromCol.style.display = '';
        toCol.style.display = '';
    } else {
        fromCol.style.display = 'none';
        toCol.style.display = 'none';
    }
}
</script>
@endsection

