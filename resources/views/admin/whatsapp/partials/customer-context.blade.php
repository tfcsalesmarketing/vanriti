@php
    $user = $customerContext['user'] ?? null;
    $orders = $customerContext['orders'] ?? collect();
    $totalSpent = $customerContext['total_spent'] ?? 0;
    $orderCount = $customerContext['order_count'] ?? 0;
    $aov = $orderCount > 0 ? ($totalSpent / $orderCount) : 0;
    $session = $customerContext['checkout_session'] ?? null;
@endphp

<div class="p-3.5">
    {{-- Header Profile Card --}}
    <div class="text-center pb-3 border-bottom mb-3">
        <div class="position-relative d-inline-block mb-2">
            <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto shadow-sm"
                 style="background-color: {{ '#' . substr(md5($activeConversation->phone), 0, 6) }}; width: 62px; height: 62px; font-size: 1.45rem;">
                {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
            </div>
            @if ($activeConversation->isWindowOpen())
                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1.5" title="24h Service Window Active"></span>
            @endif
        </div>

        <h6 class="fw-bold mb-0.5 text-dark">{{ $activeConversation->contact_name ?: 'Guest Customer' }}</h6>
        <div class="d-flex align-items-center justify-content-center gap-1.5 text-muted small">
            <span>+{{ $activeConversation->phone }}</span>
            <button type="button" class="btn btn-link btn-xs p-0 text-muted hover-dark" title="Copy Phone Number" onclick="navigator.clipboard.writeText('+{{ $activeConversation->phone }}'); this.innerHTML='<i class=\'bi bi-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 1500);">
                <i class="bi bi-copy"></i>
            </button>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-1.5 mt-2">
            @if ($user)
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-person-check-fill me-1"></i>Registered Customer
                </span>
            @else
                <span class="badge bg-white text-muted border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-person me-1"></i>Guest (WhatsApp)
                </span>
            @endif
        </div>
    </div>

    {{-- Lifetime Metrics (3 KPIs) --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.62rem;">Spent</span>
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">{{ format_price($totalSpent) }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.62rem;">Orders</span>
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $orderCount }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.62rem;">AOV</span>
                <span class="fw-bold text-dark" style="font-size: 0.85rem;">{{ format_price($aov) }}</span>
            </div>
        </div>
    </div>

    {{-- Active Checkout Session (if any) --}}
    @if ($session)
        <div class="card border border-warning-subtle rounded-3 bg-warning-subtle p-2.5 mb-3 shadow-2xs">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="fw-bold small text-warning-emphasis d-flex align-items-center gap-1.5">
                    <i class="bi bi-cart3"></i> In-Progress Cart
                </span>
                <span class="badge bg-warning text-dark fw-semibold" style="font-size: 0.65rem;">
                    {{ str_replace('_', ' ', strtoupper($session->status)) }}
                </span>
            </div>
            <div class="small text-secondary mb-1.5">
                Value: <strong class="text-dark">{{ format_price($session->grand_total) }}</strong>
                @if ($session->cart_data['items'] ?? null)
                    <span class="text-muted">({{ count($session->cart_data['items']) }} item{{ count($session->cart_data['items']) !== 1 ? 's' : '' }})</span>
                @endif
            </div>
            @if (!empty($session->cart_data['items']))
                <ul class="list-unstyled mb-0 small text-muted bg-white rounded-2 p-2 border border-warning-subtle" style="font-size: 0.75rem;">
                    @foreach ($session->cart_data['items'] as $item)
                        <li class="text-truncate py-0.5">• {{ $item['name'] }} <span class="fw-semibold text-dark">× {{ $item['quantity'] }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Recent Orders --}}
    <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold small text-dark d-flex align-items-center gap-1.5">
                <i class="bi bi-bag-check text-success"></i> Recent Orders
            </span>
            @if ($user)
                <a href="{{ route('admin.orders.index', ['search' => $activeConversation->phone]) }}" class="small text-decoration-none text-success fw-semibold" style="font-size: 0.75rem;">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            @endif
        </div>

        @forelse ($orders as $order)
            <div class="p-2.5 rounded-3 bg-white border mb-2 shadow-2xs hover-shadow-xs transition-all">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold text-dark text-decoration-none small hover-underline d-flex align-items-center gap-1">
                        <span>#{{ $order->order_number }}</span>
                        <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 0.65rem;"></i>
                    </a>
                    <span class="fw-bold text-success small">{{ format_price($order->grand_total) }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    @php
                        $orderStatusBadge = match($order->order_status) {
                            'confirmed' => 'bg-success-subtle text-success border border-success-subtle',
                            'delivered' => 'bg-primary-subtle text-primary border border-primary-subtle',
                            'shipped' => 'bg-info-subtle text-info border border-info-subtle',
                            'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                            default => 'bg-light text-muted border'
                        };
                    @endphp
                    <span class="badge {{ $orderStatusBadge }} rounded-pill" style="font-size: 0.65rem; padding: 0.2rem 0.5rem;">
                        {{ ucfirst($order->order_status) }}
                    </span>
                    <span class="text-muted" style="font-size: 0.7rem;">
                        {{ $order->created_at->format('d M, Y') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="p-3 bg-white border rounded-3 text-center text-muted small">
                <i class="bi bi-bag-x text-muted opacity-50 d-block fs-4 mb-1"></i>
                No previous store orders found.
            </div>
        @endforelse
    </div>

    {{-- Quick Action Triggers --}}
    <div>
        <span class="fw-bold small text-dark d-block mb-2">Quick Actions</span>
        <div class="d-flex flex-column gap-1.5">
            @if ($user)
                <a href="{{ route('admin.customers.show', $user->id) }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-2 shadow-2xs hover-bg-light">
                    <i class="bi bi-person-lines-fill text-primary"></i>
                    <span class="small fw-semibold text-dark">Open Customer Profile</span>
                </a>
            @endif
            <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-2 shadow-2xs hover-bg-light">
                <i class="bi bi-telephone-outbound text-secondary"></i>
                <span class="small fw-semibold text-dark">Direct Voice Call</span>
            </a>
            <a href="https://web.whatsapp.com/send?phone={{ $activeConversation->phone }}" target="_blank" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-2 shadow-2xs hover-bg-light">
                <i class="bi bi-box-arrow-up-right text-success"></i>
                <span class="small fw-semibold text-dark">Open in WhatsApp Web</span>
            </a>
        </div>
    </div>
</div>
