@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $renderSettingField = function ($setting) {
        $key = $setting->key;
        $label = $setting->label;
        $type = $setting->type;
        $val = setting($key);
@endphp
    @if ($type === 'boolean')
        <div class="p-2.5 rounded-3 border bg-light-subtle h-100 d-flex align-items-center justify-content-between">
            <div>
                <label class="form-check-label small fw-semibold cursor-pointer d-block" for="setting_{{ $key }}">
                    {{ $label }}
                </label>
            </div>
            <div class="form-check form-switch m-0 ps-0">
                <input type="checkbox" class="form-check-input" style="width: 2.2em; height: 1.2em; cursor: pointer;"
                    name="{{ $key }}" id="setting_{{ $key }}" value="1"
                    {{ $val === '1' || $val === true ? 'checked' : '' }}>
            </div>
        </div>
    @else
        <div>
            <label class="form-label small fw-semibold text-muted mb-1">{{ $label }}</label>
            @if ($type === 'textarea')
                <textarea name="{{ $key }}" class="form-control form-control-sm" rows="3">{{ $val }}</textarea>
            @elseif ($type === 'select')
                <select name="{{ $key }}" class="form-select form-select-sm">
                    <option value="inclusive" {{ $val === 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                    <option value="exclusive" {{ $val === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                </select>
            @elseif ($type === 'password')
                <input type="password" name="{{ $key }}" class="form-control form-control-sm" value="" autocomplete="new-password"
                    placeholder="{{ blank($val) ? '' : '•••••••• (leave blank to keep unchanged)' }}">
            @else
                <input type="{{ $type === 'number' ? 'number' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text')) }}"
                    name="{{ $key }}" class="form-control form-control-sm" value="{{ $val }}"
                    step="{{ $type === 'number' ? 'any' : null }}">
            @endif
        </div>
    @endif
@php
    };

    $checkoutGroups = ['orders', 'shipping', 'tax', 'returns'];
    $storeGroups = ['general', 'seo'];
    $knownGroups = ['general', 'seo', 'orders', 'shipping', 'tax', 'returns', 'payment', 'shipmojo', 'whatsapp'];
    $otherGroups = $settingsGrouped->keys()->diff($knownGroups);
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold">Store Configuration &amp; Integrations</h5>
            <div class="text-muted small mt-0.5">Manage store preferences, Razorpay, WhatsApp Meta API, and ShipMojo logistics</div>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3">
                <i class="bi bi-check2"></i><span>Save All Settings</span>
            </button>
        </div>
    </div>

    {{-- Modern Settings Navigation Tabs --}}
    <ul class="nav settings-nav-pills mb-4 flex-nowrap overflow-auto" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-store-btn" data-bs-toggle="pill" data-bs-target="#pane-store" type="button" role="tab">
                <i class="bi bi-shop"></i><span>Store Profile &amp; SEO</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-checkout-btn" data-bs-toggle="pill" data-bs-target="#pane-checkout" type="button" role="tab">
                <i class="bi bi-cart-check"></i><span>Orders, Shipping &amp; Tax</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-payment-btn" data-bs-toggle="pill" data-bs-target="#pane-payment" type="button" role="tab">
                <i class="bi bi-credit-card"></i><span>Payments &amp; Razorpay</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-shipmojo-btn" data-bs-toggle="pill" data-bs-target="#pane-shipmojo" type="button" role="tab">
                <i class="bi bi-truck"></i><span>ShipMojo Logistics</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-whatsapp-btn" data-bs-toggle="pill" data-bs-target="#pane-whatsapp" type="button" role="tab">
                <i class="bi bi-whatsapp"></i><span>WhatsApp Meta API</span>
            </button>
        </li>
        @if ($otherGroups->isNotEmpty())
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-other-btn" data-bs-toggle="pill" data-bs-target="#pane-other" type="button" role="tab">
                    <i class="bi bi-gear"></i><span>Other</span>
                </button>
            </li>
        @endif
    </ul>

    {{-- Tab Contents --}}
    <div class="tab-content" id="settingsTabContent">
        
        {{-- Pane 1: Store Profile & SEO --}}
        <div class="tab-pane fade show active" id="pane-store" role="tabpanel">
            <div class="card table-card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-shop text-muted"></i>
                    <span>Store Identity & Business Contact</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @if (isset($settingsGrouped['general']))
                            @foreach ($settingsGrouped['general'] as $setting)
                                <div class="{{ in_array($setting->type, ['textarea']) ? 'col-12' : 'col-12 col-md-6 col-lg-4' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            @if (isset($settingsGrouped['seo']))
            <div class="card table-card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-search text-muted"></i>
                    <span>Search Engine Optimization & Meta CAPI Pixel</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @foreach ($settingsGrouped['seo'] as $setting)
                            <div class="col-12 col-md-6">
                                @php $renderSettingField($setting); @endphp
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Pane 2: Orders, Shipping, Taxes & Returns (Consolidates 4 micro-groups into 1 cohesive hub) --}}
        <div class="tab-pane fade" id="pane-checkout" role="tabpanel">
            <div class="card table-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-sliders2 text-muted"></i>
                        <span>Checkout Rules, Delivery Charges, GST & Returns</span>
                    </div>
                    <span class="badge bg-light text-muted border">Unified Checkout Policies</span>
                </div>
                <div class="card-body p-3">
                    {{-- 1. Order Policies --}}
                    @if (isset($settingsGrouped['orders']))
                        <div class="settings-section-header">
                            <span class="avatar-circle" style="width:26px; height:26px; font-size:0.75rem; background: var(--vanriti-green-lighter); color: var(--vanriti-green);">
                                <i class="bi bi-cart"></i>
                            </span>
                            <h6>Order Limits & Confirmation Rules</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            @foreach ($settingsGrouped['orders'] as $setting)
                                <div class="col-12 col-md-6">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 2. Shipping & Delivery Charges --}}
                    @if (isset($settingsGrouped['shipping']))
                        <div class="settings-section-divider"></div>
                        <div class="settings-section-header">
                            <span class="avatar-circle" style="width:26px; height:26px; font-size:0.75rem; background:#EEF4FF; color:#3538CD;">
                                <i class="bi bi-truck"></i>
                            </span>
                            <h6>Shipping Fees & Delivery Pincode Verification</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            @foreach ($settingsGrouped['shipping'] as $setting)
                                <div class="{{ $setting->type === 'boolean' ? 'col-12 col-md-6' : 'col-12 col-md-4' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 3. Taxes & GST Configuration --}}
                    @if (isset($settingsGrouped['tax']))
                        <div class="settings-section-divider"></div>
                        <div class="settings-section-header">
                            <span class="avatar-circle" style="width:26px; height:26px; font-size:0.75rem; background:#ECFDF5; color:#047857;">
                                <i class="bi bi-receipt"></i>
                            </span>
                            <h6>Goods & Services Tax (GST)</h6>
                        </div>
                        <div class="row g-3 mb-4">
                            @foreach ($settingsGrouped['tax'] as $setting)
                                <div class="col-12 col-md-4">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 4. Returns & Replacement Policy --}}
                    @if (isset($settingsGrouped['returns']))
                        <div class="settings-section-divider"></div>
                        <div class="settings-section-header">
                            <span class="avatar-circle" style="width:26px; height:26px; font-size:0.75rem; background:#FFFBEB; color:#B45309;">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </span>
                            <h6>Return Window & Customer Policy</h6>
                        </div>
                        <div class="row g-3">
                            @foreach ($settingsGrouped['returns'] as $setting)
                                <div class="{{ $setting->type === 'textarea' ? 'col-12' : 'col-12 col-md-6' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pane 3: Payments & Razorpay --}}
        <div class="tab-pane fade" id="pane-payment" role="tabpanel">
            @if (isset($settingsGrouped['payment']))
                <div class="card table-card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-credit-card text-muted"></i>
                        <span>Payment Gateways & Razorpay API</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            @foreach ($settingsGrouped['payment'] as $setting)
                                <div class="{{ $setting->type === 'boolean' ? 'col-12 col-md-4' : 'col-12 col-md-6' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pane 4: ShipMojo Logistics --}}
        <div class="tab-pane fade" id="pane-shipmojo" role="tabpanel">
            @if (isset($settingsGrouped['shipmojo']))
                <div class="card table-card">
                    <div class="card-header d-flex justify-content-between align-items-center py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-truck text-muted"></i>
                            <span>ShipMojo Multi-Courier Dispatch Integration</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-success btn-sm py-0.5 px-2" onclick="testShipMojo(this)">
                            <i class="bi bi-wifi me-1"></i>Test Connection
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            @foreach ($settingsGrouped['shipmojo'] as $setting)
                                <div class="{{ $setting->type === 'boolean' ? 'col-12 col-md-6' : 'col-12 col-md-4' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach

                            <div class="col-12 mt-3">
                                <div class="alert alert-light border small mb-0 p-3 rounded-3">
                                    <div class="fw-semibold mb-1 text-dark"><i class="bi bi-broadcast me-1 text-primary"></i>ShipMojo Webhook Callback</div>
                                    <span class="text-muted" style="font-size:0.75rem;">Set this URL in your ShipMojo dashboard as the webhook callback:</span>
                                    <div class="d-flex align-items-center gap-2 mt-1.5">
                                        <code class="bg-white px-2 py-1 rounded border w-100 font-monospace small" id="shipmojo-webhook-url">{{ url('/shipmojo/webhook') }}</code>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('shipmojo-webhook-url').textContent)" title="Copy Webhook URL"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                    <small class="text-muted d-block mt-1.5" style="font-size:0.72rem; line-height:1.3;">
                                        ShipMojo must send the <code>public-key</code> header, an HMAC-SHA256 signature in <code>x-shipmojo-signature</code>, and the order reference ID. Statuses will update automatically.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pane 5: WhatsApp Meta API --}}
        <div class="tab-pane fade" id="pane-whatsapp" role="tabpanel">
            @if (isset($settingsGrouped['whatsapp']))
                <div class="card table-card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-whatsapp text-success"></i>
                        <span>WhatsApp Cloud API & Commerce Automated Templates</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            @foreach ($settingsGrouped['whatsapp'] as $setting)
                                <div class="{{ $setting->type === 'boolean' ? 'col-12 col-md-6' : 'col-12 col-md-4' }}">
                                    @php $renderSettingField($setting); @endphp
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Pane 6: Other Dynamic Groups (if any) --}}
        @if ($otherGroups->isNotEmpty())
            <div class="tab-pane fade" id="pane-other" role="tabpanel">
                @foreach ($otherGroups as $grp)
                    <div class="card table-card mb-3">
                        <div class="card-header fw-bold">{{ ucfirst($grp) }} Settings</div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                @foreach ($settingsGrouped[$grp] as $setting)
                                    <div class="col-12 col-md-6">
                                        @php $renderSettingField($setting); @endphp
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- Bottom Save Bar --}}
    <div class="card p-3 border-0 bg-transparent text-end mt-2">
        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-4">
            <i class="bi bi-check2"></i><span>Save All Settings</span>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
function testShipMojo(btn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing…';

    fetch('{{ route('admin.shipmojo.ping') }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.result === '1') {
            btn.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Connected!';
            btn.classList.replace('btn-outline-success', 'btn-success');
        } else {
            btn.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>Failed: ' + (data.message || 'Error');
            btn.classList.replace('btn-outline-success', 'btn-outline-danger');
        }
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.disabled = false;
            btn.className = btn.className.replace('btn-outline-danger','btn-outline-success').replace('btn-success','btn-outline-success');
        }, 3000);
    })
    .catch(() => {
        btn.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>Network Error';
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 3000);
    });
}
</script>
@endpush