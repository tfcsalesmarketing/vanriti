@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $groupIcons = [
        'general' => 'bi-sliders',
        'store' => 'bi-shop',
        'payment' => 'bi-credit-card',
        'shipping' => 'bi-truck',
        'shipmojo' => 'bi-box-seam',
        'whatsapp' => 'bi-whatsapp',
        'mail' => 'bi-envelope',
        'sms' => 'bi-chat-dots',
        'security' => 'bi-shield-check',
    ];
@endphp

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold">Store Configuration & Integrations</h5>
            <div class="text-muted small mt-0.5">Manage store preferences, Razorpay, WhatsApp Meta API, and ShipMojo logistics</div>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm px-3">
                <i class="bi bi-check2"></i><span>Save All Settings</span>
            </button>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($settingsGrouped as $group => $settings)
            <div class="col-12 col-lg-6">
                <div class="card table-card h-100 d-flex flex-column mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle" style="width:28px; height:28px; font-size:0.8rem; background: var(--vanriti-green-lighter); color: var(--vanriti-green);">
                                <i class="bi {{ $groupIcons[strtolower($group)] ?? 'bi-gear' }}"></i>
                            </span>
                            <span class="fw-bold">{{ ucfirst($group) }} Settings</span>
                        </div>
                        @if ($group === 'shipmojo')
                            <button type="button" class="btn btn-xs btn-outline-success btn-sm py-0.5 px-2"
                                onclick="testShipMojo(this)">
                                <i class="bi bi-wifi me-1"></i>Test Connection
                            </button>
                        @endif
                    </div>
                    <div class="card-body flex-grow-1 p-3">
                        <div class="row g-3">
                            @foreach ($settings as $setting)
                                <div class="col-12">
                                    @if ($setting->type === 'boolean')
                                        <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 m-0">
                                            <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="setting_{{ $setting->key }}">{{ $setting->label }}</label>
                                            <input type="checkbox" class="form-check-input ms-2" style="width: 2.2em; height: 1.2em;" name="{{ $setting->key }}" id="setting_{{ $setting->key }}" value="1"
                                                {{ setting($setting->key) === '1' || setting($setting->key) === true ? 'checked' : '' }}>
                                        </div>
                                    @else
                                        <label class="form-label small fw-semibold text-muted mb-1">{{ $setting->label }}</label>
                                        @if ($setting->type === 'textarea')
                                            <textarea name="{{ $setting->key }}" class="form-control form-control-sm" rows="3">{{ setting($setting->key) }}</textarea>
                                        @elseif ($setting->type === 'select')
                                            <select name="{{ $setting->key }}" class="form-select form-select-sm">
                                                <option value="inclusive" {{ setting($setting->key) === 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                                <option value="exclusive" {{ setting($setting->key) === 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                            </select>
                                        @elseif ($setting->type === 'password')
                                            <input type="password" name="{{ $setting->key }}" class="form-control form-control-sm" value="" autocomplete="new-password"
                                                placeholder="{{ blank(setting($setting->key)) ? '' : '•••••••• (leave blank to keep)' }}">
                                        @else
                                            <input type="{{ $setting->type === 'number' ? 'number' : ($setting->type === 'email' ? 'email' : ($setting->type === 'url' ? 'url' : 'text')) }}" name="{{ $setting->key }}" class="form-control form-control-sm" value="{{ setting($setting->key) }}" step="{{ $setting->type === 'number' ? 'any' : null }}">
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                            @if ($group === 'shipmojo')
                                <div class="col-12">
                                    <div class="alert alert-light border small mb-0 p-2.5 rounded-3">
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
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card p-3 border-0 bg-transparent text-end">
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
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false;
            btn.className = btn.className.replace('btn-outline-danger','btn-outline-success').replace('btn-success','btn-outline-success'); }, 3000);
    })
    .catch(() => {
        btn.innerHTML = '<i class="bi bi-x-circle-fill text-danger me-1"></i>Network Error';
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 3000);
    });
}
</script>
@endpush