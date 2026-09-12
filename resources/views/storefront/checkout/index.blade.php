@extends('storefront.layouts.app')
@section('title', 'Checkout')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        {{-- Checkout Stepper --}}
        <div class="vr-stepper d-none d-md-flex mb-4">
            <div class="vr-stepper-step completed">
                <span class="vr-stepper-num"><i class="ri-check-line"></i></span>
                <span>Cart</span>
            </div>
            <div class="vr-stepper-divider completed"></div>
            <div class="vr-stepper-step active">
                <span class="vr-stepper-num">2</span>
                <span>Shipping & Payment</span>
            </div>
            <div class="vr-stepper-divider"></div>
            <div class="vr-stepper-step">
                <span class="vr-stepper-num">3</span>
                <span>Confirmation</span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="vr-section-title mb-1">Checkout</h1>
                <p class="text-muted small mb-0">Complete your order securely</p>
            </div>
        </div>

        @if (! $cart || ! $cart->items()->exists())
            <div class="vr-guest-card text-center py-5 mx-auto" style="max-width: 500px;">
                <div class="vr-guest-icon-box mb-3" style="width: 72px; height: 72px; font-size: 2.5rem;">
                    <i class="ri-shopping-bag-line"></i>
                </div>
                <h4 class="fw-bold mb-2">Your Cart is Empty</h4>
                <p class="text-muted small mb-4">You have no items in your cart to checkout.</p>
                <a href="{{ route('shop.index') }}" class="btn vr-app-btn px-4 py-2">
                    <i class="ri-compass-line me-2"></i> Discover Products
                </a>
            </div>
        @else
            <form action="{{ route('checkout.store') }}" method="POST" id="checkoutForm" novalidate>
                @csrf
                <div class="row g-4">
                    <div class="col-lg-8">
                        {{-- Delivery Address Section --}}
                        <div class="vr-guest-card p-4 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                    <i class="ri-map-pin-2-line text-success"></i> 1. Delivery Address
                                </h5>
                                <button type="button" class="btn vr-app-outline-btn btn-sm" onclick="openAddressModal()">
                                    <i class="ri-add-line me-1"></i> Add New Address
                                </button>
                            </div>

                            @if ($addresses->count() > 0)
                                <div class="mb-3">
                                    <div class="row g-2">
                                        @foreach ($addresses as $addr)
                                            <div class="col-12">
                                                <label class="vr-selection-card d-flex align-items-start gap-3 address-option {{ ($selectedAddress && $selectedAddress->id === $addr->id) ? 'selected' : '' }}"
                                                       data-address="{{ htmlspecialchars(json_encode($addr->only(['full_name','mobile','address_line1','address_line2','landmark','city','state','pincode','country']))) }}">
                                                    <input type="radio" name="selected_address" value="{{ $addr->id }}"
                                                           class="form-check-input mt-1 address-radio"
                                                           data-address-id="{{ $addr->id }}"
                                                           {{ ($selectedAddress && $selectedAddress->id === $addr->id) ? 'checked' : '' }}>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="fw-bold text-dark">{{ $addr->full_name }}</span>
                                                            <span class="vr-type-chip">{{ $addr->type }}</span>
                                                            @if ($addr->is_default)
                                                                <span class="vr-badge-pill">Default</span>
                                                            @endif
                                                        </div>
                                                        <div class="small text-muted mt-1">{{ $addr->full_address }}</div>
                                                        <div class="small text-muted mt-1"><i class="ri-phone-line me-1"></i> {{ $addr->mobile }}</div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-auto" onclick="event.preventDefault();openAddressModal({{ $addr->id }})" title="Edit address">
                                                        <i class="ri-edit-line fs-5"></i>
                                                    </button>
                                                </label>
                                            </div>
                                        @endforeach
                                        <div class="col-12">
                                            <label class="vr-selection-card d-flex align-items-center gap-3">
                                                <input type="radio" name="selected_address" value="new"
                                                       class="form-check-input address-radio" {{ !$selectedAddress ? 'checked' : '' }}>
                                                <span class="fw-semibold small"><i class="ri-add-circle-line me-1"></i> Use a new delivery address</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div id="newAddressFields" class="{{ $addresses->count() > 0 && $selectedAddress ? 'd-none' : '' }}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Full Name</label>
                                        <input type="text" name="shipping_name" class="form-control"
                                               value="{{ old('shipping_name', $selectedAddress->full_name ?? $user->name ?? '') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Mobile Number</label>
                                        <input type="text" name="shipping_mobile" class="form-control"
                                               value="{{ old('shipping_mobile', $selectedAddress->mobile ?? '') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Address Line 1</label>
                                        <input type="text" name="shipping_address_line1" class="form-control"
                                               value="{{ old('shipping_address_line1', $selectedAddress->address_line1 ?? '') }}" placeholder="House/Flat No., Building Name, Street">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Address Line 2 <span class="text-muted">(optional)</span></label>
                                        <input type="text" name="shipping_address_line2" class="form-control"
                                               value="{{ old('shipping_address_line2', $selectedAddress->address_line2 ?? '') }}" placeholder="Apartment, suite, area">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Landmark <span class="text-muted">(optional)</span></label>
                                        <input type="text" name="shipping_landmark" class="form-control"
                                               value="{{ old('shipping_landmark', $selectedAddress->landmark ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                            Pincode
                                            <span class="vr-pincode-tip"><i class="ri-map-pin-2-line"></i> Auto-fills city &amp; state</span>
                                        </label>
                                        <input type="text" name="shipping_pincode" id="shipping_pincode"
                                               class="form-control"
                                               value="{{ old('shipping_pincode', $selectedAddress->pincode ?? '') }}"
                                               maxlength="6" inputmode="numeric" placeholder="6-digit pincode"
                                               data-pincode-input data-pincode-prefix="shipping_">
                                        <div class="vr-pincode-status" id="shipping_pincode_status"></div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">City</label>
                                        <input type="text" name="shipping_city" class="form-control"
                                               value="{{ old('shipping_city', $selectedAddress->city ?? '') }}"
                                               placeholder="Auto-filled from pincode">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">State</label>
                                        <input type="text" name="shipping_state" class="form-control"
                                               value="{{ old('shipping_state', $selectedAddress->state ?? '') }}"
                                               placeholder="Auto-filled from pincode">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Country</label>
                                        <input type="text" name="shipping_country" class="form-control"
                                               value="{{ old('shipping_country', $selectedAddress->country ?? 'India') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Billing Address Section --}}
                        <div class="vr-guest-card p-4 mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="billing_same" value="1" id="billingSame" class="form-check-input"
                                       {{ old('billing_same', '1') ? 'checked' : '' }}>
                                <label for="billingSame" class="form-check-label fw-semibold">Billing address is same as shipping address</label>
                            </div>

                            <div id="billingFields" class="{{ old('billing_same', '1') ? 'd-none' : '' }} mt-3 pt-3 border-top">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Full Name</label>
                                        <input type="text" name="billing_name" class="form-control" value="{{ old('billing_name', '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Mobile</label>
                                        <input type="text" name="billing_mobile" class="form-control" value="{{ old('billing_mobile', '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Address Line 1</label>
                                        <input type="text" name="billing_address_line1" class="form-control" value="{{ old('billing_address_line1', '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Address Line 2 <span class="text-muted">(optional)</span></label>
                                        <input type="text" name="billing_address_line2" class="form-control" value="{{ old('billing_address_line2', '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Landmark <span class="text-muted">(optional)</span></label>
                                        <input type="text" name="billing_landmark" class="form-control" value="{{ old('billing_landmark', '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                            Pincode
                                            <span class="vr-pincode-tip"><i class="ri-map-pin-2-line"></i> Auto-fills city &amp; state</span>
                                        </label>
                                        <input type="text" name="billing_pincode" id="billing_pincode"
                                               class="form-control"
                                               value="{{ old('billing_pincode', '') }}"
                                               maxlength="6" inputmode="numeric" placeholder="6-digit pincode">
                                        <div class="vr-pincode-status" id="billing_pincode_status"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">City</label>
                                        <input type="text" name="billing_city" class="form-control"
                                               value="{{ old('billing_city', '') }}"
                                               placeholder="Auto-filled from pincode">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">State</label>
                                        <input type="text" name="billing_state" class="form-control"
                                               value="{{ old('billing_state', '') }}"
                                               placeholder="Auto-filled from pincode">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Country</label>
                                        <input type="text" name="billing_country" class="form-control" value="{{ old('billing_country', 'India') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping Method Section --}}
                        <div class="vr-guest-card p-4 mb-4">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ri-truck-line text-success"></i> 2. Shipping Options
                            </h5>
                            <div class="d-flex flex-column gap-2">
                                <label class="vr-selection-card d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="shipping_method" value="standard" class="form-check-input"
                                               {{ old('shipping_method', 'standard') === 'standard' ? 'checked' : '' }}>
                                        <div>
                                            <span class="fw-bold text-dark d-block">Standard Delivery</span>
                                            <span class="small text-muted">Estimated delivery in {{ $shipping['estimated_days'] ?? '3-7' }} business days</span>
                                        </div>
                                    </div>
                                    <span class="fw-bold">
                                        @if ($shipping['charge'] <= 0)
                                            <span class="text-success">FREE</span>
                                        @else
                                            {{ format_price($shipping['charge']) }}
                                        @endif
                                    </span>
                                </label>
                                <label class="vr-selection-card d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="radio" name="shipping_method" value="express" class="form-check-input"
                                               {{ old('shipping_method') === 'express' ? 'checked' : '' }}>
                                        <div>
                                            <span class="fw-bold text-dark d-block">Express Delivery</span>
                                            <span class="small text-muted">Estimated delivery in 2-4 business days</span>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-dark">{{ format_price(($shipping['charge'] ?? 49) + 50) }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Payment Method Section --}}
                        <div class="vr-guest-card p-4 mb-4">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ri-bank-card-line text-success"></i> 3. Payment Method
                            </h5>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($availableMethods as $method => $details)
                                    @if ($details['enabled'])
                                        <label class="vr-selection-card d-flex align-items-center gap-3 payment-option">
                                            <input type="radio" name="payment_method" value="{{ $method }}" class="form-check-input"
                                                    {{ old('payment_method', 'razorpay') === $method ? 'checked' : '' }}>
                                            <div class="vr-header-icon-box" style="width:40px;height:40px;border-radius:12px;">
                                                @if ($method === 'cod')
                                                    <i class="ri-hand-coin-line fs-4"></i>
                                                @else
                                                    <i class="ri-bank-card-line fs-4"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block">{{ $details['label'] }}</span>
                                                <span class="small text-muted">{{ $details['description'] ?? '' }}</span>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Special Notes --}}
                        <div class="vr-guest-card p-4 mb-4">
                            <label class="form-label small fw-semibold">Order Notes <span class="text-muted">(optional)</span></label>
                            <textarea name="notes" class="form-control" rows="2" maxlength="500"
                                      placeholder="Any special instructions for your delivery...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Sticky Order Summary Sidebar --}}
                    <div class="col-lg-4">
                        <div class="vr-guest-card p-4 sticky-top" style="top: 100px;">
                            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-file-list-3-line text-success"></i> Order Summary
                            </h5>

                            <div class="mb-3">
                                <div class="d-flex flex-column gap-2 max-h-48 overflow-y-auto" style="max-height: 200px;">
                                    @foreach ($cart->items as $ci)
                                        <div class="d-flex align-items-center gap-2 py-1">
                                            <img src="{{ image_url($ci->product->getPrimaryImage()?->image_path, 'images/placeholder.png') }}"
                                                 alt="{{ $ci->product->name }}"
                                                 style="width:40px;height:40px;object-fit:cover;border-radius:8px;background:var(--vr-cream);">
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="small fw-semibold text-truncate">{{ $ci->product->name }}</div>
                                                <div class="small text-muted">Qty: {{ $ci->quantity }}</div>
                                            </div>
                                            <div class="small fw-bold">{{ format_price($ci->unit_price * $ci->quantity) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between small mb-2 text-muted">
                                <span>Subtotal <span class="text-muted" style="font-size:0.85em;">(incl. GST)</span></span>
                                <span class="text-dark fw-semibold">{{ format_price($subtotal) }}</span>
                            </div>

                            @if ($couponDiscount > 0)
                                <div class="d-flex justify-content-between small mb-2 text-success">
                                    <span><i class="ri-price-tag-3-line me-1"></i>Discount ({{ $couponCode }})</span>
                                    <span>-{{ format_price($couponDiscount) }}</span>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between small mb-2 text-muted">
                                <span>Shipping</span>
                                <span>
                                    @if ($shipping['charge'] <= 0)
                                        <span class="text-success fw-semibold">FREE</span>
                                    @else
                                        <span class="text-dark fw-semibold">{{ format_price($shipping['charge']) }}</span>
                                    @endif
                                </span>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between fw-bold fs-5 mb-1">
                                <span>Total</span>
                                <span class="text-success">{{ format_price(max(0, $subtotal - $couponDiscount + $shipping['charge'])) }}</span>
                            </div>
                            <div class="small text-muted mb-3">Total payable is inclusive of GST.</div>

                            <button type="submit" class="btn vr-app-btn w-100 py-3 mb-3" id="placeOrderBtn">
                                <i class="ri-lock-2-line me-2"></i> Place Order Now
                            </button>

                            <div class="p-3 bg-light rounded-3 text-center small text-muted">
                                <i class="ri-shield-check-line text-success me-1"></i> 256-Bit Encrypted Secure Checkout
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px;overflow:hidden;">
            <div class="modal-header vr-drawer-header">
                <h6 class="modal-title fw-bold mb-0" id="addressModalLabel">Add New Address</h6>
                <button type="button" class="vr-drawer-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="addressModalForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="full_name" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mobile</label>
                            <input type="text" name="mobile" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 1</label>
                            <input type="text" name="address_line1" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 2 <span class="text-muted">(optional)</span></label>
                            <input type="text" name="address_line2" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Landmark <span class="text-muted">(optional)</span></label>
                            <input type="text" name="landmark" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                Pincode
                                <span class="vr-pincode-tip"><i class="ri-map-pin-2-line"></i> Auto-fills city &amp; state</span>
                            </label>
                            <input type="text" name="pincode" id="modal_pincode" class="form-control"
                                   maxlength="6" inputmode="numeric" placeholder="6-digit pincode">
                            <div class="vr-pincode-status" id="modal_pincode_status"></div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control" placeholder="Auto-filled from pincode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text" name="state" class="form-control" placeholder="Auto-filled from pincode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Country</label>
                            <input type="text" name="country" class="form-control" value="India">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" value="1" id="modalIsDefault" class="form-check-input">
                                <label for="modalIsDefault" class="form-check-label small fw-semibold">Set as default address</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0 p-4">
                <button type="button" class="btn vr-app-outline-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn vr-app-btn" id="addressModalSaveBtn">
                    <span class="btn-text">Save Address</span>
                    <span class="spinner-border spinner-border-sm d-none ms-1"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ---- Pincode lookup for checkout inline fields ----
    var shippingPincodeEl = document.getElementById('shipping_pincode');
    if (shippingPincodeEl && typeof window.vrPincodeLookup === 'function') {
        var shippingForm = shippingPincodeEl.closest('form, div') || document;
        var shippingCityEl = document.querySelector('input[name="shipping_city"]');
        var shippingStateEl = document.querySelector('input[name="shipping_state"]');
        var shippingStatusEl = document.getElementById('shipping_pincode_status');
        window.vrPincodeLookup(shippingPincodeEl, shippingCityEl, shippingStateEl, shippingStatusEl);
    }

    var billingPincodeEl = document.getElementById('billing_pincode');
    if (billingPincodeEl && typeof window.vrPincodeLookup === 'function') {
        var billingCityEl = document.querySelector('input[name="billing_city"]');
        var billingStateEl = document.querySelector('input[name="billing_state"]');
        var billingStatusEl = document.getElementById('billing_pincode_status');
        window.vrPincodeLookup(billingPincodeEl, billingCityEl, billingStateEl, billingStatusEl);
    }

    var editAddressData = @json($addresses->keyBy('id'));

    window.openAddressModal = function (addressId) {
        var modal = document.getElementById('addressModal');
        var form = document.getElementById('addressModalForm');
        var title = document.getElementById('addressModalLabel');
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(function(el){ el.classList.remove('is-invalid'); });
        form.querySelectorAll('.invalid-feedback').forEach(function(el){ el.textContent = ''; });
        var modalStatusEl = document.getElementById('modal_pincode_status');
        if (modalStatusEl) modalStatusEl.textContent = '';

        if (addressId && editAddressData[addressId]) {
            var a = editAddressData[addressId];
            title.textContent = 'Edit Address';
            form.dataset.addressId = addressId;
            form.dataset.url = '/account/addresses/' + addressId;
            form.dataset.method = 'PUT';
            form.querySelector('input[name="full_name"]').value = a.full_name || '';
            form.querySelector('input[name="mobile"]').value = a.mobile || '';
            form.querySelector('input[name="address_line1"]').value = a.address_line1 || '';
            form.querySelector('input[name="address_line2"]').value = a.address_line2 || '';
            form.querySelector('input[name="landmark"]').value = a.landmark || '';
            // Set city & state before pincode so they're preserved (not overwritten by lookup)
            form.querySelector('input[name="city"]').value = a.city || '';
            form.querySelector('input[name="state"]').value = a.state || '';
            form.querySelector('input[name="pincode"]').value = a.pincode || '';
            form.querySelector('input[name="country"]').value = a.country || 'India';
            document.getElementById('modalIsDefault').checked = !!a.is_default;
        } else {
            title.textContent = 'Add New Address';
            delete form.dataset.addressId;
            form.dataset.url = '{{ route("account.addresses.store") }}';
            delete form.dataset.method;
        }

        // Initialize pincode lookup for modal
        var modalPincodeEl = document.getElementById('modal_pincode');
        if (modalPincodeEl && typeof window.vrPincodeLookup === 'function') {
            var modalCityEl = form.querySelector('input[name="city"]');
            var modalStateEl = form.querySelector('input[name="state"]');
            // Re-attach: remove old listener by cloning (simpler approach — just always look it up)
            modalPincodeEl._vrLookupInit = true;
            window.vrPincodeLookup(modalPincodeEl, modalCityEl, modalStateEl, modalStatusEl);
        }

        var bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    };

    document.getElementById('addressModalSaveBtn').addEventListener('click', function () {
        var form = document.getElementById('addressModalForm');
        var btn = this;
        var token = document.querySelector('meta[name="csrf-token"]').content;

        var body = new URLSearchParams(new FormData(form));
        body.append('_token', token);

        var method = form.dataset.method || 'POST';
        var url = form.dataset.url;

        if (method === 'PUT') {
            body.append('_method', 'PUT');
        }

        btn.disabled = true;
        btn.querySelector('.btn-text').textContent = 'Saving...';
        btn.querySelector('.spinner-border').classList.remove('d-none');

        fetch(url, {
            method: 'POST',
            body: body,
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (data) { return { status: r.status, data: data }; });
        }).then(function (result) {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Save Address';
            btn.querySelector('.spinner-border').classList.add('d-none');

            if (result.status === 422 && result.data.errors) {
                var errs = result.data.errors;
                Object.keys(errs).forEach(function (field) {
                    var input = form.querySelector('[name="' + field + '"]');
                    if (input) {
                        input.classList.add('is-invalid');
                        var fb = input.parentElement.querySelector('.invalid-feedback');
                        if (fb) fb.textContent = errs[field][0];
                    }
                });
                return;
            }

            if (result.data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addressModal')).hide();
                window.location.reload();
            }
        }).catch(function () {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Save Address';
            btn.querySelector('.spinner-border').classList.add('d-none');
            vrToast('Something went wrong. Please try again.', 'error');
        });
    });
})();

// Prefill shipping fields when a saved address is chosen
var addressRadios = document.querySelectorAll('.address-radio');
var newAddressFields = document.getElementById('newAddressFields');

function fillShipping(addr) {
    var map = {
        shipping_name: addr.full_name || '',
        shipping_mobile: addr.mobile || '',
        shipping_address_line1: addr.address_line1 || '',
        shipping_address_line2: addr.address_line2 || '',
        shipping_landmark: addr.landmark || '',
        shipping_city: addr.city || '',
        shipping_state: addr.state || '',
        shipping_pincode: addr.pincode || '',
        shipping_country: addr.country || 'India'
    };
    Object.keys(map).forEach(function (name) {
        var input = document.querySelector('input[name="' + name + '"]');
        if (input) input.value = map[name];
    });
}

function toggleNewAddressFields(show) {
    if (newAddressFields) newAddressFields.classList.toggle('d-none', !show);
}

addressRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
        addressRadios.forEach(function(r) {
            var l = r.closest('label');
            if (l) l.classList.remove('selected');
        });
        var label = this.closest('label');
        if (label) label.classList.add('selected');

        if (this.value === 'new') {
            toggleNewAddressFields(true);
            return;
        }
        toggleNewAddressFields(false);
        var data = label ? label.dataset.address : null;
        if (data) {
            try { fillShipping(JSON.parse(data)); } catch (e) {}
        }
    });
});

if (addressRadios.length > 0) {
    var checked = document.querySelector('.address-radio:checked');
    if (checked && checked.value !== 'new') {
        toggleNewAddressFields(false);
        var label = checked.closest('label');
        if (label) label.classList.add('selected');
        var data = label ? label.dataset.address : null;
        if (data) {
            try { fillShipping(JSON.parse(data)); } catch (e) {}
        }
    }
}

// Billing toggle
var billingSame = document.getElementById('billingSame');
var billingFields = document.getElementById('billingFields');
if (billingSame && billingFields) {
    billingSame.addEventListener('change', function () {
        billingFields.classList.toggle('d-none', this.checked);
    });
}

// Razorpay integration
var checkoutForm = document.getElementById('checkoutForm');
if (checkoutForm) {
    var checkoutFunnelFired = false;

    function fireCheckoutFunnel() {
        if (checkoutFunnelFired) return;
        checkoutFunnelFired = true;

        var base = window.vrCheckoutAnalytics && window.vrCheckoutAnalytics.ecommerce;
        if (!base) return;

        window.dataLayer = window.dataLayer || [];

        var shippingInput = checkoutForm.querySelector('input[name="shipping_method"]:checked');
        var shippingTier = shippingInput ? shippingInput.value : null;
        var shippingEcom = Object.assign({}, base);
        if (shippingTier) { shippingEcom.shipping_tier = shippingTier; }
        window.dataLayer.push({ event: 'add_shipping_info', ecommerce: shippingEcom });

        var paymentInput = checkoutForm.querySelector('input[name="payment_method"]:checked');
        var paymentType = paymentInput ? paymentInput.value : null;
        if (paymentType) {
            window.dataLayer.push({ event: 'add_payment_info', ecommerce: Object.assign({}, base, { payment_type: paymentType }) });
        }
    }

    checkoutForm.addEventListener('submit', function (e) {
        var paymentMethod = checkoutForm.querySelector('input[name="payment_method"]:checked');
        var submitBtn = document.getElementById('placeOrderBtn');
        var isRazorpay = paymentMethod && paymentMethod.value === 'razorpay';

        if (!isRazorpay) {
            if (checkoutForm.dataset.submitting) {
                e.preventDefault();
                return;
            }
            checkoutForm.dataset.submitting = '1';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing your order...';
            }
            return;
        }

        e.preventDefault();
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Processing...'; }

        var formData = new FormData(checkoutForm);
        if (billingSame && billingSame.checked) {
            formData.set('billing_same', '1');
        } else if (billingSame) {
            formData.delete('billing_same');
        }

        fetch(checkoutForm.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.success || !data.razorpay_order_id) {
                var hasErrors = false;
                if (data.errors) {
                    hasErrors = true;
                    if (typeof window.vrInlineErrors === 'function') {
                        window.vrInlineErrors(data.errors);
                    }
                }
                if (!hasErrors) {
                    vrToast(data.message || 'Payment initialisation failed.', 'error');
                }
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Place Order'; }
                return;
            }

            fireCheckoutFunnel();

            if (typeof Razorpay === 'undefined') {
                var s = document.createElement('script');
                s.src = 'https://checkout.razorpay.com/v1/checkout.js';
                s.onload = function () { openRazorpay(data); };
                s.onerror = function () {
                    vrToast('Failed to load payment gateway. Please try again.', 'error');
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Place Order'; }
                };
                document.head.appendChild(s);
                return;
            }

            openRazorpay(data);
        }).catch(function () {
            checkoutForm.submit();
        });
    });
}

function openRazorpay(data) {
    var rzp = new Razorpay({
        key: data.key_id,
        amount: data.amount_paisa,
        currency: data.currency || 'INR',
        name: '{{ store_name() }}',
        description: 'Order Payment',
        order_id: data.razorpay_order_id,
        handler: function (response) {
            verifyPayment(response, data.order_id);
        },
        prefill: {
            name: '{{ auth()->user()->name ?? '' }}',
            email: '{{ auth()->user()->email ?? '' }}'
        },
        theme: { color: '#263D25' },
        modal: {
            ondismiss: function () {
                // User closed the payment dialog — redirect to pending
                window.location.href = '{{ url("/checkout/pending") }}/' + data.order_id;
            }
        }
    });
    rzp.open();
}

function verifyPayment(response, orderId) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("checkout.verify") }}';

    var fields = {
        'razorpay_order_id': response.razorpay_order_id,
        'razorpay_payment_id': response.razorpay_payment_id,
        'razorpay_signature': response.razorpay_signature,
        'order_id': orderId,
        '_token': document.querySelector('meta[name="csrf-token"]').content
    };

    Object.keys(fields).forEach(function (name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = fields[name];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('checkoutForm');
    if (!form) return;

    var endpoint = '{{ route("checkout.validate") }}';
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var debounceTimers = {};

    function runCheck(input) {
        var name = input.name;
        var value = input.value;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ _token: token, [name]: value })
        }).then(function (r) {
            return r.json();
        }).then(function (data) {
            if (!data || typeof data.valid === 'undefined') return;
            if (data.valid) {
                input.classList.remove('is-invalid');
                var fb = input.parentElement.querySelector('.invalid-feedback');
                if (fb) fb.textContent = '';
            } else if (data.errors) {
                if (typeof window.vrInlineErrors === 'function') {
                    window.vrInlineErrors({ [name]: data.errors[name] || [] }, form);
                }
            }
        }).catch(function () {
            // transient / offline — full validation runs again on submit
        });
    }

    var textFields = Array.prototype.filter.call(form.elements, function (el) {
        return el.name && (el.name.indexOf('shipping_') === 0 || el.name.indexOf('billing_') === 0);
    });

    textFields.forEach(function (input) {
        input.addEventListener('blur', function () {
            runCheck(input);
        });

        input.addEventListener('input', function () {
            if (input.name.indexOf('mobile') === -1 && input.name.indexOf('pincode') === -1) return;
            var key = input.name;
            clearTimeout(debounceTimers[key]);
            debounceTimers[key] = setTimeout(function () {
                runCheck(input);
            }, 500);
        });
    });
})();
</script>
@endpush

@if ($beginCheckoutPayload)
    @push('scripts')
    <script>
    window.vrCheckoutAnalytics = {!! json_encode(['ecommerce' => $checkoutEcommerce], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    window.dataLayer.push({!! json_encode($beginCheckoutPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    </script>
    @endpush
@endif