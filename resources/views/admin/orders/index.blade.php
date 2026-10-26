@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
@php
    $bulkActions = [
        'pending' => [
            ['route' => route('admin.orders.shipmojo.bulk.push'), 'label' => 'Push to ShipMojo', 'btn' => 'dark', 'icon' => 'bi-upload', 'type' => 'primary', 'ok' => 'Push', 'confirm' => 'Push selected orders to ShipMojo?'],
            ['route' => route('admin.orders.bulk.cancel'), 'label' => 'Cancel Selected', 'btn' => 'outline-danger', 'icon' => 'bi-x-circle', 'type' => 'danger', 'ok' => 'Cancel Orders', 'confirm' => 'Cancel selected orders? Stock will be restored and paid orders will receive a refund.'],
        ],
        'hold' => [
            ['route' => route('admin.orders.bulk.cancel'), 'label' => 'Cancel Selected', 'btn' => 'outline-danger', 'icon' => 'bi-x-circle', 'type' => 'danger', 'ok' => 'Cancel Orders', 'confirm' => 'Cancel selected orders?'],
        ],
        'ready_to_ship' => [
            ['route' => route('admin.orders.shipmojo.bulk.schedule-pickup'), 'label' => 'Schedule Pickup', 'btn' => 'dark', 'icon' => 'bi-truck', 'type' => 'primary', 'ok' => 'Schedule', 'confirm' => 'Schedule pickup for selected orders?'],
            ['route' => route('admin.orders.shipmojo.bulk.labels'), 'label' => 'Download Labels (ZIP)', 'btn' => 'dark', 'icon' => 'bi-printer', 'type' => 'primary', 'ok' => 'Download', 'confirm' => 'Download shipping labels for selected orders?'],
            ['route' => route('admin.orders.bulk.cancel'), 'label' => 'Cancel Selected', 'btn' => 'outline-danger', 'icon' => 'bi-x-circle', 'type' => 'danger', 'ok' => 'Cancel Orders', 'confirm' => 'Cancel selected orders in system & ShipMojo?'],
        ],
    ];
    $tabBulkActions = $bulkActions[$activeTab] ?? [];
    $orderClasses = [
        'cancelled' => 'danger', 'failed' => 'danger', 'delivered' => 'success',
        'shipped' => 'info', 'out_for_delivery' => 'info', 'pending' => 'secondary',
        'confirmed' => 'primary', 'processing' => 'warning', 'packed' => 'warning',
    ];
    $paymentClasses = [
        'paid' => 'success', 'refunded' => 'secondary', 'partially_refunded' => 'warning',
        'pending' => 'secondary', 'processing' => 'info', 'failed' => 'danger', 'cancelled' => 'danger',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Orders Management</h5>
        <div class="text-muted small mt-0.5">Showing {{ $orders->total() }} order(s) in <span class="fw-semibold text-dark">{{ $tabs[$activeTab] }}</span></div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.orders.manifest') }}" target="_blank" class="btn btn-sm btn-outline-dark d-inline-flex align-items-center gap-1" title="Print courier handover manifest for dispatched orders">
            <i class="bi bi-file-earmark-spreadsheet text-success"></i><span>Handover Manifest</span>
        </a>
        <a href="{{ route('admin.orders.index', ['tab' => 'pending']) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-clock-history"></i><span>Pending ({{ $tabCounts['pending'] ?? 0 }})</span>
        </a>
        <a href="{{ route('admin.orders.index', ['tab' => 'ready_to_ship']) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-seam"></i><span>Ready to Ship ({{ $tabCounts['ready_to_ship'] ?? 0 }})</span>
        </a>
    </div>
</div>

{{-- Modern Order KPI Metric Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left:4px solid #10b981 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Revenue</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#d1fae5;color:#059669;">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ format_price(\App\Models\Order::whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total')) }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Confirmed order volume</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-left:4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Pending Fulfillment</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#dbeafe;color:#2563eb;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $tabCounts['pending'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Awaiting ShipMojo push</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #fefce8 0%, #ffffff 100%); border-left:4px solid #eab308 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Ready to Ship</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fef9c3;color:#ca8a04;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $tabCounts['ready_to_ship'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Pushed to ShipMojo / Ready for dispatch</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); border-left:4px solid #64748b !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">In Transit / Shipped</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#e2e8f0;color:#475569;">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $tabCounts['shipped'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Delivered: {{ $tabCounts['delivered'] ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Modern Segmented Status Tabs --}}
<div class="mb-3">
    <ul class="nav segmented-nav-pills flex-nowrap overflow-auto p-1.5 shadow-xs bg-white border" style="border-radius: 9999px;">
        @foreach ($tabs as $key => $label)
            @php
                $isActive = $activeTab === $key;
                $count = $tabCounts[$key] ?? 0;
            @endphp
            <li class="nav-item">
                <a class="nav-link {{ $isActive ? 'active' : '' }}"
                   href="{{ route('admin.orders.index', array_merge(request()->except('tab', 'page'), ['tab' => $key])) }}"
                   style="white-space: nowrap;">
                    <span>{{ $label }}</span>
                    <span class="badge ms-1.5">
                        {{ $count }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

{{-- Streamlined Filter Bar --}}
{{-- Streamlined Filter Bar --}}
<div class="card filter-card mb-3 shadow-xs border">
    <div class="card-body p-2.5">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-center" id="filterForm">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" id="orderSearchInput" class="form-control border-start-0"
                           placeholder="Order #, name, phone, AWB, email..."
                           value="{{ request('q') }}" autocomplete="off">
                    @if (request('q'))
                        <a href="{{ route('admin.orders.index', array_merge(request()->except('q', 'page'), ['tab' => $activeTab])) }}"
                           class="input-group-text bg-light text-muted border-start-0 text-decoration-none" title="Clear search">
                            <i class="bi bi-x"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="payment_method" class="form-select form-select-sm js-auto-submit">
                    <option value="">All Payment Methods</option>
                    <option value="cod" {{ request('payment_method') === 'cod' ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                    <option value="razorpay" {{ request('payment_method') === 'razorpay' ? 'selected' : '' }}>Online / Prepaid (Razorpay)</option>
                    <option value="manual" {{ request('payment_method') === 'manual' ? 'selected' : '' }}>Manual / Bank Transfer</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="courier" class="form-select form-select-sm js-auto-submit">
                    <option value="">All Couriers</option>
                    @foreach ($couriers ?? [] as $courierName)
                        <option value="{{ $courierName }}" {{ request('courier') === $courierName ? 'selected' : '' }}>{{ $courierName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1.5" style="flex: 0 0 auto; width: 14%;">
                <select name="payment_status" class="form-select form-select-sm js-auto-submit">
                    <option value="">All Payment Status</option>
                    @foreach ($paymentStatuses as $status)
                        <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1.5" style="flex: 0 0 auto; width: 13%;">
                <select name="date_preset" id="datePresetSelect" class="form-select form-select-sm">
                    <option value="">All Dates</option>
                    <option value="today" {{ request('date_preset') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('date_preset') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ request('date_preset') === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="this_month" {{ request('date_preset') === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ request('date_preset') === 'custom' || request('date_from') || request('date_to') ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div class="col-6 col-md-1" id="dateFromCol" style="{{ request('date_preset') === 'custom' || request('date_from') ? '' : 'display:none;' }}">
                <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-1" id="dateToCol" style="{{ request('date_preset') === 'custom' || request('date_to') ? '' : 'display:none;' }}">
                <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto d-flex gap-1 ms-auto">
                <button type="submit" class="btn btn-sm btn-dark" title="Apply Filters"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('admin.orders.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($tabBulkActions)
{{-- Top Bulk Actions Bar --}}
<div class="card mb-3 border bg-light-subtle">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-3 flex-wrap">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="select-all">
            <label class="form-check-label small fw-semibold cursor-pointer" for="select-all">Select all on page</label>
        </div>
        <span class="small text-muted border-start ps-3"><strong id="selected-count" class="text-dark">0</strong> order(s) selected</span>
        <div class="d-flex align-items-center gap-1.5 flex-wrap">
            @foreach ($tabBulkActions as $action)
                <button type="button" class="btn btn-sm btn-{{ $action['btn'] }} js-bulk-btn"
                    data-action="{{ $action['route'] }}" data-confirm="{{ $action['confirm'] }}"
                    data-ok="{{ $action['ok'] ?? 'Yes, proceed' }}" data-type="{{ $action['type'] ?? 'danger' }}">
                    <i class="bi {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
                </button>
            @endforeach
        </div>
        <small class="text-muted ms-auto d-none d-md-inline">{{ $tabCounts[$activeTab] }} order(s) in this view</small>
    </div>
</div>

{{-- Modern Floating Bulk Action Bar (fixed at bottom center) --}}
<div class="bulk-floating-bar" id="bulkFloatingBar">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill text-success"></i>
        <span class="small fw-bold"><span id="floating-selected-count">0</span> Orders Selected</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        @foreach ($tabBulkActions as $action)
            <button type="button" class="btn btn-sm btn-light text-dark fw-semibold js-bulk-btn"
                data-action="{{ $action['route'] }}" data-confirm="{{ $action['confirm'] }}"
                data-ok="{{ $action['ok'] ?? 'Yes, proceed' }}" data-type="{{ $action['type'] ?? 'danger' }}">
                <i class="bi {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
            </button>
        @endforeach
    </div>
</div>

<form method="POST" id="bulk-form" action="{{ $tabBulkActions[0]['route'] ?? route('admin.orders.shipmojo.bulk.push') }}" style="display:none;">
    @csrf
</form>
@endif

<div class="card table-card">
    <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        @if ($tabBulkActions)
                            <th style="width:38px;"><input type="checkbox" class="form-check-input row-select-all" aria-label="Select All"></th>
                        @endif
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer Details</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total Amount</th>
                        @if (in_array($activeTab, ['ready_to_ship', 'shipped', 'delivered'], true))
                            <th>Courier Partner</th>
                            <th>AWB / Tracking</th>
                        @endif
                        <th>Payment</th>
                        <th>Fulfillment</th>
                        <th class="text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $shipment = $order->shipments->sortByDesc('created_at')->first(); @endphp
                        <tr>
                            @if ($tabBulkActions)
                                <td><input type="checkbox" class="form-check-input order-check" name="order_ids[]" value="{{ $order->id }}"></td>
                            @endif
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none text-primary small">
                                    {{ $order->order_number }}
                                </a>
                                @if (($order->checkout_source ?? '') === 'whatsapp')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" title="Placed via WhatsApp Commerce"><i class="bi bi-whatsapp"></i> WA</span>
                                @endif
                            </td>
                            <td class="small text-muted" style="white-space: nowrap;">
                                {{ $order->created_at->format('d M Y') }}
                                <div style="font-size: 0.72rem; color: #8C9E90;">{{ $order->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                @php
                                    $custName = $order->user?->name ?: ($order->shipping_name ?: $order->billing_name);
                                    $initials = strtoupper(substr($custName, 0, 1) . substr(explode(' ', $custName)[1] ?? '', 0, 1));
                                    $custPhone = trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: ($order->user?->phone ?: $order->guest_mobile))));
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $custPhone);
                                    if (strlen($cleanPhone) === 10) {
                                        $cleanPhone = '91' . $cleanPhone;
                                    }
                                    $latestPayment = $order->payments->sortByDesc('created_at')->first();
                                    $failReason = $latestPayment?->gateway_response['failure_reason'] ?? $order->cancellation_reason;
                                    $dropReason = $latestPayment?->gateway_response['dropoff_reason'] ?? null;
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-dark fw-bold"
                                         style="width:32px; height:32px; font-size:0.75rem; background:#EBF3ED; color:#1C3C24; flex-shrink:0;">
                                        {{ $initials ?: 'C' }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold small text-dark">{{ $custName }}</div>
                                        <div class="text-muted small" style="font-size:0.75rem;">{{ $order->user?->email ?: ($order->guest_email ?: 'No email') }}</div>
                                        @if ($custPhone)
                                            <div class="d-flex align-items-center gap-1 small" style="font-size:0.72rem;">
                                                <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="text-success text-decoration-none fw-semibold d-inline-flex align-items-center gap-0.5">
                                                    <i class="bi bi-whatsapp"></i> {{ $custPhone }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center small"><span class="badge bg-light text-dark border">{{ $order->items_count }}</span></td>
                            <td class="text-end fw-bold small text-dark">{{ format_price($order->grand_total) }}</td>
                            @if (in_array($activeTab, ['ready_to_ship', 'shipped', 'delivered'], true))
                                <td class="small fw-semibold text-secondary">{{ $shipment?->courier ?: '—' }}</td>
                                <td class="small font-monospace" style="font-size:0.78rem;">
                                    @if ($shipment?->awb_number)
                                        @if ($order->tracking_url)
                                            <a href="{{ $order->tracking_url }}" target="_blank" class="text-decoration-none fw-bold text-primary d-inline-flex align-items-center gap-1" title="Track Shipment">
                                                <span>{{ $shipment->awb_number }}</span>
                                                <i class="bi bi-box-arrow-up-right" style="font-size: 0.7rem;"></i>
                                            </a>
                                        @else
                                            {{ $shipment->awb_number }}
                                        @endif
                                    @else
                                        <span class="text-muted">Awaiting Assignment</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="badge badge-soft-{{ $paymentClasses[$order->payment_status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}
                                </span>
                                @if ($failReason && in_array($order->payment_status, ['failed', 'cancelled'], true))
                                    <div class="text-danger small mt-1" style="font-size:0.72rem; max-width: 150px; line-height: 1.2;" title="{{ $failReason }}">
                                        <i class="bi bi-exclamation-circle-fill"></i> {{ Str::limit($failReason, 28) }}
                                    </div>
                                @elseif ($dropReason && in_array($order->payment_status, ['pending', 'processing'], true))
                                    <div class="text-warning small mt-1" style="font-size:0.72rem; line-height: 1.2;" title="{{ $dropReason }}">
                                        <i class="bi bi-box-arrow-right"></i> Quit Gateway
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-soft-{{ $orderClasses[$order->order_status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    @if ($custPhone)
                                        <form method="POST" action="{{ route('admin.orders.send-recovery-whatsapp', $order) }}" class="d-inline" data-confirm="Send WhatsApp recovery template via Meta API to {{ $custPhone }}?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success p-1 px-2" title="Send WhatsApp Recovery via Meta API">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($activeTab === 'pending')
                                        <form method="POST" action="{{ route('admin.orders.shipmojo.push', $order) }}" class="d-inline" data-confirm="Push order {{ $order->order_number }} to ShipMojo?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-dark p-1 px-2" title="Push to ShipMojo">
                                                <i class="bi bi-cloud-upload"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if (! in_array($order->order_status, ['cancelled', 'delivered'], true))
                                        <form method="POST" action="{{ route('admin.orders.bulk.cancel') }}" class="d-inline" data-confirm="Cancel order {{ $order->order_number }}? Stock will be restored and paid orders will receive a refund.">
                                            @csrf
                                            <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Cancel Order">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($activeTab === 'ready_to_ship')
                                        <a href="{{ route('admin.orders.shipmojo.label', $order) }}" class="btn btn-sm btn-outline-dark p-1 px-2" title="Download Shipping Label">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    @endif
                                    @if ($order->tracking_url)
                                        <a href="{{ $order->tracking_url }}" target="_blank" class="btn btn-sm btn-outline-primary p-1 px-2" title="Track Live Order">
                                            <i class="bi bi-geo-alt"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Order Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="Edit Order">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
                                <div class="empty-state py-5">
                                    <div class="empty-state-icon"><i class="bi bi-bag-x"></i></div>
                                    <div class="empty-state-title">No Orders Found</div>
                                    <p class="empty-state-desc">There are no orders matching your current filter in this pipeline tab.</p>
                                    <a href="{{ route('admin.orders.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @if ($orders->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} entries</span>
            <div>{{ $orders->links() }}</div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
window.handleDatePreset = function (select) {
    var fromCol = document.getElementById('dateFromCol');
    var toCol = document.getElementById('dateToCol');
    if (select.value === 'custom') {
        if (fromCol) fromCol.style.display = '';
        if (toCol) toCol.style.display = '';
    } else {
        if (fromCol) fromCol.style.display = 'none';
        if (toCol) toCol.style.display = 'none';
        select.form.submit();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('orderSearchInput');
    if (searchInput) {
        var timer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                searchInput.form.submit();
            }, 600);
        });
    }

    // Auto-submitting filter selects
    document.querySelectorAll('.js-auto-submit').forEach(function (select) {
        select.addEventListener('change', function () {
            if (this.form) this.form.submit();
        });
    });

    // Date preset change handler
    var datePresetSelect = document.getElementById('datePresetSelect');
    if (datePresetSelect) {
        datePresetSelect.addEventListener('change', function () {
            window.handleDatePreset(this);
        });
    }

    // Delegated click handler for bulk action buttons
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-bulk-btn');
        if (btn) {
            e.preventDefault();
            if (typeof window.bulkSubmit === 'function') {
                window.bulkSubmit(btn);
            }
        }
    });
});

@if ($tabBulkActions)
window.bulkSubmit = function (btn) {
    var checkedBoxes = document.querySelectorAll('.order-check:checked');
    if (!checkedBoxes.length) {
        if (window.vrAlert) {
            window.vrAlert('Please select at least one order using the checkboxes to perform this action.', {
                title: 'No Orders Selected',
                type: 'danger',
                okLabel: 'Got It'
            });
        } else {
            alert('Please select at least one order using the checkboxes to perform this action.');
        }
        return false;
    }

    var form = document.getElementById('bulk-form');
    if (!form) return false;

    var countText = checkedBoxes.length + ' order' + (checkedBoxes.length > 1 ? 's' : '');
    var confirmMsg = btn.dataset.confirm ? btn.dataset.confirm.replace('selected orders', countText) : 'Proceed with ' + countText + '?';

    window.vrConfirm(confirmMsg, {
        title: btn.textContent.trim(),
        okLabel: btn.dataset.ok || 'Yes, proceed',
        type: btn.dataset.type || 'primary'
    }).then(function (ok) {
        if (!ok) return;

        // Clear existing hidden inputs for order_ids
        form.querySelectorAll('input[name="order_ids[]"]').forEach(function (el) { el.remove(); });

        // Add checked order IDs
        checkedBoxes.forEach(function (cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });

        form.action = btn.dataset.action;

        if (btn.dataset.action === '{{ route('admin.orders.shipmojo.bulk.labels') }}') {
            submitLabels(form);
            return;
        }

        if (window.showPageLoader) window.showPageLoader();
        form.submit();
    });
    return false;
};

function submitLabels(form) {
    if (form.dataset.preventDouble !== undefined) return;
    form.dataset.preventDouble = '1';
    if (window.showPageLoader) window.showPageLoader();

    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route('admin.orders.shipmojo.bulk.labels') }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: new FormData(form)
    }).then(function (r) {
        var type = r.headers.get('Content-Type') || '';
        var cd = r.headers.get('Content-Disposition') || '';
        var filename = 'shipmojo-labels.zip';
        var m = /filename="?([^";]+)"?/.exec(cd);
        if (m) filename = m[1];

        if (type.indexOf('application/json') !== -1 || !r.ok) {
            return r.text().then(function (txt) { return { json: true, text: txt }; });
        }
        return r.blob().then(function (blob) { return { json: false, blob: blob, filename: filename }; });
    }).then(function (res) {
        if (res.json) {
            if (window.hidePageLoader) window.hidePageLoader();
            delete form.dataset.preventDouble;
            var msg = 'No labels were available for the selected orders.';
            try {
                var d = JSON.parse(res.text);
                if (d && d.message) msg = d.message;
            } catch (e) {}
            if (window.vrAlert) window.vrAlert(msg, { title: 'Download labels', type: 'danger' });
            return;
        }

        var url = URL.createObjectURL(res.blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = res.filename || 'shipmojo-labels.zip';
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        window.location.reload();
    }).catch(function () {
        window.location.reload();
    });
}

(function () {
    const toolbarAll = document.getElementById('select-all');
    const rowAll = document.querySelector('.row-select-all');
    const boxes = () => Array.from(document.querySelectorAll('.order-check'));
    const counter = document.getElementById('selected-count');

    const count = () => boxes().filter(b => b.checked).length;

    const floatingCounter = document.getElementById('floating-selected-count');
    const floatingBar = document.getElementById('bulkFloatingBar');

    const refresh = () => {
        const c = count();
        if (counter) counter.textContent = c;
        if (floatingCounter) floatingCounter.textContent = c;
        if (floatingBar) {
            if (c > 0) {
                floatingBar.classList.add('active');
            } else {
                floatingBar.classList.remove('active');
            }
        }
        if (toolbarAll) toolbarAll.checked = boxes().length > 0 && boxes().every(b => b.checked);
        if (rowAll) rowAll.checked = toolbarAll.checked;
    };

    boxes().forEach(b => b.addEventListener('change', refresh));

    const toggleAll = (checked) => {
        boxes().forEach(b => b.checked = checked);
        refresh();
    };

    if (rowAll) rowAll.addEventListener('change', e => toggleAll(e.target.checked));
    if (toolbarAll) toolbarAll.addEventListener('change', e => toggleAll(e.target.checked));
})();
@endif
</script>
@endpush