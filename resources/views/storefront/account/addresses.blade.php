@extends('storefront.layouts.app')

@section('title', 'My Addresses')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row g-4">
        <div class="col-lg-3">
            @include('storefront.account.partials.nav')
        </div>
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">My Addresses</h4>
                    <p class="text-muted small mb-0">Manage saved shipping locations</p>
                </div>
                <button type="button" class="btn vr-app-btn btn-sm py-2 px-3" onclick="openEditModal()">
                    <i class="ri-add-line me-1"></i> Add New Address
                </button>
            </div>

            @if ($addresses->isEmpty())
                <div class="vr-guest-card text-center py-5">
                    <div class="vr-guest-icon-box mb-3" style="width:72px;height:72px;font-size:2.5rem;">
                        <i class="ri-map-pin-line"></i>
                    </div>
                    <h5 class="fw-bold mb-2">No Saved Addresses</h5>
                    <p class="text-muted small mb-4">Add your shipping address for faster checkout.</p>
                    <button type="button" class="btn vr-app-btn px-4 py-2" onclick="openEditModal()">
                        <i class="ri-add-line me-1"></i> Add Your First Address
                    </button>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($addresses as $address)
                        <div class="col-md-6">
                            <div class="vr-selection-card h-100 d-flex flex-column" style="cursor:default;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($address->type === 'home')
                                            <i class="ri-home-4-line text-success fs-5"></i>
                                        @elseif ($address->type === 'office')
                                            <i class="ri-building-line text-success fs-5"></i>
                                        @else
                                            <i class="ri-map-pin-line text-success fs-5"></i>
                                        @endif
                                        <span class="fw-bold text-dark">{{ $address->full_name }}</span>
                                        <span class="vr-type-chip">{{ $address->type }}</span>
                                    </div>
                                    @if ($address->is_default)
                                        <span class="vr-badge-pill">Default</span>
                                    @endif
                                </div>
                                <p class="small text-muted mb-3 flex-grow-1">
                                    {{ $address->address_line1 }}{{ $address->address_line2 ? ', '.$address->address_line2 : '' }}<br>
                                    @if ($address->landmark)<span class="text-secondary">Near {{ $address->landmark }}</span><br>@endif
                                    {{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}<br>
                                    <span class="fw-semibold text-dark"><i class="ri-phone-line me-1"></i> {{ $address->mobile }}</span>
                                </p>
                                <div class="d-flex align-items-center gap-2 pt-2 border-top mt-auto flex-wrap">
                                    <button type="button" class="btn vr-app-outline-btn btn-sm py-1 px-3"
                                            onclick='openEditModal(@json($address))'>
                                        <i class="ri-edit-line me-1"></i> Edit
                                    </button>
                                    @if (! $address->is_default)
                                        <form method="POST" action="{{ route('account.addresses.default', $address) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Set Default</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}"
                                          onsubmit="return confirm('Remove this address?');" class="d-inline ms-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete address">
                                            <i class="ri-delete-bin-line fs-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit / Add Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px;overflow:hidden;">
            <div class="modal-header vr-drawer-header">
                <h6 class="modal-title fw-bold mb-0" id="addressModalTitle">Add New Address</h6>
                <button type="button" class="vr-drawer-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="addressForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" id="addressFormMethod" value="POST">
                    <input type="hidden" name="address_id" id="addressFormId" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="full_name" id="af_full_name" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mobile Number</label>
                            <input type="text" name="mobile" id="af_mobile" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 1</label>
                            <input type="text" name="address_line1" id="af_address_line1" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 2 <span class="text-muted">(optional)</span></label>
                            <input type="text" name="address_line2" id="af_address_line2" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Landmark <span class="text-muted">(optional)</span></label>
                            <input type="text" name="landmark" id="af_landmark" class="form-control">
                        </div>
                        {{-- Pincode first: city & state auto-fill --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold d-flex align-items-center gap-2">
                                Pincode
                                <span class="vr-pincode-tip"><i class="ri-map-pin-2-line"></i> Auto-fills city &amp; state</span>
                            </label>
                            <input type="text" name="pincode" id="af_pincode" class="form-control"
                                   maxlength="6" inputmode="numeric" placeholder="6-digit pincode">
                            <div class="vr-pincode-status" id="af_pincode_status"></div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" id="af_city" class="form-control" placeholder="Auto-filled from pincode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text" name="state" id="af_state" class="form-control" placeholder="Auto-filled from pincode">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Type</label>
                            <select name="type" id="af_type" class="form-select">
                                <option value="home">Home</option>
                                <option value="office">Office</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_default" value="1" id="af_is_default" class="form-check-input">
                                <label for="af_is_default" class="form-check-label small fw-semibold">Set as default address</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn vr-app-outline-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn vr-app-btn" id="addressSaveBtn" onclick="submitAddressForm()">
                    <span class="btn-text">Save Address</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var addressModal = null;

// Init pincode lookup for modal
(function() {
    var pincodeEl = document.getElementById('af_pincode');
    var cityEl    = document.getElementById('af_city');
    var stateEl   = document.getElementById('af_state');
    var statusEl  = document.getElementById('af_pincode_status');
    if (pincodeEl && cityEl && stateEl && typeof window.vrPincodeLookup === 'function') {
        window.vrPincodeLookup(pincodeEl, cityEl, stateEl, statusEl);
    }
})();

function openEditModal(address) {
    var form = document.getElementById('addressForm');
    form.reset();
    form.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
    var statusEl = document.getElementById('af_pincode_status');
    if (statusEl) statusEl.textContent = '';

    document.getElementById('addressModalTitle').textContent = address ? 'Edit Address' : 'Add New Address';
    document.getElementById('addressFormMethod').value = address ? 'PUT' : 'POST';
    document.getElementById('addressFormId').value = address ? address.id : '';

    if (address) {
        document.getElementById('af_full_name').value = address.full_name || '';
        document.getElementById('af_mobile').value = address.mobile || '';
        document.getElementById('af_address_line1').value = address.address_line1 || '';
        document.getElementById('af_address_line2').value = address.address_line2 || '';
        document.getElementById('af_landmark').value = address.landmark || '';
        // Set city & state BEFORE pincode so they show existing data
        document.getElementById('af_city').value = address.city || '';
        document.getElementById('af_state').value = address.state || '';
        document.getElementById('af_pincode').value = address.pincode || '';
        document.getElementById('af_type').value = address.type || 'home';
        document.getElementById('af_is_default').checked = !!address.is_default;
    }

    if (!addressModal) {
        addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
    }
    addressModal.show();
}

function submitAddressForm() {
    var form = document.getElementById('addressForm');
    var btn = document.getElementById('addressSaveBtn');
    var id = document.getElementById('addressFormId').value;
    var isEdit = !!id;
    var url = isEdit ? '/account/addresses/' + id : '/account/addresses';

    var fd = new FormData(form);
    if (isEdit) fd.set('_method', 'PUT');

    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Saving...';
    btn.querySelector('.spinner-border').classList.remove('d-none');

    fetch(url, {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.errors) {
            Object.keys(data.errors).forEach(function(field) {
                var input = form.querySelector('[name="' + field + '"]');
                if (input) input.classList.add('is-invalid');
            });
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = 'Save Address';
            btn.querySelector('.spinner-border').classList.add('d-none');
            vrToast(Object.values(data.errors).flat(), 'error');
            return;
        }
        if (data.success) {
            addressModal.hide();
            window.location.reload();
        }
    }).catch(function() {
        btn.disabled = false;
        btn.querySelector('.btn-text').textContent = 'Save Address';
        btn.querySelector('.spinner-border').classList.add('d-none');
        vrToast('Something went wrong. Please try again.', 'error');
    });
}
</script>
@endpush
