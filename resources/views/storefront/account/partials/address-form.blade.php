<form method="POST" action="{{ $route }}" novalidate class="vr-guest-card p-4">
    @csrf
    @if (isset($method) && $method === 'PUT')
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Full Name</label>
            <input type="text" name="full_name" value="{{ old('full_name', $address->full_name ?? '') }}" class="form-control">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Mobile Number</label>
            <input type="text" name="mobile" value="{{ old('mobile', $address->mobile ?? '') }}" class="form-control">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Address Line 1</label>
            <input type="text" name="address_line1" value="{{ old('address_line1', $address->address_line1 ?? '') }}" class="form-control" placeholder="House/Flat No., Building, Street">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Address Line 2 <span class="text-muted">(optional)</span></label>
            <input type="text" name="address_line2" value="{{ old('address_line2', $address->address_line2 ?? '') }}" class="form-control" placeholder="Apartment, suite, landmark area">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Landmark <span class="text-muted">(optional)</span></label>
            <input type="text" name="landmark" value="{{ old('landmark', $address->landmark ?? '') }}" class="form-control">
        </div>

        {{-- Pincode first — city & state auto-fill --}}
        <div class="col-md-6">
            <label class="form-label small fw-semibold d-flex align-items-center gap-2">
                Pincode
                <span class="vr-pincode-tip"><i class="ri-map-pin-2-line"></i> City & State will auto-fill</span>
            </label>
            <input type="text" name="pincode" id="af_pincode_form"
                   value="{{ old('pincode', $address->pincode ?? '') }}"
                   class="form-control" maxlength="6" inputmode="numeric" pattern="\d{6}"
                   placeholder="6-digit pincode" data-pincode-input>
            <div class="vr-pincode-status"></div>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">City</label>
            <input type="text" name="city" value="{{ old('city', $address->city ?? '') }}" class="form-control" placeholder="Auto-filled from pincode">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">State</label>
            <input type="text" name="state" value="{{ old('state', $address->state ?? '') }}" class="form-control" placeholder="Auto-filled from pincode">
            <div class="invalid-feedback"></div>
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold">Address Type</label>
            <select name="type" class="form-select">
                @foreach (['home' => 'Home (All day delivery)', 'office' => 'Office (Delivery between 9am-6pm)', 'other' => 'Other'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('type', $address->type ?? 'home') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <div class="form-check mt-4">
                <input type="checkbox" name="is_default" value="1" id="is_default" class="form-check-input" @checked(old('is_default', $address->is_default ?? false))>
                <label for="is_default" class="form-check-label small fw-semibold">Set as default delivery address</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4 pt-3 border-top">
        <button type="submit" class="btn vr-app-btn px-4 py-2">
            <i class="ri-save-line me-1"></i> Save Address
        </button>
        <a href="{{ route('account.addresses') }}" class="btn vr-app-outline-btn px-4 py-2">Cancel</a>
    </div>
</form>