@extends('admin.layouts.app')

@section('title', 'Edit Order '.$order->order_number)

@section('content')
@php
    $oClass = ['cancelled'=>'danger','failed'=>'danger','delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','pending'=>'secondary','confirmed'=>'primary','processing'=>'warning','packed'=>'warning'];
    $pClass = ['paid'=>'success','refunded'=>'secondary','partially_refunded'=>'warning','pending'=>'secondary','processing'=>'info','failed'=>'danger','cancelled'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold">Edit Order {{ $order->order_number }}</h5>
            <span class="badge bg-{{ $oClass[$order->order_status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
            <span class="badge bg-{{ $pClass[$order->payment_status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span>
        </div>
        <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }} &bull; Source: <span class="fw-semibold text-uppercase">{{ $order->checkout_source ?? 'web' }}</span></small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye me-1"></i>View Order</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-0.5"></i>
            <div>
                <strong>Please fix the following validation errors:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-x-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3 mb-3">
    {{-- Card 1: Order Status --}}
    <div class="col-md-6">
        <div class="card h-100 shadow-xs border-0" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-arrow-repeat me-1 text-primary"></i>Update Order Status</span>
                <span class="badge bg-{{ $oClass[$order->order_status] ?? 'secondary' }}-subtle text-{{ $oClass[$order->order_status] ?? 'secondary' }} border">
                    Current: {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                </span>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-muted">New Order Status</label>
                        <select name="order_status" class="form-select form-select-sm" required>
                            @foreach ($orderStatuses as $status)
                                <option value="{{ $status }}" {{ $order->order_status === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Status Note / Description (optional)</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="e.g. Dispatched with Bluedart or verified by telephone"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Update Order Status</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Card 2: Payment Method & Payment Status --}}
    <div class="col-md-6" id="paymentCard">
        <div class="card h-100 shadow-xs border-0" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-credit-card me-1 text-success"></i>Payment Method &amp; Status</span>
                <span class="badge bg-{{ $pClass[$order->payment_status] ?? 'secondary' }}-subtle text-{{ $pClass[$order->payment_status] ?? 'secondary' }} border">
                    {{ strtoupper($order->payment_method) }} &bull; {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}
                </span>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('admin.orders.payment', $order) }}">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="orderPaymentMethodSelect" class="form-select form-select-sm" required>
                                @foreach ($paymentMethods as $mKey => $mLabel)
                                    <option value="{{ $mKey }}" {{ $order->payment_method === $mKey ? 'selected' : '' }}>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Payment Status <span class="text-danger">*</span></label>
                            <select name="payment_status" id="orderPaymentStatusSelect" class="form-select form-select-sm" required>
                                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="cancelled" {{ $order->payment_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-muted">Transaction / Reference ID</label>
                            @php
                                $latestPayment = $order->payments()->latest()->first();
                                $defaultRef = $latestPayment?->payment_reference ?? '';
                            @endphp
                            <input type="text" name="payment_reference" class="form-control form-control-sm" value="{{ old('payment_reference', $defaultRef) }}" placeholder="e.g. UPI-9876543210 or UTR12345678" maxlength="100">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Amount Paid (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount_paid" class="form-control form-control-sm" value="{{ old('amount_paid', $order->amount_paid > 0 ? $order->amount_paid : $order->grand_total) }}" placeholder="₹0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Reason for Change (Audit Note)</label>
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="500" placeholder="e.g. Customer paid via PhonePe / UPI upon dispatch">
                    </div>

                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-wallet2 me-1"></i>Update Payment Details</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Card 3: Interactive Order Items & Product Manager --}}
<div class="card mb-3 shadow-xs border-0" id="itemsCard" style="border-radius:12px;">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-primary"></i>
                <span>Order Products &amp; Line Items</span>
                <span class="badge bg-secondary-subtle text-secondary" id="editItemsCountBadge">{{ $order->items->count() }} Item(s)</span>
            </div>
            <div class="text-muted small mt-0.5">Add new products, remove existing products, or adjust quantities and unit prices.</div>
        </div>
        <div class="small text-muted">
            Order Subtotal: <strong class="text-dark" id="displaySubtotal">{{ format_price($order->subtotal) }}</strong>
        </div>
    </div>
    <div class="card-body p-3">
        {{-- Product Search to Add Items --}}
        <div class="position-relative mb-3 bg-light p-2.5 rounded border">
            <label class="form-label small fw-semibold text-dark mb-1">
                <i class="bi bi-plus-circle me-1 text-success"></i>Add a Product to this Order:
            </label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="addProductSearchInput" class="form-control" placeholder="Type product name or SKU to search and add to order..." autocomplete="off">
                <button type="button" id="clearAddProductSearchBtn" class="btn btn-outline-secondary d-none"><i class="bi bi-x"></i></button>
            </div>
            <div id="addProductSearchResults" class="list-group position-absolute w-100 shadow border mt-1 d-none" style="z-index: 1050; max-height: 260px; overflow-y: auto;"></div>
        </div>

        <form method="POST" action="{{ route('admin.orders.items', $order) }}" id="orderItemsForm">
            @csrf

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="editOrderItemsTable">
                    <thead class="table-light">
                        <tr style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="min-width: 250px;">Product / SKU</th>
                            <th class="text-end" style="width: 100px;">MRP</th>
                            <th class="text-end" style="width: 130px;">Unit Price (₹)</th>
                            <th class="text-center" style="width: 140px;">Quantity</th>
                            <th class="text-end" style="width: 130px;">Line Total</th>
                            <th class="text-center" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="editItemsTableBody">
                        @foreach ($order->items as $index => $item)
                            @php
                                $stockable = $item->product_variant_id
                                    ? \App\Models\ProductVariant::find($item->product_variant_id)
                                    : \App\Models\Product::find($item->product_id);
                                $currentStock = $stockable ? (int) $stockable->stock : 0;
                            @endphp
                            <tr class="item-row" data-id="{{ $item->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($item->image)
                                            @if (str_starts_with($item->image, 'http'))
                                                <img src="{{ $item->image }}" alt="" class="rounded border" width="40" height="40" style="object-fit:cover;">
                                            @else
                                                <img src="{{ image_url($item->image) }}" alt="" class="rounded border" width="40" height="40" style="object-fit:cover;">
                                            @endif
                                        @else
                                            <div class="rounded bg-light border d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold small text-dark">{{ $item->product_name }}</div>
                                            <div class="text-muted" style="font-size:0.75rem;">
                                                <span>{{ $item->variant_name ?: ($item->sku ?: 'No SKU') }}</span>
                                                &bull; <span class="{{ $currentStock > 0 ? 'text-success' : 'text-danger' }}">({{ $currentStock }} in stock)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                    @if ($item->product_variant_id)
                                        <input type="hidden" name="items[{{ $index }}][product_variant_id]" value="{{ $item->product_variant_id }}">
                                    @endif
                                    <input type="hidden" name="items[{{ $index }}][mrp]" value="{{ $item->mrp }}">
                                </td>
                                <td class="text-end small text-muted">
                                    ₹{{ number_format((float) $item->mrp, 2) }}
                                </td>
                                <td class="text-end">
                                    <div class="input-group input-group-sm ms-auto" style="max-width: 115px;">
                                        <span class="input-group-text bg-light p-1">₹</span>
                                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" class="form-control text-end item-price-input" value="{{ (float) $item->unit_price }}">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 110px;">
                                        <button type="button" class="btn btn-outline-secondary px-2 item-minus-btn"><i class="bi bi-dash"></i></button>
                                        <input type="number" min="1" name="items[{{ $index }}][quantity]" class="form-control text-center px-1 item-qty-input" value="{{ $item->quantity }}">
                                        <button type="button" class="btn btn-outline-secondary px-2 item-plus-btn"><i class="bi bi-plus"></i></button>
                                    </div>
                                </td>
                                <td class="text-end fw-bold small text-dark item-line-total">
                                    ₹{{ number_format((float) $item->total_price, 2) }}
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 item-remove-btn" title="Remove item from order">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Controls and Submission --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3 mt-2 border-top">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="adjust_stock" id="adjustStockCheck" value="1" checked>
                    <label class="form-check-label small" for="adjustStockCheck">
                        <strong>Adjust inventory stock automatically</strong> (restock removed items, deduct newly added items)
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 py-2" id="saveItemsBtn">
                        <i class="bi bi-check2-circle me-1"></i>Save Items &amp; Recalculate Order
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3 shadow-xs border-0" style="border-radius:12px;">
    <div class="card-header bg-white py-2.5 border-bottom fw-bold small text-muted">
        <i class="bi bi-card-text me-1"></i>Add Internal Note
    </div>
    <div class="card-body p-3">
        <form method="POST" action="{{ route('admin.orders.notes', $order) }}">
            @csrf
            <div class="mb-2">
                <textarea name="internal_notes" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="Add a note for your team..."></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-sticky me-1"></i>Add Note</button>
        </form>
        @if ($order->internal_notes)
            <hr>
            <div class="small text-muted" style="white-space:pre-line;">{{ $order->internal_notes }}</div>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100 shadow-xs border-0" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-bottom fw-bold small">Order Financial Totals</div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span class="fw-semibold">{{ format_price($order->subtotal) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Discount</span><span>{{ format_price($order->discount_amount) }}</span></div>
                @if ($order->coupon_discount > 0)
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">
                            Coupon{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}
                            @if ($order->coupon_type || $order->coupon_value !== null)
                                <span class="d-block text-muted" style="font-size:0.8em;">{{ ucfirst((string) $order->coupon_type) }}{{ $order->coupon_value !== null ? ' '.format_price($order->coupon_value) : '' }}</span>
                            @endif
                        </span>
                        <span class="text-success">- {{ format_price($order->coupon_discount) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Taxable Value</span><span>{{ format_price($order->taxable_value) }}</span></div>
                @if ($order->isIntraState())
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">CGST</span><span>{{ format_price($order->cgst_amount) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">SGST</span><span>{{ format_price($order->sgst_amount) }}</span></div>
                @else
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">IGST</span><span>{{ format_price($order->igst_amount) }}</span></div>
                @endif
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Shipping</span><span>{{ format_price($order->shipping_charge) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between py-1 fw-bold fs-6 text-dark"><span>Grand Total</span><span>{{ format_price($order->grand_total) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Paid</span><span class="text-success fw-semibold">{{ format_price($order->amount_paid) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Due / Balance</span><span class="fw-semibold {{ $order->amount_due > 0 ? 'text-danger' : 'text-muted' }}">{{ format_price($order->amount_due) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-xs border-0" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-bottom fw-bold small">Billing Information</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->billing_name }}</div>
                <div class="text-muted">{{ $order->billing_address_line1 }}</div>
                @if ($order->billing_address_line2)<div class="text-muted">{{ $order->billing_address_line2 }}</div>@endif
                @if ($order->billing_landmark)<div class="text-muted">{{ $order->billing_landmark }}</div>@endif
                <div class="text-muted">{{ $order->billing_city }}, {{ $order->billing_state }} - {{ $order->billing_pincode }}</div>
                <div class="text-muted mt-2"><i class="bi bi-telephone me-1"></i>{{ $order->billing_mobile }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-xs border-0" style="border-radius:12px;">
            <div class="card-header bg-white py-3 border-bottom fw-bold small">Shipping Information</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->shipping_name }}</div>
                <div class="text-muted">{{ $order->shipping_address_line1 }}</div>
                @if ($order->shipping_address_line2)<div class="text-muted">{{ $order->shipping_address_line2 }}</div>@endif
                @if ($order->shipping_landmark)<div class="text-muted">{{ $order->shipping_landmark }}</div>@endif
                <div class="text-muted">{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</div>
                <div class="text-muted mt-2"><i class="bi bi-telephone me-1"></i>{{ $order->shipping_mobile }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-xs border-0" style="border-radius:12px;">
    <div class="card-header bg-white py-3 border-bottom fw-bold small">Status &amp; Audit History</div>
    <div class="card-body p-3">
        @forelse ($order->statusHistories as $history)
            <div class="d-flex gap-2 mb-2.5 small">
                <i class="bi bi-circle-fill text-primary mt-1" style="font-size:0.5rem;"></i>
                <div>
                    <div class="fw-semibold text-dark">{{ ucwords(str_replace('_', ' ', $history->status)) }}</div>
                    <div class="text-muted" style="font-size:0.75rem;">{{ $history->created_at->format('d M Y, h:i A') }}</div>
                    @if ($history->description)<div class="text-muted mt-0.5">{{ $history->description }}</div>@endif
                </div>
            </div>
        @empty
            <div class="text-muted small">No status history.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const addProductSearchInput = document.getElementById('addProductSearchInput');
    const addProductSearchResults = document.getElementById('addProductSearchResults');
    const clearAddProductSearchBtn = document.getElementById('clearAddProductSearchBtn');
    const editItemsTableBody = document.getElementById('editItemsTableBody');
    const editItemsCountBadge = document.getElementById('editItemsCountBadge');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const orderItemsForm = document.getElementById('orderItemsForm');
    const saveItemsBtn = document.getElementById('saveItemsBtn');

    let nextIndex = {{ $order->items->count() }};

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Live search for products
    addProductSearchInput.addEventListener('input', debounce(function () {
        const query = this.value.trim();
        if (query.length < 1) {
            addProductSearchResults.classList.add('d-none');
            addProductSearchResults.innerHTML = '';
            clearAddProductSearchBtn.classList.add('d-none');
            return;
        }

        clearAddProductSearchBtn.classList.remove('d-none');
        fetch(`{{ route('admin.orders.search-products') }}?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(products => {
            addProductSearchResults.innerHTML = '';
            if (!products || products.length === 0) {
                addProductSearchResults.innerHTML = '<div class="list-group-item text-muted small p-2">No products found.</div>';
                addProductSearchResults.classList.remove('d-none');
                return;
            }

            products.forEach(product => {
                if (product.has_variants && product.variants.length > 0) {
                    product.variants.forEach(variant => {
                        const item = createProductDropdownItem(
                            product.id,
                            variant.id,
                            `${product.name} (${variant.name})`,
                            variant.sku || product.sku,
                            variant.mrp || product.mrp,
                            variant.selling_price || product.selling_price,
                            variant.stock,
                            product.image_url
                        );
                        addProductSearchResults.appendChild(item);
                    });
                } else {
                    const item = createProductDropdownItem(
                        product.id,
                        null,
                        product.name,
                        product.sku,
                        product.mrp,
                        product.selling_price,
                        product.stock,
                        product.image_url
                    );
                    addProductSearchResults.appendChild(item);
                }
            });
            addProductSearchResults.classList.remove('d-none');
        })
        .catch(() => {
            addProductSearchResults.classList.add('d-none');
        });
    }, 250));

    clearAddProductSearchBtn.addEventListener('click', function () {
        addProductSearchInput.value = '';
        addProductSearchResults.classList.add('d-none');
        this.classList.add('d-none');
    });

    function createProductDropdownItem(productId, variantId, name, sku, mrp, price, stock, imageUrl) {
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action p-2 d-flex align-items-center justify-content-between';
        const stockBadge = stock > 0
            ? `<span class="badge bg-success-subtle text-success">${stock} in stock</span>`
            : `<span class="badge bg-danger-subtle text-danger">Out of stock</span>`;

        a.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <img src="${imageUrl}" class="rounded border" style="width:36px;height:36px;object-fit:cover;" onerror="this.src='/images/placeholder.svg'">
                <div>
                    <div class="fw-bold small text-dark">${escapeHtml(name)}</div>
                    <div class="text-muted" style="font-size:0.72rem;">SKU: <span class="fw-semibold">${escapeHtml(sku || 'N/A')}</span> &bull; MRP: ₹${Number(mrp).toFixed(2)}</div>
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold text-dark small">₹${Number(price).toFixed(2)}</div>
                ${stockBadge}
            </div>
        `;

        a.addEventListener('click', function (e) {
            e.preventDefault();
            addNewRowToTable({
                product_id: productId,
                product_variant_id: variantId,
                name: name,
                sku: sku || 'N/A',
                mrp: Number(mrp) || 0,
                unit_price: Number(price) || 0,
                quantity: 1,
                stock: Number(stock) || 0,
                image_url: imageUrl
            });
            addProductSearchResults.classList.add('d-none');
            addProductSearchInput.value = '';
            clearAddProductSearchBtn.classList.add('d-none');
        });

        return a;
    }

    function addNewRowToTable(item) {
        const index = nextIndex++;
        const tr = document.createElement('tr');
        tr.className = 'item-row table-success';
        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${item.image_url}" class="rounded border" style="width:40px;height:40px;object-fit:cover;" onerror="this.src='/images/placeholder.svg'">
                    <div>
                        <div class="fw-semibold small text-dark">${escapeHtml(item.name)} <span class="badge bg-success ms-1" style="font-size:0.6rem;">New</span></div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <span>${escapeHtml(item.sku)}</span>
                            &bull; <span class="${item.stock > 0 ? 'text-success' : 'text-danger'}">(${item.stock} in stock)</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                ${item.product_variant_id ? `<input type="hidden" name="items[${index}][product_variant_id]" value="${item.product_variant_id}">` : ''}
                <input type="hidden" name="items[${index}][mrp]" value="${item.mrp}">
            </td>
            <td class="text-end small text-muted">
                ₹${item.mrp.toFixed(2)}
            </td>
            <td class="text-end">
                <div class="input-group input-group-sm ms-auto" style="max-width: 115px;">
                    <span class="input-group-text bg-light p-1">₹</span>
                    <input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="form-control text-end item-price-input" value="${item.unit_price.toFixed(2)}">
                </div>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 110px;">
                    <button type="button" class="btn btn-outline-secondary px-2 item-minus-btn"><i class="bi bi-dash"></i></button>
                    <input type="number" min="1" name="items[${index}][quantity]" class="form-control text-center px-1 item-qty-input" value="1">
                    <button type="button" class="btn btn-outline-secondary px-2 item-plus-btn"><i class="bi bi-plus"></i></button>
                </div>
            </td>
            <td class="text-end fw-bold small text-dark item-line-total">
                ₹${item.unit_price.toFixed(2)}
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0 item-remove-btn" title="Remove item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        editItemsTableBody.appendChild(tr);
        bindRowEvents(tr);
        recalculateTotals();
    }

    function bindRowEvents(row) {
        const minusBtn = row.querySelector('.item-minus-btn');
        const plusBtn = row.querySelector('.item-plus-btn');
        const qtyInput = row.querySelector('.item-qty-input');
        const priceInput = row.querySelector('.item-price-input');
        const removeBtn = row.querySelector('.item-remove-btn');
        const lineTotalEl = row.querySelector('.item-line-total');

        if (minusBtn) {
            minusBtn.addEventListener('click', function () {
                let qty = parseInt(qtyInput.value, 10) || 1;
                if (qty > 1) {
                    qtyInput.value = qty - 1;
                    updateLine();
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', function () {
                let qty = parseInt(qtyInput.value, 10) || 1;
                qtyInput.value = qty + 1;
                updateLine();
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                let qty = parseInt(qtyInput.value, 10) || 1;
                if (qty < 1) qty = 1;
                qtyInput.value = qty;
                updateLine();
            });
        }

        if (priceInput) {
            priceInput.addEventListener('input', updateLine);
        }

        function updateLine() {
            const qty = parseInt(qtyInput.value, 10) || 1;
            const price = parseFloat(priceInput.value) || 0;
            const total = qty * price;
            lineTotalEl.textContent = `₹${total.toFixed(2)}`;
            recalculateTotals();
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const totalRows = editItemsTableBody.querySelectorAll('.item-row').length;
                if (totalRows <= 1) {
                    alert('An order must contain at least one item. You cannot remove the last remaining product.');
                    return;
                }
                if (confirm('Are you sure you want to remove this product from the order?')) {
                    row.remove();
                    recalculateTotals();
                }
            });
        }
    }

    // Bind existing rows
    document.querySelectorAll('#editItemsTableBody .item-row').forEach(bindRowEvents);

    function recalculateTotals() {
        const rows = editItemsTableBody.querySelectorAll('.item-row');
        editItemsCountBadge.textContent = `${rows.length} Item(s)`;

        let subtotal = 0;
        rows.forEach(row => {
            const qty = parseInt(row.querySelector('.item-qty-input').value, 10) || 1;
            const price = parseFloat(row.querySelector('.item-price-input').value) || 0;
            subtotal += (qty * price);
        });

        displaySubtotal.textContent = `₹${subtotal.toFixed(2)}`;
    }

    orderItemsForm.addEventListener('submit', function (e) {
        const rows = editItemsTableBody.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('An order must have at least one product.');
            return false;
        }

        saveItemsBtn.disabled = true;
        saveItemsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving & Recalculating...';
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
    }
});
</script>
@endpush
