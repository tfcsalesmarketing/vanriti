@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
@php
    $bulkActions = [
        'pending' => [
            ['route' => route('admin.orders.shipmojo.bulk.push'), 'label' => 'Push to ShipMojo', 'btn' => 'dark', 'icon' => 'bi-upload', 'type' => 'primary', 'ok' => 'Push', 'confirm' => 'Push selected orders to ShipMojo?'],
            ['route' => route('admin.orders.bulk.cancel'), 'label' => 'Cancel Selected', 'btn' => 'outline-danger', 'icon' => 'bi-x-circle', 'type' => 'danger', 'ok' => 'Cancel Orders', 'confirm' => 'Cancel selected orders? Stock will be restored and paid orders will receive a refund.'],
        ],
        'assign_courier' => [
            ['route' => route('admin.orders.shipmojo.bulk.auto-assign'), 'label' => 'Auto-Assign Courier', 'btn' => 'dark', 'icon' => 'bi-check2-all', 'type' => 'primary', 'ok' => 'Assign', 'confirm' => 'Auto-assign courier for selected orders?'],
        ],
        'ready_to_ship' => [
            ['route' => route('admin.orders.shipmojo.bulk.schedule-pickup'), 'label' => 'Schedule Pickup', 'btn' => 'dark', 'icon' => 'bi-truck', 'type' => 'primary', 'ok' => 'Schedule', 'confirm' => 'Schedule pickup for selected orders?'],
            ['route' => route('admin.orders.shipmojo.bulk.labels'), 'label' => 'Download Labels (ZIP)', 'btn' => 'dark', 'icon' => 'bi-printer', 'type' => 'primary', 'ok' => 'Download', 'confirm' => 'Download shipping labels for selected orders?'],
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
        <a href="{{ route('admin.orders.index', ['tab' => 'pending']) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-clock-history"></i><span>Pending ({{ $tabCounts['pending'] ?? 0 }})</span>
        </a>
        <a href="{{ route('admin.orders.index', ['tab' => 'ready_to_ship']) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-seam"></i><span>Ready to Ship ({{ $tabCounts['ready_to_ship'] ?? 0 }})</span>
        </a>
    </div>
</div>

{{-- Modern Segmented Status Tabs --}}
<div class="card mb-3 border-0 bg-transparent">
    <ul class="nav nav-tabs flex-nowrap overflow-auto pb-1" style="border-bottom: 2px solid var(--vanriti-border);">
        @foreach ($tabs as $key => $label)
            @php
                $isActive = $activeTab === $key;
                $count = $tabCounts[$key] ?? 0;
            @endphp
            <li class="nav-item">
                <a class="nav-link {{ $isActive ? 'active fw-bold' : '' }}"
                   href="{{ route('admin.orders.index', array_merge(request()->except('tab', 'page'), ['tab' => $key])) }}"
                   style="white-space: nowrap;">
                    {{ $label }}
                    <span class="badge {{ $isActive ? 'bg-success text-white' : 'bg-light text-muted border' }} ms-1.5" style="font-size:0.72rem;">
                        {{ $count }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

{{-- Streamlined Filter Bar --}}
<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-center">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="col-12 col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Order #, email, phone, name..." value="{{ request('q') }}">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <select name="order_status" class="form-select form-select-sm">
                <option value="">All Order Status</option>
                @foreach ($orderStatuses as $status)
                    <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="payment_status" class="form-select form-select-sm">
                <option value="">All Payment Status</option>
                @foreach ($paymentStatuses as $status)
                    <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <input type="date" name="date_from" class="form-control form-control-sm" title="From Date" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-md-2">
            <input type="date" name="date_to" class="form-control form-control-sm" title="To Date" value="{{ request('date_to') }}">
        </div>
        <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Apply Filters"><i class="bi bi-funnel"></i></button>
            <a href="{{ route('admin.orders.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
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
                <button type="submit" form="bulk-form" class="btn btn-sm btn-{{ $action['btn'] }}"
                    data-action="{{ $action['route'] }}" data-confirm="{{ $action['confirm'] }}"
                    data-ok="{{ $action['ok'] ?? 'Yes, proceed' }}" data-type="{{ $action['type'] ?? 'danger' }}"
                    onclick="return bulkSubmit(this)">
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
            <button type="submit" form="bulk-form" class="btn btn-sm btn-light text-dark fw-semibold"
                data-action="{{ $action['route'] }}" data-confirm="{{ $action['confirm'] }}"
                data-ok="{{ $action['ok'] ?? 'Yes, proceed' }}" data-type="{{ $action['type'] ?? 'danger' }}"
                onclick="return bulkSubmit(this)">
                <i class="bi {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
            </button>
        @endforeach
    </div>
</div>
@endif

<div class="card table-card">
    <form method="POST" id="bulk-form" action="#" @if (! $tabBulkActions) data-disabled @endif>
        @csrf
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
                        @if (in_array($activeTab, ['ready_to_ship', 'assign_courier', 'shipped'], true))
                            <th>Courier Partner</th>
                            <th>AWB Number</th>
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
                                <div class="fw-semibold small text-dark">{{ $order->user?->name ?: $order->billing_name }}</div>
                                <div class="text-muted small" style="font-size:0.75rem;">{{ $order->user?->email ?: 'No email' }}</div>
                                @php
                                    $custPhone = trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: $order->user?->phone)));
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $custPhone);
                                    if (strlen($cleanPhone) === 10) {
                                        $cleanPhone = '91' . $cleanPhone;
                                    }
                                    $recoveryMsg = "Hi " . ($order->user?->name ?: $order->billing_name) . ", we noticed your order #" . $order->order_number . " at Vanriti was not completed. You can complete your order here: " . route('account.order', $order) . " - Let us know if you need any assistance!";
                                    $waUrl = $cleanPhone ? "https://wa.me/{$cleanPhone}?text=" . rawurlencode($recoveryMsg) : null;
                                    $latestPayment = $order->payments->sortByDesc('created_at')->first();
                                    $failReason = $latestPayment?->gateway_response['failure_reason'] ?? $order->cancellation_reason;
                                    $dropReason = $latestPayment?->gateway_response['dropoff_reason'] ?? null;
                                @endphp
                                @if ($custPhone)
                                    <div class="text-muted small" style="font-size:0.75rem;">
                                        <i class="bi bi-telephone me-1 text-muted"></i>{{ $custPhone }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center small"><span class="badge bg-light text-dark border">{{ $order->items_count }}</span></td>
                            <td class="text-end fw-bold small text-dark">{{ format_price($order->grand_total) }}</td>
                            @if (in_array($activeTab, ['ready_to_ship', 'assign_courier', 'shipped'], true))
                                <td class="small fw-semibold text-secondary">{{ $shipment?->courier ?: '—' }}</td>
                                <td class="small font-monospace" style="font-size:0.78rem;">{{ $shipment?->awb_number ?: '—' }}</td>
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
                                        <form method="POST" action="{{ route('admin.orders.send-recovery-whatsapp', $order) }}" class="d-inline" onsubmit="return confirm('Send WhatsApp recovery template via Meta API to {{ $custPhone }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success p-1 px-2" title="Send WhatsApp Recovery via Meta API">
                                                <i class="bi bi-whatsapp"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($activeTab === 'ready_to_ship')
                                        <a href="{{ route('admin.orders.shipmojo.label', $order) }}" class="btn btn-sm btn-outline-dark p-1 px-2" title="Download Shipping Label">
                                            <i class="bi bi-printer"></i>
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
    </form>
    @if ($orders->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} entries</span>
            <div>{{ $orders->links() }}</div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@if ($tabBulkActions)
<script nonce="{{ $cspNonce }}">
window.bulkSubmit = function (btn) {
    var form = document.getElementById('bulk-form');
    window.vrConfirm(btn.dataset.confirm, {
        okLabel: btn.dataset.ok,
        type: btn.dataset.type
    }).then(function (ok) {
        if (!ok) return;
        if (btn.dataset.action === '{{ route('admin.orders.shipmojo.bulk.labels') }}') {
            submitLabels(form);
            return;
        }
        form.action = btn.dataset.action;
        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
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
    const bulkForm = document.getElementById('bulk-form');
    const toolbarAll = document.getElementById('select-all');
    const rowAll = document.querySelector('.row-select-all');
    const boxes = () => Array.from(bulkForm.querySelectorAll('.order-check'));
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
</script>
@endpush
@endif