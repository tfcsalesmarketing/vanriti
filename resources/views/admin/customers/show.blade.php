@extends('admin.layouts.app')

@section('title', 'Customer 360° Profile: ' . $user->name)

@section('content')
{{-- Header Bar --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="mb-0 fw-bold d-inline-flex align-items-center gap-2">
                <span>{{ $user->name }}</span>
                <span class="badge bg-{{ $stats['segment']['badge'] }}-subtle text-{{ $stats['segment']['badge'] }} border border-{{ $stats['segment']['badge'] }}-subtle rounded-pill small py-1 px-2.5" style="font-size:0.75rem;">
                    <i class="bi {{ $stats['segment']['icon'] }} me-1"></i>{{ $stats['segment']['label'] }}
                </span>
                <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }}" style="font-size:0.75rem;">
                    {{ ucfirst($user->status) }}
                </span>
            </h5>
            <small class="text-muted">
                Customer ID: #{{ $user->id }} &middot; Member since {{ $user->created_at->format('d M Y') }} ({{ $user->created_at->diffForHumans() }})
            </small>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if ($user->phone)
            <a href="{{ route('admin.whatsapp.inbox', ['user_id' => $user->id]) }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-whatsapp"></i><span>Open in WhatsApp Inbox</span>
            </a>
            <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#sendCustomerWhatsAppModal">
                <i class="bi bi-send"></i><span>Send WhatsApp Alert</span>
            </button>
        @endif
        <form method="POST" action="{{ route('admin.customers.toggle', $user->id) }}" class="d-inline" data-confirm="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }} this customer?">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-{{ $user->status === 'active' ? 'warning' : 'success' }} d-inline-flex align-items-center gap-1">
                <i class="bi {{ $user->status === 'active' ? 'bi-person-x' : 'bi-person-check' }}"></i>
                <span>{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}</span>
            </button>
        </form>
    </div>
</div>

{{-- 360° Metrics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Lifetime Value (CLV)</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-currency-rupee fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-dark">{{ format_price($stats['total_spent']) }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">
                    Avg Order: <strong class="text-dark">{{ format_price($stats['average_order_value']) }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Orders Summary</span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-2"><i class="bi bi-bag-check fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1 text-primary">{{ $stats['total_orders'] }}</div>
                <div class="small text-muted" style="font-size:0.75rem;">
                    <span class="text-success"><i class="bi bi-check2-circle me-0.5"></i>{{ $stats['delivered_orders'] }} delivered</span>
                    @if ($stats['cancelled_orders'] > 0)
                        &middot; <span class="text-danger">{{ $stats['cancelled_orders'] }} cancelled</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">WhatsApp Status</span>
                    <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-whatsapp fs-6"></i></span>
                </div>
                @if ($conversation)
                    <div class="h5 fw-bold mb-1">
                        @if ($conversation->isWindowOpen())
                            <span class="text-success"><i class="bi bi-broadcast me-1"></i>24h Window Open</span>
                        @else
                            <span class="text-secondary"><i class="bi bi-lock me-1"></i>Window Closed</span>
                        @endif
                    </div>
                    <div class="small text-muted" style="font-size:0.75rem;">
                        {{ $conversation->windowRemainingFormatted() }}
                        @if ($conversation->unread_count > 0)
                            &middot; <span class="badge bg-danger">{{ $conversation->unread_count }} unread</span>
                        @endif
                    </div>
                @elseif ($user->phone)
                    <div class="h5 fw-bold mb-1 text-dark">Ready to Connect</div>
                    <div class="small text-muted" style="font-size:0.75rem;">Mobile verified: +{{ $user->getCleanPhone() }}</div>
                @else
                    <div class="h5 fw-bold mb-1 text-muted">No Phone Registered</div>
                    <div class="small text-muted" style="font-size:0.75rem;">Email only profile</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider" style="font-size:0.75rem;">Active Cart Snapshot</span>
                    <span class="badge bg-purple-subtle text-purple rounded-pill p-2" style="background:#f3e8ff;color:#9333ea;"><i class="bi bi-cart3 fs-6"></i></span>
                </div>
                <div class="h3 fw-bold mb-1" style="color:#7e22ce;">{{ $stats['cart_items_count'] }} item(s)</div>
                <div class="small text-muted" style="font-size:0.75rem;">
                    Total Value: <strong class="text-dark">{{ format_price($stats['cart_total_value']) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Left Column: Customer Info, Addresses, Reviews, WhatsApp Preview --}}
    <div class="col-lg-4">
        {{-- Profile Card --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold small text-uppercase tracking-wider text-muted">Customer Details</span>
                <span class="badge bg-light text-dark border">{{ $stats['segment']['label'] }}</span>
            </div>
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="avatar-circle shadow-sm fw-bold bg-primary text-white"
                          style="width: 50px; height: 50px; min-width: 50px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">{{ $user->name }}</h6>
                        <small class="text-muted">{{ $user->email }}</small>
                    </div>
                </div>

                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-phone me-1.5 text-muted"></i>Mobile</span>
                        <span class="fw-semibold font-monospace">
                            @if ($user->phone)
                                +{{ $user->getCleanPhone() }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-clock-history me-1.5 text-muted"></i>Last Active</span>
                        <span class="fw-semibold">
                            @if ($stats['last_active'])
                                {{ $stats['last_active']->diffForHumans() }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-calendar-event me-1.5 text-muted"></i>Last Order</span>
                        <span class="fw-semibold">
                            @if ($stats['last_order_date'])
                                {{ $stats['last_order_date']->format('d M Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-arrow-return-left me-1.5 text-muted"></i>Returns</span>
                        <span class="badge bg-light text-dark border">{{ $stats['return_requests'] }} requests</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- WhatsApp Quick Preview Card --}}
        @if ($user->phone)
            <div class="card border-0 shadow-sm mb-3 border-success-subtle" style="border-radius: 12px;">
                <div class="card-header bg-success-subtle text-success py-2.5 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-whatsapp"></i><span>WhatsApp Thread</span>
                    </span>
                    <a href="{{ route('admin.whatsapp.inbox', ['user_id' => $user->id]) }}" class="badge bg-success text-white text-decoration-none">
                        Open Full Chat &rarr;
                    </a>
                </div>
                <div class="card-body p-3">
                    @if ($conversation && $conversation->messages->isNotEmpty())
                        <div class="d-flex flex-column gap-2 mb-3" style="max-height: 220px; overflow-y: auto;">
                            @foreach ($conversation->messages->reverse()->take(5) as $msg)
                                <div class="p-2 rounded {{ $msg->direction === 'outbound' ? 'bg-success-subtle text-dark ms-auto' : 'bg-light text-dark me-auto' }}" style="max-width: 85%; font-size: 0.78rem;">
                                    <div class="small fw-semibold text-muted" style="font-size:0.65rem;">
                                        {{ $msg->direction === 'outbound' ? 'Staff' : $user->name }} &middot; {{ $msg->created_at->format('h:i A, d M') }}
                                    </div>
                                    <div class="mt-0.5">{{ Str::limit($msg->content ?: '[' . $msg->type . ']', 80) }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-3">No messages logged in WhatsApp Inbox yet.</p>
                    @endif

                    <div class="d-grid gap-2">
                        <a href="{{ $user->getWhatsAppDirectUrl() }}" target="_blank" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-box-arrow-up-right"></i><span>Launch Direct WhatsApp (wa.me)</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Saved Addresses --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold small text-uppercase tracking-wider text-muted">Addresses ({{ $addresses->count() }})</span>
            </div>
            <div class="card-body p-3">
                @forelse ($addresses as $address)
                    <div class="border rounded p-2.5 mb-2 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small text-dark">{{ $address->full_name }}</span>
                            <div>
                                <span class="badge bg-light text-dark border" style="font-size:0.68rem;">{{ ucfirst($address->type) }}</span>
                                @if ($address->is_default)
                                    <span class="badge bg-primary" style="font-size:0.68rem;">Default</span>
                                @endif
                            </div>
                        </div>
                        <div class="small text-muted" style="font-size:0.75rem; line-height: 1.4;">
                            {{ $address->address_line1 }}{{ $address->address_line2 ? ', ' . $address->address_line2 : '' }}<br>
                            {{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}<br>
                            Phone: {{ $address->phone }}
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No saved addresses on file.</p>
                @endforelse
            </div>
        </div>

        {{-- Customer Reviews --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold small text-uppercase tracking-wider text-muted">Product Reviews ({{ $reviews->count() }})</span>
            </div>
            <div class="card-body p-3">
                @forelse ($reviews as $review)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="small fw-semibold text-dark">{{ $review->product?->name ?: 'Product' }}</span>
                            <span class="small text-muted" style="font-size:0.7rem;">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="small mb-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}" style="font-size:0.75rem;"></i>
                            @endfor
                            <span class="badge bg-light text-dark border ms-1" style="font-size:0.65rem;">{{ ucfirst($review->status) }}</span>
                        </div>
                        @if ($review->comment)
                            <div class="text-muted small" style="font-size:0.75rem;">"{{ Str::limit($review->comment, 90) }}"</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">No product reviews submitted yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Column: Complete Purchase & Orders History + Active Cart --}}
    <div class="col-lg-8">
        {{-- Active Cart Snapshot if items exist --}}
        @if ($user->activeCart && $user->activeCart->items->isNotEmpty())
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; background: #faf5ff; border: 1px dashed #d8b4fe !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small text-purple d-inline-flex align-items-center gap-1.5" style="color:#7e22ce;">
                            <i class="bi bi-cart-check-fill"></i><span>Active Abandoned/Live Cart Detected ({{ $user->activeCart->items->count() }} items)</span>
                        </span>
                        <span class="badge bg-white text-dark border fw-bold">{{ format_price($stats['cart_total_value']) }}</span>
                    </div>
                    <div class="row g-2 mb-2">
                        @foreach ($user->activeCart->items as $cartItem)
                            <div class="col-md-6">
                                <div class="bg-white p-2 rounded border small d-flex align-items-center justify-content-between">
                                    <div class="text-truncate me-2">
                                        <div class="fw-semibold text-truncate">{{ $cartItem->product?->name }}</div>
                                        <span class="text-muted" style="font-size:0.72rem;">Qty: {{ $cartItem->quantity }} &middot; {{ format_price($cartItem->variant?->selling_price ?? $cartItem->product?->selling_price ?? 0) }}</span>
                                    </div>
                                    <span class="badge bg-light text-dark border">In Cart</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($user->phone)
                        <button type="button" class="btn btn-xs btn-outline-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#sendCustomerWhatsAppModal">
                            <i class="bi bi-whatsapp"></i><span>Send Recovery WhatsApp Alert</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Purchase History Table --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0 fw-bold">Purchase &amp; Order History</h6>
                    <small class="text-muted">{{ $orders->count() }} total orders placed</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Order #</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Fulfillment Status</th>
                            <th class="text-end">Grand Total</th>
                            <th>Date</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $orderStatusColors = [
                                'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'info',
                                'shipped' => 'primary', 'out_for_delivery' => 'primary', 'delivered' => 'success',
                                'cancelled' => 'danger', 'failed' => 'danger',
                            ];
                        @endphp
                        @forelse ($orders as $order)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold small text-decoration-none text-dark hover-green">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="small">
                                    <span class="badge bg-light text-dark border">{{ $order->items->count() }} item(s)</span>
                                    @if ($firstItem = $order->items->first())
                                        <div class="text-muted text-truncate" style="max-width: 150px; font-size:0.72rem;">
                                            {{ $firstItem->product_name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="small">
                                    <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'dark') }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                    <div class="text-muted" style="font-size:0.7rem;">{{ strtoupper($order->payment_method ?? 'COD') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $orderStatusColors[$order->order_status] ?? 'secondary' }} rounded-pill small">
                                        {{ ucfirst(str_replace('_', ' ', $order->order_status)) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold small text-dark">
                                    {{ format_price($order->grand_total) }}
                                </td>
                                <td class="small text-muted" style="white-space: nowrap;">
                                    {{ $order->created_at->format('d M Y') }}
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Order Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-bag-x fs-2 d-block mb-2"></i>
                                    No purchase orders placed by this customer yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Send WhatsApp Message / Meta Template to Customer --}}
@if ($user->phone)
<div class="modal fade" id="sendCustomerWhatsAppModal" tabindex="-1" aria-labelledby="sendCustomerWhatsAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title fw-bold" id="sendCustomerWhatsAppModalLabel">
                    <i class="bi bi-whatsapp me-1"></i> Send WhatsApp to {{ $user->name }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.customers.send-whatsapp', $user->id) }}">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Recipient Mobile</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                            <input type="text" class="form-control" value="+{{ $user->getCleanPhone() }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Message Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeTemplate" value="template" checked onchange="toggleMessageType()">
                                <label class="form-check-label small" for="typeTemplate">Approved Meta Template</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="typeDirect" value="direct" {{ $conversation && $conversation->isWindowOpen() ? '' : 'disabled' }} onchange="toggleMessageType()">
                                <label class="form-check-label small" for="typeDirect">
                                    Direct Message
                                    @if ($conversation && $conversation->isWindowOpen())
                                        <span class="badge bg-success" style="font-size:0.65rem;">Window Open</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size:0.65rem;">Window Closed</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Template Selection Group --}}
                    <div id="templateGroup" class="mb-3">
                        <label class="form-label small fw-semibold">Select Meta Template</label>
                        <select name="template_name" class="form-select form-select-sm" required>
                            @forelse ($approvedTemplates as $tpl)
                                <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ strtoupper($tpl->language) }} - {{ ucfirst($tpl->category) }})</option>
                            @empty
                                <option value="vanriti_welcome_v1">vanriti_welcome_v1 (Default Welcome)</option>
                                <option value="vanriti_order_confirmed_v1">vanriti_order_confirmed_v1 (Order Confirmation)</option>
                            @endforelse
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size:0.72rem;">Customer name parameter is populated automatically.</small>
                    </div>

                    {{-- Direct Message Group --}}
                    <div id="directGroup" class="mb-3 d-none">
                        <label class="form-label small fw-semibold">Direct Message</label>
                        <textarea name="message" class="form-control form-control-sm" rows="3" placeholder="Type direct WhatsApp reply..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                        <i class="bi bi-send"></i><span>Send WhatsApp</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleMessageType() {
    const isDirect = document.getElementById('typeDirect').checked;
    const templateGroup = document.getElementById('templateGroup');
    const directGroup = document.getElementById('directGroup');
    const templateSelect = templateGroup.querySelector('select');
    const directTextarea = directGroup.querySelector('textarea');

    if (isDirect) {
        templateGroup.classList.add('d-none');
        directGroup.classList.remove('d-none');
        templateSelect.removeAttribute('required');
        directTextarea.setAttribute('required', 'required');
    } else {
        templateGroup.classList.remove('d-none');
        directGroup.classList.add('d-none');
        templateSelect.setAttribute('required', 'required');
        directTextarea.removeAttribute('required');
    }
}
</script>
@endpush
@endif
@endsection
