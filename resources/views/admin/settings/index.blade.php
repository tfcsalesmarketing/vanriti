@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Settings</h5>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    <div class="row g-3">
        @foreach ($settingsGrouped as $group => $settings)
            <div class="col-md-6">
                <div class="card h-100 d-flex flex-column mb-3">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span>{{ ucfirst($group) }}</span>
                        @if ($group === 'shipmojo')
                            <button type="button" class="btn btn-xs btn-outline-success btn-sm py-0 px-2"
                                onclick="testShipMojo(this)">
                                <i class="bi bi-wifi me-1"></i>Test Connection
                            </button>
                        @endif
                    </div>
                    <div class="card-body flex-grow-1">
                        <div class="row g-3">
                            @foreach ($settings as $setting)
                                <div class="col-12">
                                    @if ($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" name="{{ $setting->key }}" id="setting_{{ $setting->key }}" value="1"
                                                {{ setting($setting->key) === '1' || setting($setting->key) === true ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="setting_{{ $setting->key }}">{{ $setting->label }}</label>
                                        </div>
                                    @else
                                        <label class="form-label small fw-semibold">{{ $setting->label }}</label>
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
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
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