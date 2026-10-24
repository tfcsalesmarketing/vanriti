@php
    $user = $customerContext['user'] ?? null;
    $orders = $customerContext['orders'] ?? collect();
    $totalSpent = $customerContext['total_spent'] ?? 0;
    $orderCount = $customerContext['order_count'] ?? 0;
    $session = $customerContext['checkout_session'] ?? null;
@endphp

<div class="p-3">
    {{-- Header Profile Card --}}
    <div class="text-center pb-3 border-bottom mb-3">
        <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto mb-2"
             style="background-color: {{ '#' . substr(md5($activeConversation->phone), 0, 6) }}; width: 56px; height: 56px; font-size: 1.35rem;">
            {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
        </div>
        <h6 class="fw-bold mb-0 text-dark">{{ $activeConversation->contact_name ?: 'Guest Customer' }}</h6>
        <div class="text-muted small mt-0.5">+{{ $activeConversation->phone }}</div>

        <div class="d-flex align-items-center justify-content-center gap-1.5 mt-2">
            @if ($user)
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-person-check-fill me-1"></i>Registered User
                </span>
            @else
                <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-person me-1"></i>Guest (WhatsApp)
                </span>
            @endif
        </div>
    </div>

    {{-- Lifetime Metrics --}}
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-white border text-center">
                <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.65rem;">Total Spent</span>
                <span class="fw-bold text-dark fs-6">{{ format_price($totalSpent) }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2.5 rounded-3 bg-white border text-center">
                <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.65rem;">Orders</span>
                <span class="fw-bold text-dark fs-6">{{ $orderCount }}</span>
            </div>
        </div>
    </div>

    {{-- Active Checkout Session (if any) --}}
    @if ($session)
        <div class="card border border-warning-subtle rounded-3 bg-warning-subtle p-2.5 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="fw-bold small text-warning-emphasis d-flex align-items-center gap-1">
                    <i class="bi bi-cart3"></i> In-Progress Cart
                </span>
                <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">
                    {{ str_replace('_', ' ', strtoupper($session->status)) }}
                </span>
            </div>
            <div class="small text-secondary mb-1">
                Value: <strong>{{ format_price($session->grand_total) }}</strong>
                @if ($session->cart_data['items'] ?? null)
                    ({{ count($session->cart_data['items']) }} item{{ count($session->cart_data['items']) !== 1 ? 's' : '' }})
                @endif
            </div>
            @if (!empty($session->cart_data['items']))
                <ul class="list-unstyled mb-0 small text-muted" style="font-size: 0.75rem;">
                    @foreach ($session->cart_data['items'] as $item)
                        <li class="text-truncate">• {{ $item['name'] }} × {{ $item['quantity'] }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Recent Orders --}}
    <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold small text-dark">Recent Orders</span>
            @if ($user)
                <a href="{{ route('admin.orders.index', ['search' => $activeConversation->phone]) }}" class="small text-decoration-none text-success" style="font-size: 0.75rem;">
                    View All
                </a>
            @endif
        </div>

        @forelse ($orders as $order)
            <div class="p-2.5 rounded-3 bg-white border mb-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold text-dark text-decoration-none small hover-underline">
                        #{{ $order->order_number }}
                    </a>
                    <span class="fw-bold text-success small">{{ format_price($order->grand_total) }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge {{ $order->order_status === 'confirmed' ? 'bg-success-subtle text-success' : ($order->order_status === 'cancelled' ? 'bg-danger-subtle text-danger' : 'bg-light text-muted') }}" style="font-size: 0.65rem;">
                        {{ ucfirst($order->order_status) }}
                    </span>
                    <span class="text-muted" style="font-size: 0.7rem;">
                        {{ $order->created_at->format('d M, Y') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="p-3 bg-white border rounded-3 text-center text-muted small">
                No previous store orders found.
            </div>
        @endforelse
    </div>

    {{-- Quick Action Triggers --}}
    <div>
        <span class="fw-bold small text-dark d-block mb-2">Quick Actions</span>
        <div class="d-flex flex-column gap-1.5">
            @if ($user)
                <a href="{{ route('admin.customers.show', $user->id) }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-muted"></i>
                    <span>Open Customer Profile</span>
                </a>
            @endif
            <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2">
                <i class="bi bi-telephone-outbound text-muted"></i>
                <span>Direct Voice Call</span>
            </a>
            <a href="https://web.whatsapp.com/send?phone={{ $activeConversation->phone }}" target="_blank" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-up-right text-success"></i>
                <span>Open in WhatsApp Web</span>
            </a>
        </div>
    </div>
</div>
