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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Orders</h5>
        <small class="text-muted">{{ $orders->total() }} orders in "{{ $tabs[$activeTab] }}"</small>
    </div>
</div>

{{-- Tab navigation --}}
<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto">
    @foreach ($tabs as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === $key ? 'active fw-semibold' : '' }}"
               href="{{ route('admin.orders.index', array_merge(request()->except('tab', 'page'), ['tab' => $key])) }}">
                {{ $label }}
                <span class="badge {{ $activeTab === $key ? 'text-bg-primary' : 'text-bg-secondary' }} ms-1">{{ $tabCounts[$key] }}</span>
            </a>
        </li>
    @endforeach
</ul>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search order #, email, name..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <select name="order_status" class="form-select form-select-sm">
                    <option value="">All Order Status</option>
                    @foreach ($orderStatuses as $status)
                        <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Payment Status</option>
                    @foreach ($paymentStatuses as $status)
                        <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.orders.index', ['tab' => $activeTab]) }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($tabBulkActions)
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="select-all">
            <label class="form-check-label small" for="select-all">Select all on page</label>
        </div>
        <span class="small text-muted"><span id="selected-count">0</span> selected</span>
        @foreach ($tabBulkActions as $action)
            <button type="submit" form="bulk-form" class="btn btn-sm btn-{{ $action['btn'] }}"
                data-action="{{ $action['route'] }}" data-confirm="{{ $action['confirm'] }}"
                data-ok="{{ $action['ok'] ?? 'Yes, proceed' }}" data-type="{{ $action['type'] ?? 'danger' }}"
                onclick="return bulkSubmit(this)">
                <i class="bi {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
            </button>
        @endforeach
        <small class="text-muted ms-auto">{{ $tabCounts[$activeTab] }} order(s) in this tab</small>
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
                            <th style="width:36px;"><input type="checkbox" class="form-check-input row-select-all"></th>
                        @endif
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        @if (in_array($activeTab, ['ready_to_ship', 'assign_courier', 'shipped'], true))
                            <th>Courier</th>
                            <th>AWB</th>
                        @endif
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $shipment = $order->shipments->sortByDesc('created_at')->first(); @endphp
                        <tr>
                            @if ($tabBulkActions)
                                <td><input type="checkbox" class="form-check-input order-check" name="order_ids[]" value="{{ $order->id }}"></td>
                            @endif
                            <td class="small text-primary fw-semibold">{{ $order->order_number }}</td>
                            <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="fw-semibold small">{{ $order->user?->name ?: $order->billing_name }}</div>
                                <div class="text-muted small">{{ $order->user?->email }}</div>
                            </td>
                            <td class="text-center small">{{ $order->items_count }}</td>
                            <td class="text-end fw-semibold small">{{ format_price($order->grand_total) }}</td>
                            @if (in_array($activeTab, ['ready_to_ship', 'assign_courier', 'shipped'], true))
                                <td class="small">{{ $shipment?->courier ?: 'â€”' }}</td>
                                <td class="small">{{ $shipment?->awb_number ?: 'â€”' }}</td>
                            @endif
                            <td>
                                <span class="badge bg-{{ $paymentClasses[$order->payment_status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $orderClasses[$order->order_status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if ($activeTab === 'ready_to_ship')
                                        <a href="{{ route('admin.orders.shipmojo.label', $order) }}" class="btn btn-sm btn-outline-dark" title="Download Label"><i class="bi bi-printer"></i></a>
                                    @endif
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    <div class="card-footer">
        {{ $orders->links() }}
    </div>
</div>
@endsection

@push('scripts')
@if ($tabBulkActions)
<script>
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

    const refresh = () => {
        counter.textContent = count();
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