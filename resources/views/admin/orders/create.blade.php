@extends('admin.layouts.app')

@section('title', 'Create Manual Order')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0 fw-bold">Create Manual Order</h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">Backoffice Order Desk</span>
        </div>
        <div class="text-muted small mt-0.5">Create and dispatch manual orders directly for telephone, offline, WhatsApp or VIP customers.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i><span>Back to Orders</span>
        </a>
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

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-x-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('admin.orders.store') }}" id="manualOrderForm" novalidate>
    @csrf

    <div class="row g-3">
        {{-- Left Main Column --}}
        <div class="col-lg-8">
            {{-- Card 1: Customer Information --}}
            <div class="card border-0 shadow-xs mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;font-size:0.8rem;">1</span>
                        <span>Customer Details</span>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="customer_type" id="typeExisting" value="existing" {{ old('customer_type', 'existing') === 'existing' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="typeExisting"><i class="bi bi-search me-1"></i>Existing Customer</label>

                        <input type="radio" class="btn-check" name="customer_type" id="typeNew" value="new" {{ old('customer_type') === 'new' ? 'checked' : '' }}>
                        <label class="btn btn-outline-secondary" for="typeNew"><i class="bi bi-person-plus me-1"></i>New Customer</label>
                    </div>
                </div>
                <div class="card-body p-3">
                    {{-- Existing Customer Search Section --}}
                    <div id="existingCustomerSection" class="{{ old('customer_type', 'existing') === 'existing' ? '' : 'd-none' }}">
                        <div class="position-relative mb-2">
                            <label class="form-label small fw-semibold text-muted">Search Registered Customer</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="customerSearchInput" class="form-control border-start-0" placeholder="Type customer name, 10-digit mobile, or email..." autocomplete="off">
                                <button type="button" id="clearCustomerSearchBtn" class="btn btn-outline-secondary d-none"><i class="bi bi-x"></i></button>
                            </div>
                            <div id="customerSearchResults" class="list-group position-absolute w-100 shadow-sm border mt-1 d-none" style="z-index: 1050; max-height: 250px; overflow-y: auto;"></div>
                        </div>

                        {{-- Selected Customer Card --}}
                        <div id="selectedCustomerCard" class="{{ old('user_id') ? '' : 'd-none' }} p-2.5 rounded bg-light border mb-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center fw-bold" id="customerAvatar" style="width:36px;height:36px;">C</div>
                                    <div>
                                        <div class="fw-bold small text-dark" id="customerDisplayName">{{ old('customer_name', 'Customer') }}</div>
                                        <div class="text-muted" style="font-size:0.75rem;">
                                            <span id="customerDisplayPhone">{{ old('customer_phone') }}</span> &bull;
                                            <span id="customerDisplayEmail">{{ old('customer_email', 'No email') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-danger" id="detachCustomerBtn" title="Clear selection">
                                    <i class="bi bi-x-circle me-1"></i>Change
                                </button>
                            </div>

                            {{-- Saved Addresses Quick Pick --}}
                            <div id="savedAddressesWrapper" class="mt-2 pt-2 border-top d-none">
                                <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Use a Saved Address:</label>
                                <select id="savedAddressSelector" class="form-select form-select-sm">
                                    <option value="">-- Choose from saved addresses --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Customer Form Fields --}}
                    <input type="hidden" name="user_id" id="userIdInput" value="{{ old('user_id') }}">

                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" id="customerNameInput" class="form-control form-control-sm" value="{{ old('customer_name') }}" placeholder="e.g. Priya Sharma" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">10-Digit Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="tel" name="customer_phone" id="customerPhoneInput" class="form-control" value="{{ old('customer_phone') }}" placeholder="9876543210" maxlength="15" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Email Address</label>
                            <input type="email" name="customer_email" id="customerEmailInput" class="form-control form-control-sm" value="{{ old('customer_email') }}" placeholder="customer@email.com">
                        </div>
                    </div>

                    <div class="form-check mt-2" id="saveAddressCheckWrapper">
                        <input class="form-check-input" type="checkbox" name="save_customer_address" id="saveCustomerAddressCheck" value="1" {{ old('save_customer_address', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted" for="saveCustomerAddressCheck">
                            Save address to customer's address book for future orders
                        </label>
                    </div>
                </div>
            </div>

            {{-- Card 2: Shipping & Delivery Address --}}
            <div class="card border-0 shadow-xs mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;font-size:0.8rem;">2</span>
                        <span>Delivery &amp; Billing Address</span>
                    </div>
                    <span class="text-muted small"><i class="bi bi-truck me-1"></i>Destination Details</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Recipient Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_name" id="shippingNameInput" class="form-control form-control-sm" value="{{ old('shipping_name') }}" placeholder="Name on parcel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Recipient Mobile <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">+91</span>
                                <input type="tel" name="shipping_mobile" id="shippingMobileInput" class="form-control" value="{{ old('shipping_mobile') }}" placeholder="10-digit mobile" maxlength="15" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">PIN Code <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm position-relative">
                                <input type="text" name="shipping_pincode" id="shippingPincodeInput" class="form-control" value="{{ old('shipping_pincode') }}" placeholder="6-digit PIN" maxlength="6" pattern="[1-9][0-9]{5}" required>
                                <button type="button" class="btn btn-outline-secondary" id="lookupPinBtn" title="Auto-detect city/state">
                                    <i class="bi bi-geo-alt"></i>
                                </button>
                            </div>
                            <div id="pincodeFeedback" class="small text-muted mt-0.5" style="font-size:0.72rem;"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">City / District <span class="text-danger">*</span></label>
                            <input type="text" name="shipping_city" id="shippingCityInput" class="form-control form-control-sm" value="{{ old('shipping_city') }}" placeholder="City" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">State <span class="text-danger">*</span></label>
                            <select name="shipping_state" id="shippingStateSelect" class="form-select form-select-sm" required>
                                <option value="">-- Select State --</option>
                                @foreach ($states as $code => $stateInfo)
                                    @php $stateName = is_array($stateInfo) ? $stateInfo[0] : $stateInfo; @endphp
                                    <option value="{{ $stateName }}" {{ old('shipping_state') === $stateName ? 'selected' : '' }}>{{ $stateName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted">Flat, House No., Building, Apartment <span class="text-danger">*</span></label>
                        <input type="text" name="shipping_address_line1" id="shippingAddress1Input" class="form-control form-control-sm" value="{{ old('shipping_address_line1') }}" placeholder="e.g. Flat 402, Lotus Residency, MG Road" required>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Area, Sector, Street</label>
                            <input type="text" name="shipping_address_line2" id="shippingAddress2Input" class="form-control form-control-sm" value="{{ old('shipping_address_line2') }}" placeholder="e.g. Near Civic Center">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Landmark</label>
                            <input type="text" name="shipping_landmark" id="shippingLandmarkInput" class="form-control form-control-sm" value="{{ old('shipping_landmark') }}" placeholder="e.g. Opposite ICICI Bank">
                        </div>
                    </div>

                    <input type="hidden" name="shipping_country" value="India">

                    {{-- Billing Same Toggle --}}
                    <div class="pt-2 border-top mt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_billing_same" id="isBillingSameCheck" value="1" {{ old('is_billing_same', '1') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="isBillingSameCheck">Billing address is identical to delivery address</label>
                        </div>
                    </div>

                    {{-- Collapsible Billing Address --}}
                    <div id="billingAddressSection" class="{{ old('is_billing_same', '1') ? 'd-none' : '' }} mt-3 pt-3 border-top bg-light p-3 rounded">
                        <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-receipt me-1"></i>Custom Billing Address</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Billing Name</label>
                                <input type="text" name="billing_name" id="billingNameInput" class="form-control form-control-sm" value="{{ old('billing_name') }}" placeholder="Billing entity name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Billing Phone</label>
                                <input type="tel" name="billing_mobile" id="billingMobileInput" class="form-control form-control-sm" value="{{ old('billing_mobile') }}" placeholder="10-digit mobile">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">PIN Code</label>
                                <input type="text" name="billing_pincode" id="billingPincodeInput" class="form-control form-control-sm" value="{{ old('billing_pincode') }}" placeholder="PIN Code" maxlength="6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">City</label>
                                <input type="text" name="billing_city" id="billingCityInput" class="form-control form-control-sm" value="{{ old('billing_city') }}" placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">State</label>
                                <select name="billing_state" id="billingStateSelect" class="form-select form-select-sm">
                                    <option value="">-- Select State --</option>
                                    @foreach ($states as $code => $stateInfo)
                                        @php $stateName = is_array($stateInfo) ? $stateInfo[0] : $stateInfo; @endphp
                                        <option value="{{ $stateName }}" {{ old('billing_state') === $stateName ? 'selected' : '' }}>{{ $stateName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted">Billing Street Address</label>
                            <input type="text" name="billing_address_line1" id="billingAddress1Input" class="form-control form-control-sm" value="{{ old('billing_address_line1') }}" placeholder="Address line 1">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3: Line Items Builder --}}
            <div class="card border-0 shadow-xs mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;font-size:0.8rem;">3</span>
                        <span>Order Items &amp; Products</span>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary" id="itemsCountBadge">0 Item(s)</span>
                </div>
                <div class="card-body p-3">
                    {{-- Product Search Bar --}}
                    <div class="position-relative mb-3">
                        <label class="form-label small fw-semibold text-muted">Search &amp; Add Products</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-box-seam text-muted"></i></span>
                            <input type="text" id="productSearchInput" class="form-control border-start-0" placeholder="Type product name or SKU to add items..." autocomplete="off">
                            <button type="button" id="clearProductSearchBtn" class="btn btn-outline-secondary d-none"><i class="bi bi-x"></i></button>
                        </div>
                        <div id="productSearchResults" class="list-group position-absolute w-100 shadow border mt-1 d-none" style="z-index: 1050; max-height: 280px; overflow-y: auto;"></div>
                    </div>

                    {{-- Items Table --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="orderItemsTable">
                            <thead class="table-light">
                                <tr style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th style="min-width: 220px;">Product / SKU</th>
                                    <th class="text-center" style="width: 100px;">Stock</th>
                                    <th class="text-end" style="width: 100px;">MRP</th>
                                    <th class="text-end" style="width: 120px;">Unit Price (₹)</th>
                                    <th class="text-center" style="width: 130px;">Quantity</th>
                                    <th class="text-end" style="width: 120px;">Total</th>
                                    <th class="text-center" style="width: 45px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyItemsRow">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-basket3 fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                        <span class="small">No products added yet. Use the search bar above to select products.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Card 4: Internal Notes --}}
            <div class="card border-0 shadow-xs mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-2.5 border-bottom fw-bold small text-muted">
                    <i class="bi bi-card-text me-1"></i>Order Notes &amp; Special Instructions
                </div>
                <div class="card-body p-3">
                    <textarea name="notes" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="e.g. Customer requested urgent dispatch via Bluedart. Offline payment verified via phone call.">{{ old('notes') }}</textarea>
                    <div class="text-muted mt-1" style="font-size:0.72rem;">These notes are recorded internally and attached to the order audit trail.</div>
                </div>
            </div>
        </div>

        {{-- Right Sidebar Column --}}
        <div class="col-lg-4">
            {{-- Card 5: Pricing & Calculations (Sticky) --}}
            <div class="card border-0 shadow-xs mb-3" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold"><i class="bi bi-calculator me-1"></i>Order Summary</span>
                    <span class="badge bg-light text-dark border">INR (₹)</span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Items Subtotal:</span>
                        <span class="fw-bold small" id="summarySubtotal">₹0.00</span>
                    </div>

                    {{-- Manual Discount --}}
                    <div class="mb-2.5 pt-2 border-top">
                        <label class="form-label small fw-semibold text-muted d-flex justify-content-between align-items-center mb-1">
                            <span>Order Discount</span>
                            <span class="text-success small fw-semibold" id="summaryDiscountText">- ₹0.00</span>
                        </label>
                        <div class="input-group input-group-sm mb-1">
                            <select name="discount_type" id="discountTypeSelect" class="form-select" style="max-width: 105px;">
                                <option value="none" {{ old('discount_type') === 'none' ? 'selected' : '' }}>None</option>
                                <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Flat (₹)</option>
                                <option value="percent" {{ old('discount_type') === 'percent' ? 'selected' : '' }}>Percent (%)</option>
                            </select>
                            <input type="number" step="0.01" min="0" name="discount_value" id="discountValueInput" class="form-control" value="{{ old('discount_value', '0') }}" placeholder="Value" disabled>
                        </div>
                    </div>

                    {{-- Coupon Code (Optional) --}}
                    <div class="mb-2.5">
                        <label class="form-label small fw-semibold text-muted mb-1">Coupon Code (Optional)</label>
                        <select name="coupon_code" id="couponCodeSelect" class="form-select form-select-sm">
                            <option value="">-- No coupon applied --</option>
                            @foreach ($coupons as $c)
                                <option value="{{ $c->code }}" data-type="{{ $c->discount_type }}" data-value="{{ $c->discount_value }}" data-max="{{ $c->max_discount }}" {{ old('coupon_code') === $c->code ? 'selected' : '' }}>
                                    {{ $c->code }} ({{ $c->discount_type === 'percent' ? $c->discount_value.'%' : '₹'.$c->discount_value }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Shipping Charge --}}
                    <div class="mb-2.5 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small fw-semibold">Shipping Charge</span>
                            <span class="small fw-semibold" id="summaryShippingText">₹0.00</span>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" step="0.01" min="0" name="shipping_charge" id="shippingChargeInput" class="form-control" value="{{ old('shipping_charge', '0.00') }}" placeholder="0.00">
                        </div>
                    </div>

                    {{-- Estimated Tax Breakdown --}}
                    <div class="p-2 bg-light rounded small mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted" style="font-size: 0.75rem;">Estimated GST (Inclusive):</span>
                            <span class="fw-semibold text-muted" id="summaryTaxAmount" style="font-size: 0.75rem;">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.7rem;" id="taxSplitDetail">
                            <span>Tax Type:</span>
                            <span id="taxSplitLabel">State Intra/Inter</span>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 rounded mb-3" style="background:#ecfdf5; border:1px solid #a7f3d0;">
                        <div>
                            <div class="fw-bold text-success" style="font-size: 1.15rem;">Grand Total:</div>
                            <div class="text-muted" style="font-size: 0.7rem;">GST-Inclusive Net Payable</div>
                        </div>
                        <div class="fw-bolder text-success text-end" style="font-size: 1.35rem;" id="summaryGrandTotal">₹0.00</div>
                    </div>

                    {{-- Card 6: Payment Configuration --}}
                    <div class="pt-2 border-top mb-3">
                        <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-wallet2 me-1"></i>Payment Mode</h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm" required>
                                @foreach ($paymentMethods as $pmKey => $pmLabel)
                                    <option value="{{ $pmKey }}" {{ old('payment_method', 'cod') === $pmKey ? 'selected' : '' }}>{{ $pmLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted">Payment Status <span class="text-danger">*</span></label>
                            <select name="payment_status" id="paymentStatusSelect" class="form-select form-select-sm" required>
                                <option value="pending" {{ old('payment_status', 'pending') === 'pending' ? 'selected' : '' }}>Pending (Collect on Delivery / Unsettled)</option>
                                <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid (Payment Collected / Settled)</option>
                            </select>
                        </div>

                        <div id="paymentRefWrapper" class="mb-2 {{ old('payment_status') === 'paid' ? '' : 'd-none' }}">
                            <label class="form-label small fw-semibold text-muted">Transaction / UTR Reference</label>
                            <input type="text" name="payment_reference" id="paymentReferenceInput" class="form-control form-control-sm" value="{{ old('payment_reference') }}" placeholder="e.g. UPI-9823472648 or UTR12345678">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted">Initial Order Status</label>
                            <select name="order_status" id="orderStatusSelect" class="form-select form-select-sm" required>
                                <option value="confirmed" {{ old('order_status', 'confirmed') === 'confirmed' ? 'selected' : '' }}>Confirmed (Ready for Packing)</option>
                                <option value="processing" {{ old('order_status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="pending" {{ old('order_status') === 'pending' ? 'selected' : '' }}>Pending (Draft / Unconfirmed)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Card 7: Automation & Action Checkboxes --}}
                    <div class="pt-2 border-top mb-3">
                        <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Automations</h6>
                        <div class="form-check mb-1.5">
                            <input class="form-check-input" type="checkbox" name="reduce_stock" id="reduceStockCheck" value="1" {{ old('reduce_stock', '1') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="reduceStockCheck">
                                Deduct inventory stock immediately
                            </label>
                        </div>
                        <div class="form-check mb-1.5">
                            <input class="form-check-input" type="checkbox" name="send_whatsapp" id="sendWhatsAppCheck" value="1" {{ old('send_whatsapp', '1') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="sendWhatsAppCheck">
                                Send WhatsApp order confirmation to customer
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="push_shipmojo" id="pushShipMojoCheck" value="1" {{ old('push_shipmojo') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="pushShipMojoCheck">
                                Push order to ShipMojo courier system
                            </label>
                        </div>
                    </div>

                    {{-- Submit CTA --}}
                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="submitOrderBtn">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Create &amp; Dispatch Order</span>
                    </button>
                    <div class="text-center text-muted mt-2" style="font-size:0.7rem;">
                        <i class="bi bi-shield-check me-1"></i>Generates order invoice and inventory log atomically.
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    // State management
    let selectedItems = []; // Array of { product_id, product_variant_id, name, sku, mrp, unit_price, quantity, stock, gst_rate, image_url }
    let customerAddresses = [];

    // Elements
    const customerTypeRadios = document.querySelectorAll('input[name="customer_type"]');
    const existingSection = document.getElementById('existingCustomerSection');
    const customerSearchInput = document.getElementById('customerSearchInput');
    const customerSearchResults = document.getElementById('customerSearchResults');
    const clearCustomerSearchBtn = document.getElementById('clearCustomerSearchBtn');
    const selectedCustomerCard = document.getElementById('selectedCustomerCard');
    const detachCustomerBtn = document.getElementById('detachCustomerBtn');
    const customerAvatar = document.getElementById('customerAvatar');
    const customerDisplayName = document.getElementById('customerDisplayName');
    const customerDisplayPhone = document.getElementById('customerDisplayPhone');
    const customerDisplayEmail = document.getElementById('customerDisplayEmail');
    const savedAddressesWrapper = document.getElementById('savedAddressesWrapper');
    const savedAddressSelector = document.getElementById('savedAddressSelector');
    const userIdInput = document.getElementById('userIdInput');
    const customerNameInput = document.getElementById('customerNameInput');
    const customerPhoneInput = document.getElementById('customerPhoneInput');
    const customerEmailInput = document.getElementById('customerEmailInput');

    const shippingNameInput = document.getElementById('shippingNameInput');
    const shippingMobileInput = document.getElementById('shippingMobileInput');
    const shippingPincodeInput = document.getElementById('shippingPincodeInput');
    const shippingCityInput = document.getElementById('shippingCityInput');
    const shippingStateSelect = document.getElementById('shippingStateSelect');
    const shippingAddress1Input = document.getElementById('shippingAddress1Input');
    const shippingAddress2Input = document.getElementById('shippingAddress2Input');
    const shippingLandmarkInput = document.getElementById('shippingLandmarkInput');
    const pincodeFeedback = document.getElementById('pincodeFeedback');
    const lookupPinBtn = document.getElementById('lookupPinBtn');

    const isBillingSameCheck = document.getElementById('isBillingSameCheck');
    const billingAddressSection = document.getElementById('billingAddressSection');

    const productSearchInput = document.getElementById('productSearchInput');
    const productSearchResults = document.getElementById('productSearchResults');
    const clearProductSearchBtn = document.getElementById('clearProductSearchBtn');
    const itemsTableBody = document.getElementById('itemsTableBody');
    const emptyItemsRow = document.getElementById('emptyItemsRow');
    const itemsCountBadge = document.getElementById('itemsCountBadge');

    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryDiscountText = document.getElementById('summaryDiscountText');
    const discountTypeSelect = document.getElementById('discountTypeSelect');
    const discountValueInput = document.getElementById('discountValueInput');
    const couponCodeSelect = document.getElementById('couponCodeSelect');
    const shippingChargeInput = document.getElementById('shippingChargeInput');
    const summaryShippingText = document.getElementById('summaryShippingText');
    const summaryTaxAmount = document.getElementById('summaryTaxAmount');
    const summaryGrandTotal = document.getElementById('summaryGrandTotal');

    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    const paymentStatusSelect = document.getElementById('paymentStatusSelect');
    const paymentRefWrapper = document.getElementById('paymentRefWrapper');
    const manualOrderForm = document.getElementById('manualOrderForm');
    const submitOrderBtn = document.getElementById('submitOrderBtn');

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Toggle customer type radio
    customerTypeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'existing') {
                existingSection.classList.remove('d-none');
            } else {
                existingSection.classList.add('d-none');
                clearCustomerSelection();
            }
        });
    });

    // Customer Search Autocomplete
    customerSearchInput.addEventListener('input', debounce(function () {
        const query = this.value.trim();
        if (query.length < 2) {
            customerSearchResults.classList.add('d-none');
            customerSearchResults.innerHTML = '';
            clearCustomerSearchBtn.classList.add('d-none');
            return;
        }

        clearCustomerSearchBtn.classList.remove('d-none');
        fetch(`{{ route('admin.orders.search-customers') }}?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(users => {
            customerSearchResults.innerHTML = '';
            if (!users || users.length === 0) {
                customerSearchResults.innerHTML = '<div class="list-group-item text-muted small p-2">No matching customers found.</div>';
                customerSearchResults.classList.remove('d-none');
                return;
            }

            users.forEach(user => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action p-2 d-flex align-items-center justify-content-between';
                item.innerHTML = `
                    <div>
                        <div class="fw-bold small text-dark">${escapeHtml(user.name)}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-telephone me-1"></i>${escapeHtml(user.phone || 'No phone')} &bull;
                            <i class="bi bi-envelope me-1"></i>${escapeHtml(user.email || 'No email')}
                        </div>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">${user.orders_count} orders</span>
                `;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    selectCustomer(user);
                });
                customerSearchResults.appendChild(item);
            });
            customerSearchResults.classList.remove('d-none');
        })
        .catch(() => {
            customerSearchResults.classList.add('d-none');
        });
    }, 250));

    clearCustomerSearchBtn.addEventListener('click', function () {
        customerSearchInput.value = '';
        customerSearchResults.classList.add('d-none');
        this.classList.add('d-none');
    });

    function selectCustomer(user) {
        userIdInput.value = user.id;
        customerNameInput.value = user.name || '';
        customerPhoneInput.value = user.phone ? user.phone.replace(/\D/g, '').slice(-10) : '';
        customerEmailInput.value = user.email || '';

        // Auto fill shipping recipient with customer details
        if (!shippingNameInput.value) shippingNameInput.value = user.name || '';
        if (!shippingMobileInput.value && user.phone) shippingMobileInput.value = user.phone.replace(/\D/g, '').slice(-10);

        customerDisplayName.textContent = user.name || 'Customer';
        customerDisplayPhone.textContent = user.phone ? '+91 ' + user.phone.replace(/\D/g, '').slice(-10) : 'No phone';
        customerDisplayEmail.textContent = user.email || 'No email';
        customerAvatar.textContent = (user.name || 'C').charAt(0).toUpperCase();

        selectedCustomerCard.classList.remove('d-none');
        customerSearchResults.classList.add('d-none');
        customerSearchInput.value = '';
        clearCustomerSearchBtn.classList.add('d-none');

        // Populate saved addresses if any
        customerAddresses = user.addresses || [];
        savedAddressSelector.innerHTML = '<option value="">-- Choose from saved addresses --</option>';
        if (customerAddresses.length > 0) {
            customerAddresses.forEach((addr, idx) => {
                const opt = document.createElement('option');
                opt.value = idx;
                opt.textContent = `${addr.full_name} - ${addr.address_line1}, ${addr.city} (${addr.pincode})${addr.is_default ? ' [Default]' : ''}`;
                savedAddressSelector.appendChild(opt);
            });
            savedAddressesWrapper.classList.remove('d-none');

            // Pre-select default address if exists
            const defaultIdx = customerAddresses.findIndex(a => a.is_default);
            if (defaultIdx !== -1) {
                savedAddressSelector.value = defaultIdx;
                applySavedAddress(customerAddresses[defaultIdx]);
            } else if (customerAddresses.length === 1) {
                savedAddressSelector.value = 0;
                applySavedAddress(customerAddresses[0]);
            }
        } else {
            savedAddressesWrapper.classList.add('d-none');
        }
    }

    savedAddressSelector.addEventListener('change', function () {
        const idx = this.value;
        if (idx !== '' && customerAddresses[idx]) {
            applySavedAddress(customerAddresses[idx]);
        }
    });

    function applySavedAddress(addr) {
        if (addr.full_name) shippingNameInput.value = addr.full_name;
        if (addr.mobile) shippingMobileInput.value = addr.mobile.replace(/\D/g, '').slice(-10);
        if (addr.address_line1) shippingAddress1Input.value = addr.address_line1;
        if (addr.address_line2) shippingAddress2Input.value = addr.address_line2;
        if (addr.landmark) shippingLandmarkInput.value = addr.landmark;
        if (addr.city) shippingCityInput.value = addr.city;
        if (addr.pincode) shippingPincodeInput.value = addr.pincode;
        if (addr.state) {
            shippingStateSelect.value = addr.state;
            recalculateSummary();
        }
    }

    detachCustomerBtn.addEventListener('click', function () {
        clearCustomerSelection();
    });

    function clearCustomerSelection() {
        userIdInput.value = '';
        selectedCustomerCard.classList.add('d-none');
        savedAddressesWrapper.classList.add('d-none');
        customerAddresses = [];
    }

    // Auto-sync customer name & phone to shipping if shipping was empty
    customerNameInput.addEventListener('input', function () {
        if (!shippingNameInput.value || shippingNameInput.dataset.synced === 'true') {
            shippingNameInput.value = this.value;
            shippingNameInput.dataset.synced = 'true';
        }
    });
    shippingNameInput.addEventListener('input', function () {
        shippingNameInput.dataset.synced = 'false';
    });

    customerPhoneInput.addEventListener('input', function () {
        const clean = this.value.replace(/\D/g, '').slice(-10);
        if (!shippingMobileInput.value || shippingMobileInput.dataset.synced === 'true') {
            shippingMobileInput.value = clean;
            shippingMobileInput.dataset.synced = 'true';
        }
    });
    shippingMobileInput.addEventListener('input', function () {
        shippingMobileInput.dataset.synced = 'false';
    });

    // Pincode auto-lookup
    shippingPincodeInput.addEventListener('input', function () {
        const pin = this.value.replace(/\D/g, '');
        if (pin.length === 6) {
            lookupPincode(pin);
        } else {
            pincodeFeedback.textContent = '';
        }
    });

    lookupPinBtn.addEventListener('click', function () {
        const pin = shippingPincodeInput.value.replace(/\D/g, '');
        if (pin.length === 6) {
            lookupPincode(pin);
        } else {
            pincodeFeedback.textContent = 'Please enter a 6-digit PIN code';
            pincodeFeedback.className = 'small text-danger mt-0.5';
        }
    });

    function lookupPincode(pincode) {
        pincodeFeedback.textContent = 'Looking up location...';
        pincodeFeedback.className = 'small text-muted mt-0.5';

        fetch(`{{ route('admin.orders.lookup-pincode') }}?pincode=${pincode}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && (data.city || data.state)) {
                if (data.city && !shippingCityInput.value) {
                    shippingCityInput.value = data.city;
                }
                if (data.state) {
                    // Match state in select
                    let matched = false;
                    for (let i = 0; i < shippingStateSelect.options.length; i++) {
                        if (shippingStateSelect.options[i].value.toLowerCase() === data.state.toLowerCase()) {
                            shippingStateSelect.selectedIndex = i;
                            matched = true;
                            break;
                        }
                    }
                }
                pincodeFeedback.textContent = `Location: ${data.city ? data.city + ', ' : ''}${data.state || ''}`;
                pincodeFeedback.className = 'small text-success mt-0.5';
                recalculateSummary();
            } else {
                pincodeFeedback.textContent = 'Pincode not found in directory. Please enter city/state manually.';
                pincodeFeedback.className = 'small text-warning mt-0.5';
            }
        })
        .catch(() => {
            pincodeFeedback.textContent = '';
        });
    }

    // Toggle billing address
    isBillingSameCheck.addEventListener('change', function () {
        if (this.checked) {
            billingAddressSection.classList.add('d-none');
        } else {
            billingAddressSection.classList.remove('d-none');
        }
    });

    // Product Search Autocomplete
    productSearchInput.addEventListener('input', debounce(function () {
        const query = this.value.trim();
        if (query.length < 1) {
            productSearchResults.classList.add('d-none');
            productSearchResults.innerHTML = '';
            clearProductSearchBtn.classList.add('d-none');
            return;
        }

        clearProductSearchBtn.classList.remove('d-none');
        fetch(`{{ route('admin.orders.search-products') }}?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(products => {
            productSearchResults.innerHTML = '';
            if (!products || products.length === 0) {
                productSearchResults.innerHTML = '<div class="list-group-item text-muted small p-2">No matching products found.</div>';
                productSearchResults.classList.remove('d-none');
                return;
            }

            products.forEach(product => {
                if (product.has_variants && product.variants.length > 0) {
                    product.variants.forEach(variant => {
                        const item = createProductResultItem(
                            product.id,
                            variant.id,
                            `${product.name} (${variant.name})`,
                            variant.sku || product.sku,
                            variant.mrp || product.mrp,
                            variant.selling_price || product.selling_price,
                            variant.stock,
                            variant.gst_rate || product.gst_rate,
                            product.image_url
                        );
                        productSearchResults.appendChild(item);
                    });
                } else {
                    const item = createProductResultItem(
                        product.id,
                        null,
                        product.name,
                        product.sku,
                        product.mrp,
                        product.selling_price,
                        product.stock,
                        product.gst_rate,
                        product.image_url
                    );
                    productSearchResults.appendChild(item);
                }
            });
            productSearchResults.classList.remove('d-none');
        })
        .catch(() => {
            productSearchResults.classList.add('d-none');
        });
    }, 250));

    clearProductSearchBtn.addEventListener('click', function () {
        productSearchInput.value = '';
        productSearchResults.classList.add('d-none');
        this.classList.add('d-none');
    });

    function createProductResultItem(productId, variantId, name, sku, mrp, price, stock, gstRate, imageUrl) {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action p-2 d-flex align-items-center justify-content-between';
        const stockBadge = stock > 0
            ? `<span class="badge bg-success-subtle text-success">${stock} in stock</span>`
            : `<span class="badge bg-danger-subtle text-danger">Out of stock</span>`;

        item.innerHTML = `
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

        item.addEventListener('click', function (e) {
            e.preventDefault();
            addItemToOrder({
                product_id: productId,
                product_variant_id: variantId,
                name: name,
                sku: sku || 'N/A',
                mrp: Number(mrp) || 0,
                unit_price: Number(price) || 0,
                quantity: 1,
                stock: Number(stock) || 0,
                gst_rate: Number(gstRate) || 0,
                image_url: imageUrl
            });
            productSearchResults.classList.add('d-none');
            productSearchInput.value = '';
            clearProductSearchBtn.classList.add('d-none');
        });

        return item;
    }

    function addItemToOrder(newItem) {
        // Check if item already exists in selectedItems
        const existingIdx = selectedItems.findIndex(i =>
            i.product_id === newItem.product_id && i.product_variant_id === newItem.product_variant_id
        );

        if (existingIdx !== -1) {
            selectedItems[existingIdx].quantity += 1;
        } else {
            selectedItems.push(newItem);
        }

        renderItemsTable();
    }

    function removeItem(idx) {
        selectedItems.splice(idx, 1);
        renderItemsTable();
    }

    function updateItemQuantity(idx, qty) {
        qty = Math.max(1, parseInt(qty, 10) || 1);
        selectedItems[idx].quantity = qty;
        renderItemsTable();
    }

    function updateItemPrice(idx, price) {
        price = Math.max(0, parseFloat(price) || 0);
        selectedItems[idx].unit_price = price;
        recalculateSummary();
    }

    function renderItemsTable() {
        if (selectedItems.length === 0) {
            emptyItemsRow.classList.remove('d-none');
            itemsTableBody.innerHTML = '';
            itemsTableBody.appendChild(emptyItemsRow);
            itemsCountBadge.textContent = '0 Item(s)';
            recalculateSummary();
            return;
        }

        emptyItemsRow.classList.add('d-none');
        itemsTableBody.innerHTML = '';
        itemsCountBadge.textContent = `${selectedItems.length} Item(s)`;

        selectedItems.forEach((item, idx) => {
            const tr = document.createElement('tr');
            const lineTotal = item.unit_price * item.quantity;
            const stockColor = item.stock > 0 ? 'text-success' : 'text-danger';

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="${item.image_url}" class="rounded border" style="width:36px;height:36px;object-fit:cover;" onerror="this.src='/images/placeholder.svg'">
                        <div>
                            <div class="fw-bold small text-dark">${escapeHtml(item.name)}</div>
                            <div class="text-muted" style="font-size:0.72rem;">SKU: ${escapeHtml(item.sku)}</div>
                        </div>
                    </div>
                    <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                    ${item.product_variant_id ? `<input type="hidden" name="items[${idx}][product_variant_id]" value="${item.product_variant_id}">` : ''}
                    <input type="hidden" name="items[${idx}][mrp]" value="${item.mrp}">
                </td>
                <td class="text-center small ${stockColor}">
                    ${item.stock} left
                </td>
                <td class="text-end small text-muted">
                    ₹${item.mrp.toFixed(2)}
                </td>
                <td class="text-end">
                    <div class="input-group input-group-sm ms-auto" style="max-width: 110px;">
                        <span class="input-group-text bg-light p-1" style="font-size:0.75rem;">₹</span>
                        <input type="number" step="0.01" min="0" name="items[${idx}][unit_price]" class="form-control text-end item-price-input" data-idx="${idx}" value="${item.unit_price.toFixed(2)}">
                    </div>
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 105px;">
                        <button type="button" class="btn btn-outline-secondary px-2 item-qty-minus" data-idx="${idx}"><i class="bi bi-dash"></i></button>
                        <input type="number" min="1" name="items[${idx}][quantity]" class="form-control text-center px-1 item-qty-input" data-idx="${idx}" value="${item.quantity}">
                        <button type="button" class="btn btn-outline-secondary px-2 item-qty-plus" data-idx="${idx}"><i class="bi bi-plus"></i></button>
                    </div>
                </td>
                <td class="text-end fw-bold small text-dark item-line-total" id="lineTotal_${idx}">
                    ₹${lineTotal.toFixed(2)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 item-remove-btn" data-idx="${idx}" title="Remove item">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            itemsTableBody.appendChild(tr);
        });

        // Attach event listeners to table inputs
        document.querySelectorAll('.item-qty-minus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.idx, 10);
                if (selectedItems[idx].quantity > 1) {
                    updateItemQuantity(idx, selectedItems[idx].quantity - 1);
                }
            });
        });

        document.querySelectorAll('.item-qty-plus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.idx, 10);
                updateItemQuantity(idx, selectedItems[idx].quantity + 1);
            });
        });

        document.querySelectorAll('.item-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.idx, 10);
                updateItemQuantity(idx, this.value);
            });
        });

        document.querySelectorAll('.item-price-input').forEach(input => {
            input.addEventListener('input', function () {
                const idx = parseInt(this.dataset.idx, 10);
                const val = parseFloat(this.value) || 0;
                selectedItems[idx].unit_price = val;
                const lineTotal = val * selectedItems[idx].quantity;
                const lineSpan = document.getElementById(`lineTotal_${idx}`);
                if (lineSpan) lineSpan.textContent = `₹${lineTotal.toFixed(2)}`;
                recalculateSummary();
            });
        });

        document.querySelectorAll('.item-remove-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.idx, 10);
                removeItem(idx);
            });
        });

        recalculateSummary();
    }

    // Calculations
    function recalculateSummary() {
        let subtotal = 0;
        let totalGstInclusive = 0;

        selectedItems.forEach(item => {
            const line = item.unit_price * item.quantity;
            subtotal += line;
            if (item.gst_rate > 0) {
                const tax = line - (line / (1 + (item.gst_rate / 100)));
                totalGstInclusive += tax;
            }
        });

        summarySubtotal.textContent = `₹${subtotal.toFixed(2)}`;

        // Discount calculation
        const discType = discountTypeSelect.value;
        let manualDiscount = 0;
        if (discType === 'fixed') {
            discountValueInput.disabled = false;
            manualDiscount = Math.min(subtotal, Math.max(0, parseFloat(discountValueInput.value) || 0));
        } else if (discType === 'percent') {
            discountValueInput.disabled = false;
            const pct = Math.max(0, parseFloat(discountValueInput.value) || 0);
            manualDiscount = (subtotal * pct) / 100;
        } else {
            discountValueInput.disabled = true;
            discountValueInput.value = '0';
        }

        // Coupon calculation
        let couponDiscount = 0;
        const selectedCoupon = couponCodeSelect.options[couponCodeSelect.selectedIndex];
        if (selectedCoupon && selectedCoupon.value) {
            const cType = selectedCoupon.dataset.type;
            const cVal = parseFloat(selectedCoupon.dataset.value) || 0;
            const cMax = parseFloat(selectedCoupon.dataset.max) || 0;
            if (cType === 'percent') {
                couponDiscount = (subtotal * cVal) / 100;
                if (cMax > 0 && couponDiscount > cMax) couponDiscount = cMax;
            } else {
                couponDiscount = Math.min(subtotal, cVal);
            }
        }

        const totalDiscount = Math.min(subtotal, manualDiscount + couponDiscount);
        summaryDiscountText.textContent = totalDiscount > 0 ? `- ₹${totalDiscount.toFixed(2)}` : '- ₹0.00';

        // Shipping charge
        const shipping = Math.max(0, parseFloat(shippingChargeInput.value) || 0);
        summaryShippingText.textContent = `₹${shipping.toFixed(2)}`;

        // Tax calculation with discount factor
        let discountedTax = 0;
        if (subtotal > 0 && totalDiscount > 0) {
            const factor = Math.max(0, (subtotal - totalDiscount) / subtotal);
            discountedTax = totalGstInclusive * factor;
        } else {
            discountedTax = totalGstInclusive;
        }
        summaryTaxAmount.textContent = `₹${discountedTax.toFixed(2)}`;

        // Intra vs Inter state label
        const selectedState = shippingStateSelect.value.trim().toLowerCase();
        // Typically intra-state is Rajasthan for VANRITI
        const isIntra = selectedState === 'rajasthan' || selectedState === 'rj';
        const taxSplitLabel = document.getElementById('taxSplitLabel');
        if (taxSplitLabel) {
            if (isIntra) {
                taxSplitLabel.textContent = `CGST (₹${(discountedTax / 2).toFixed(2)}) + SGST (₹${(discountedTax / 2).toFixed(2)})`;
            } else if (selectedState) {
                taxSplitLabel.textContent = `IGST (₹${discountedTax.toFixed(2)})`;
            } else {
                taxSplitLabel.textContent = 'Based on state selection';
            }
        }

        // Grand Total
        const grandTotal = Math.max(0, subtotal + shipping - totalDiscount);
        summaryGrandTotal.textContent = `₹${grandTotal.toFixed(2)}`;
    }

    discountTypeSelect.addEventListener('change', recalculateSummary);
    discountValueInput.addEventListener('input', recalculateSummary);
    couponCodeSelect.addEventListener('change', recalculateSummary);
    shippingChargeInput.addEventListener('input', recalculateSummary);
    shippingStateSelect.addEventListener('change', recalculateSummary);

    // Payment method & status toggles
    paymentMethodSelect.addEventListener('change', function () {
        if (this.value === 'cod') {
            paymentStatusSelect.value = 'pending';
            paymentRefWrapper.classList.add('d-none');
        } else {
            paymentStatusSelect.value = 'paid';
            paymentRefWrapper.classList.remove('d-none');
        }
    });

    paymentStatusSelect.addEventListener('change', function () {
        if (this.value === 'paid') {
            paymentRefWrapper.classList.remove('d-none');
        } else {
            paymentRefWrapper.classList.add('d-none');
        }
    });

    // Form submission validation
    manualOrderForm.addEventListener('submit', function (e) {
        if (selectedItems.length === 0) {
            e.preventDefault();
            alert('Please add at least one product item to create the order.');
            productSearchInput.focus();
            return false;
        }

        const phone = customerPhoneInput.value.replace(/\D/g, '');
        if (phone.length < 10) {
            e.preventDefault();
            alert('Please enter a valid 10-digit mobile number for the customer.');
            customerPhoneInput.focus();
            return false;
        }

        const pin = shippingPincodeInput.value.replace(/\D/g, '');
        if (pin.length !== 6) {
            e.preventDefault();
            alert('Please enter a valid 6-digit postal PIN code.');
            shippingPincodeInput.focus();
            return false;
        }

        // Disable submit button to prevent double-submit
        submitOrderBtn.disabled = true;
        submitOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Order...';
    });

    // Utility: HTML Escaping
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

    @if ($selectedUser)
        selectCustomer({
            id: {{ $selectedUser->id }},
            name: {!! json_encode($selectedUser->name) !!},
            phone: {!! json_encode($selectedUser->phone) !!},
            email: {!! json_encode($selectedUser->email) !!},
            orders_count: {{ $selectedUser->orders()->count() }},
            addresses: {!! json_encode($selectedUser->addresses->map(fn($a) => [
                'id' => $a->id,
                'full_name' => $a->full_name,
                'mobile' => $a->mobile,
                'address_line1' => $a->address_line1,
                'address_line2' => $a->address_line2,
                'landmark' => $a->landmark,
                'city' => $a->city,
                'state' => $a->state,
                'pincode' => $a->pincode,
                'country' => $a->country,
                'is_default' => $a->is_default,
                'full_address' => $a->full_address,
            ])) !!}
        });
    @endif
});
</script>
@endpush
