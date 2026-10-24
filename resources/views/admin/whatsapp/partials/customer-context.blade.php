@php
    $user = $customerContext['user'] ?? null;
    $orders = $customerContext['orders'] ?? collect();
    $totalSpent = $customerContext['total_spent'] ?? 0;
    $orderCount = $customerContext['order_count'] ?? 0;
    $aov = $orderCount > 0 ? ($totalSpent / $orderCount) : 0;
    $session = $customerContext['checkout_session'] ?? null;
@endphp

<div class="d-flex flex-column h-100">
    {{-- Authentic 56px WhatsApp Contact Info Header --}}
    <div class="px-3 border-bottom d-flex align-items-center justify-content-between flex-shrink-0" style="height: 56px; background-color: #f0f2f5; border-bottom: 1px solid #d1d7db !important;">
        <span class="fw-bold text-dark small d-flex align-items-center gap-2" style="font-size: 0.92rem;">
            <i class="bi bi-person-lines-fill text-success"></i> Contact Info
        </span>
        @if ($user)
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">Registered</span>
        @else
            <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.65rem;">Guest</span>
        @endif
    </div>

    <div class="p-3 overflow-y-auto flex-grow-1" style="background-color: #ffffff;">
        {{-- Profile Card --}}
        <div class="text-center pb-2.5 border-bottom mb-2.5">
            <div class="position-relative d-inline-block mb-1.5">
                <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto shadow-2xs"
                     style="background-color: {{ '#' . substr(md5($activeConversation->phone), 0, 6) }}; width: 52px; height: 52px; font-size: 1.25rem;">
                    {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                </div>
                @if ($activeConversation->isWindowOpen())
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1" title="24h Service Window Active"></span>
                @endif
            </div>

            <h6 class="fw-bold mb-0 text-dark text-truncate" style="font-size: 0.95rem;">{{ $activeConversation->contact_name ?: 'Guest Customer' }}</h6>
            <div class="d-flex align-items-center justify-content-center gap-1.5 text-muted small mt-0.5">
                <span style="font-size: 0.78rem;">+{{ $activeConversation->phone }}</span>
                <button type="button" class="btn btn-link btn-xs p-0 text-muted hover-dark text-decoration-none" title="Copy Phone Number" onclick="navigator.clipboard.writeText('+{{ $activeConversation->phone }}'); this.innerHTML='<i class=\'bi bi-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 1500);">
                    <i class="bi bi-copy" style="font-size: 0.7rem;"></i>
                </button>
            </div>
        </div>

        {{-- Lifetime Metrics (3 KPIs) --}}
        <div class="row g-1.5 mb-2.5">
            <div class="col-4">
                <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.6rem; letter-spacing: 0.02em;">Spent</span>
                    <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ format_price($totalSpent) }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.6rem; letter-spacing: 0.02em;">Orders</span>
                    <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ $orderCount }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2 rounded-3 bg-white border text-center shadow-2xs">
                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.6rem; letter-spacing: 0.02em;">AOV</span>
                    <span class="fw-bold text-dark" style="font-size: 0.8rem;">{{ format_price($aov) }}</span>
                </div>
            </div>
        </div>

        {{-- Active Checkout Session (if any) --}}
        @if ($session)
            <div class="card border border-warning-subtle rounded-3 bg-warning-subtle p-2 mb-2.5 shadow-2xs">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="fw-bold small text-warning-emphasis d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                        <i class="bi bi-cart3"></i> In-Progress Cart
                    </span>
                    <span class="badge bg-warning text-dark fw-semibold" style="font-size: 0.62rem;">
                        {{ str_replace('_', ' ', strtoupper($session->status)) }}
                    </span>
                </div>
                <div class="small text-secondary mb-1" style="font-size: 0.75rem;">
                    Value: <strong class="text-dark">{{ format_price($session->grand_total) }}</strong>
                    @if ($session->cart_data['items'] ?? null)
                        <span class="text-muted">({{ count($session->cart_data['items']) }} item{{ count($session->cart_data['items']) !== 1 ? 's' : '' }})</span>
                    @endif
                </div>
                @if (!empty($session->cart_data['items']))
                    <ul class="list-unstyled mb-0 small text-muted bg-white rounded-2 p-1.5 border border-warning-subtle" style="font-size: 0.7rem;">
                        @foreach ($session->cart_data['items'] as $item)
                            <li class="text-truncate py-0.5">• {{ $item['name'] }} <span class="fw-semibold text-dark">× {{ $item['quantity'] }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- Recent Orders --}}
        <div class="mb-2.5">
            <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="fw-bold small text-dark d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                    <i class="bi bi-bag-check text-success"></i> Recent Orders
                </span>
                @if ($user)
                    <a href="{{ route('admin.orders.index', ['search' => $activeConversation->phone]) }}" class="small text-decoration-none text-success fw-semibold" style="font-size: 0.72rem;">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>

            @forelse ($orders->take(3) as $order)
                <div class="p-2 rounded-3 bg-white border mb-1.5 shadow-2xs hover-shadow-xs transition-all">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold text-dark text-decoration-none small hover-underline d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                            <span>#{{ $order->order_number }}</span>
                            <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 0.6rem;"></i>
                        </a>
                        <span class="fw-bold text-success small" style="font-size: 0.75rem;">{{ format_price($order->grand_total) }}</span>
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
                        <span class="badge {{ $orderStatusBadge }} rounded-pill" style="font-size: 0.62rem; padding: 0.15rem 0.45rem;">
                            {{ ucfirst($order->order_status) }}
                        </span>
                        <span class="text-muted" style="font-size: 0.68rem;">
                            {{ $order->created_at->format('d M, Y') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-2.5 bg-white border rounded-3 text-center text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-bag text-muted opacity-50 d-block fs-5 mb-0.5"></i>
                    No previous store orders found.
                </div>
            @endforelse
        </div>

        {{-- Quick Action Triggers --}}
        <div>
            <span class="fw-bold small text-dark d-block mb-1.5" style="font-size: 0.78rem;">Quick Actions</span>
            <div class="d-flex flex-column gap-1">
                @if ($user)
                    <a href="{{ route('admin.customers.show', $user->id) }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-1.5 shadow-2xs hover-bg-light" style="font-size: 0.75rem;">
                        <i class="bi bi-person-lines-fill text-primary"></i>
                        <span class="fw-semibold text-dark">Open Customer Profile</span>
                    </a>
                @endif
                <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-1.5 shadow-2xs hover-bg-light" style="font-size: 0.75rem;">
                    <i class="bi bi-telephone-outbound text-secondary"></i>
                    <span class="fw-semibold text-dark">Direct Voice Call</span>
                </a>
                <a href="https://web.whatsapp.com/send?phone={{ $activeConversation->phone }}" target="_blank" class="btn btn-sm btn-white border text-start d-flex align-items-center gap-2 rounded-3 py-1.5 shadow-2xs hover-bg-light" style="font-size: 0.75rem;">
                    <i class="bi bi-box-arrow-up-right text-success"></i>
                    <span class="fw-semibold text-dark">Open in WhatsApp Web</span>
                </a>
            </div>
        </div>
    </div>
</div>
