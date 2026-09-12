@php
    $statusColors = [
        'pending' => 'ps-pending', 'confirmed' => 'os-processing', 'processing' => 'os-processing',
        'packed' => 'os-processing', 'shipped' => 'os-shipped', 'out_for_delivery' => 'os-shipped',
        'delivered' => 'os-delivered', 'cancelled' => 'os-cancelled', 'failed' => 'os-cancelled',
    ];
@endphp

<div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 14px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3">Order</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td class="ps-3">
                            <span class="fw-semibold small">{{ $order->order_number }}</span>
                            <div class="small text-muted">{{ $order->payment_method }}</div>
                        </td>
                        <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                        <td class="small">{{ $order->items_count ?? $order->items_count }}</td>
                        <td class="small fw-semibold">{{ format_price($order->grand_total) }}</td>
                        <td>
                            <span class="vr-pill-badge {{ $statusColors[$order->order_status] ?? 'ps-pending' }}">
                                {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('account.order', $order) }}" class="btn btn-sm btn-vr-outline">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>