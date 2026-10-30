@php
    $user = $customerContext['user'] ?? null;
    $orders = $customerContext['orders'] ?? collect();
    $totalSpent = $customerContext['total_spent'] ?? 0;
    $orderCount = $customerContext['order_count'] ?? 0;
    $aov = $orderCount > 0 ? ($totalSpent / $orderCount) : 0;
    $session = $customerContext['checkout_session'] ?? null;
@endphp

<div class="d-flex flex-column h-100 bg-white">
    {{-- Clean Header --}}
    <div class="px-3.5 py-3 border-bottom d-flex align-items-center justify-content-between flex-shrink-0" style="background-color: #ffffff; border-color: #eef2f5 !important; min-height: 56px;">
        <span class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem;">
            <i class="bi bi-person-fill text-dark" style="font-size: 1.05rem;"></i> Contact Info
        </span>
        @if ($user)
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 500;">Registered</span>
        @else
            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem; font-weight: 500;">Guest</span>
        @endif
    </div>

    <div class="p-3.5 overflow-y-auto flex-grow-1" style="background-color: #ffffff;">
        {{-- Profile Center --}}
        <div class="text-center pb-3 mb-3">
            <div class="position-relative d-inline-block mb-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold mx-auto shadow-2xs"
                     style="background-color: #2563eb; width: 68px; height: 68px; font-size: 1.6rem; letter-spacing: -0.02em;">
                    {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                </div>
                @if ($activeConversation->isWindowOpen())
                    <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1" style="width: 14px; height: 14px;" title="24h Service Window Active"></span>
                @endif
            </div>

            <h5 class="fw-bold mb-1 text-dark text-truncate" style="font-size: 1.05rem; letter-spacing: -0.01em;">
                {{ $activeConversation->contact_name ?: 'Guest Customer' }}
            </h5>
            <div class="d-flex align-items-center justify-content-center gap-1.5 text-muted small">
                <span style="font-size: 0.85rem;">+{{ $activeConversation->phone }}</span>
                <button type="button" class="btn btn-link btn-xs p-0 text-muted hover-dark text-decoration-none" title="Copy Phone Number" onclick="navigator.clipboard.writeText('+{{ $activeConversation->phone }}'); this.innerHTML='<i class=\'bi bi-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-copy\'></i>', 1500);">
                    <i class="bi bi-copy" style="font-size: 0.75rem;"></i>
                </button>
            </div>
        </div>

        {{-- 3 Lifetime Metrics KPI Tiles --}}
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="p-2.5 rounded-3 border text-center" style="background-color: #fafbfc; border-color: #eef2f5 !important;">
                    <span class="text-muted text-uppercase fw-semibold d-flex align-items-center justify-content-center gap-1 mb-1" style="font-size: 0.65rem; letter-spacing: 0.02em;">
                        <i class="bi bi-cart2"></i> Spent
                    </span>
                    <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ format_price($totalSpent) }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2.5 rounded-3 border text-center" style="background-color: #fafbfc; border-color: #eef2f5 !important;">
                    <span class="text-muted text-uppercase fw-semibold d-flex align-items-center justify-content-center gap-1 mb-1" style="font-size: 0.65rem; letter-spacing: 0.02em;">
                        <i class="bi bi-bag"></i> Orders
                    </span>
                    <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ $orderCount }}</span>
                </div>
            </div>
            <div class="col-4">
                <div class="p-2.5 rounded-3 border text-center" style="background-color: #fafbfc; border-color: #eef2f5 !important;">
                    <span class="text-muted text-uppercase fw-semibold d-flex align-items-center justify-content-center gap-1 mb-1" style="font-size: 0.65rem; letter-spacing: 0.02em;">
                        <i class="bi bi-graph-up"></i> AOV
                    </span>
                    <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ format_price($aov) }}</span>
                </div>
            </div>
        </div>

        {{-- Active Checkout Session (if any) --}}
        @if ($session)
            <div class="card border rounded-3 p-2.5 mb-3 shadow-2xs" style="background-color: #fffdf0; border-color: #fef08a !important;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="fw-bold small text-warning-emphasis d-flex align-items-center gap-1" style="font-size: 0.74rem;">
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
                    <ul class="list-unstyled mb-0 small text-muted bg-white rounded-2 p-1.5 border" style="font-size: 0.7rem; border-color: #fef08a !important;">
                        @foreach ($session->cart_data['items'] as $item)
                            <li class="text-truncate py-0.5">• {{ $item['name'] }} <span class="fw-semibold text-dark">× {{ $item['quantity'] }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        {{-- Recent Orders Section --}}
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark" style="font-size: 0.88rem;">Recent Orders</span>
                <a href="{{ $user ? route('admin.orders.index', ['search' => $activeConversation->phone]) : '#' }}" class="small text-decoration-none fw-semibold" style="font-size: 0.78rem; color: #0284c7;">
                    View All &gt;
                </a>
            </div>

            @forelse ($orders->take(3) as $order)
                <div class="p-2.5 rounded-3 border mb-2 shadow-2xs hover-shadow-xs transition-all" style="background-color: #ffffff; border-color: #eef2f5 !important;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold text-dark text-decoration-none small hover-underline d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                            <span>#{{ $order->order_number }}</span>
                            <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 0.65rem;"></i>
                        </a>
                        <span class="fw-bold text-success small" style="font-size: 0.78rem;">{{ format_price($order->grand_total) }}</span>
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
                        <span class="badge {{ $orderStatusBadge }} rounded-pill" style="font-size: 0.64rem; padding: 0.15rem 0.5rem;">
                            {{ ucfirst($order->order_status) }}
                        </span>
                        <span class="text-muted" style="font-size: 0.7rem;">
                            {{ $order->created_at->format('d M, Y') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-4 rounded-3 text-center text-muted border" style="background-color: #ffffff; border-color: #eef2f5 !important; font-size: 0.8rem;">
                    <i class="bi bi-bag text-muted opacity-50 d-block fs-3 mb-1"></i>
                    No previous store orders found.
                </div>
            @endforelse
        </div>

        {{-- Quick Actions List --}}
        <div>
            <span class="fw-bold text-dark d-block mb-2" style="font-size: 0.88rem;">Quick Actions</span>
            <div class="d-flex flex-column gap-1.5">
                <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" style="font-size: 0.82rem; border-color: #eef2f5 !important;">
                    <i class="bi bi-telephone text-dark" style="font-size: 0.95rem;"></i>
                    <span class="fw-medium text-dark">Direct Voice Call</span>
                </a>
                <a href="https://web.whatsapp.com/send?phone={{ $activeConversation->phone }}" target="_blank" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" style="font-size: 0.82rem; border-color: #eef2f5 !important;">
                    <i class="bi bi-box-arrow-up-right text-dark" style="font-size: 0.95rem;"></i>
                    <span class="fw-medium text-dark">Open in WhatsApp Web</span>
                </a>
                <button type="button" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" onclick="document.getElementById('btnAssignDropdown')?.click();" style="font-size: 0.82rem; border-color: #eef2f5 !important;">
                    <i class="bi bi-person text-dark" style="font-size: 0.95rem;"></i>
                    <span class="fw-medium text-dark">Assign to Team Member</span>
                </button>
                <button type="button" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" onclick="document.getElementById('btnModeNote')?.click(); document.getElementById('waMessageInput')?.focus();" style="font-size: 0.82rem; border-color: #eef2f5 !important;">
                    <i class="bi bi-pencil-square text-dark" style="font-size: 0.95rem;"></i>
                    <span class="fw-medium text-dark">Add Internal Note</span>
                </button>
                <button type="button" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" onclick="if(confirm('Are you sure you want to block this customer from WhatsApp interactions?')) { document.querySelector('[data-status=resolved]')?.click(); alert('Customer marked resolved.'); }" style="font-size: 0.82rem; border-color: #fee2e2 !important; background-color: #fffbfa;">
                    <i class="bi bi-slash-circle text-danger" style="font-size: 0.95rem;"></i>
                    <span class="fw-medium text-danger">Block Customer</span>
                </button>
                <button type="button" class="btn btn-white border text-start d-flex align-items-center gap-2.5 rounded-3 py-2 px-3 shadow-2xs hover-bg-light" data-bs-toggle="modal" data-bs-target="#deleteChatModal" style="font-size: 0.82rem; border-color: #fecaca !important; background-color: #fef2f2;">
                    <i class="bi bi-trash3 text-danger" style="font-size: 0.95rem;"></i>
                    <span class="fw-bold text-danger">Delete Chat & Purge Data</span>
                </button>
            </div>
        </div>
    </div>
</div>
