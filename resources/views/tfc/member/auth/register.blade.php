@extends('tfc.layouts.app')

@section('title', 'Become a TFC Direct Seller | Independent Member Registration')
@section('meta_description', 'Join TFC Sales & Marketing as an independent direct-selling member. Free registration under Direct Selling Rules 2021 with genuine botanical products.')

@section('content')
<div class="py-5 bg-light" style="min-height: 85vh;">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                
                {{-- Statutory Free Registration Notice Banner --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #0A1D37 0%, #1E3A8A 100%); color: #FFFFFF;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2.5 rounded-3 bg-warning text-dark fs-4">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold mb-1 brand-font text-white">Join TFC Sales &amp; Marketing</h4>
                                <p class="text-light mb-0 small opacity-90">
                                    Direct Selling Membership is <strong>100% Free</strong>. Under the Consumer Protection (Direct Selling) Rules, 2021, no entry fee, subscription charges, or mandatory starter product purchases are required.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Registration Form Card --}}
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark">Member Registration</h5>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5">Free Enrollment</span>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        {{-- SERVER-SIDE VALIDATION ERROR SUMMARY BANNER --}}
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 mb-4 shadow-sm border-danger" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 mt-0.5 flex-shrink-0"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1 text-danger">Please correct the following errors:</h6>
                                        <ul class="mb-0 ps-3 small text-danger">
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
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 mb-4 shadow-sm border-danger" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                    <div class="fw-semibold text-danger">{{ session('error') }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ tfc_route('member.register.submit') }}" id="memberRegisterForm" novalidate>
                            @csrf

                            {{-- SECTION 1: SPONSOR & PLACEMENT --}}
                            <div class="mb-4">
                                <h6 class="fw-bold text-uppercase text-secondary small pb-2 border-bottom" style="letter-spacing: 0.8px;">
                                    <i class="bi bi-diagram-3-fill text-warning me-1"></i> 1. Sponsor &amp; Team Placement
                                </h6>

                                {{-- Sponsor ID (Live auto-verification as typed) --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Sponsor ID <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                                        <input type="text" 
                                               name="sponsor_tfc_id" 
                                               id="sponsor_tfc_id" 
                                               class="form-control @error('sponsor_tfc_id') is-invalid @enderror font-monospace text-uppercase" 
                                               placeholder="Type Sponsor ID (e.g. TFC10001)" 
                                               value="{{ old('sponsor_tfc_id', $prefilledSponsor ? $prefilledSponsor->tfc_member_id : '') }}" 
                                               autocomplete="off"
                                               required 
                                               {{ $prefilledSponsor ? 'readonly' : '' }}>
                                        <span class="input-group-text bg-white" id="sponsorStatusIcon">
                                            <i class="bi bi-search text-muted"></i>
                                        </span>
                                    </div>
                                    <div id="sponsorFeedback" class="mt-2">
                                        @if ($prefilledSponsor)
                                            <div class="alert alert-success py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-success">
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                <div>
                                                    <div class="fw-bold text-success text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Verified Sponsor</div>
                                                    <div class="fw-bold fs-6 text-dark">{{ $prefilledSponsor->full_name }}</div>
                                                    <div class="text-muted small font-monospace">Member ID: <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ $prefilledSponsor->tfc_member_id }}</span></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @error('sponsor_tfc_id')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Placement Parent ID (Dedicated row with live auto-verification) --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Placement Parent ID <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-diagram-2"></i></span>
                                        <input type="text" 
                                               name="placement_parent_tfc_id" 
                                               id="placement_parent_tfc_id" 
                                               class="form-control @error('placement_parent_tfc_id') is-invalid @enderror font-monospace text-uppercase" 
                                               placeholder="Enter Sponsor ID or direct upline ID" 
                                               value="{{ old('placement_parent_tfc_id', $prefilledSponsor ? $prefilledSponsor->tfc_member_id : '') }}" 
                                               autocomplete="off"
                                               required>
                                        <span class="input-group-text bg-white" id="placementStatusIcon">
                                            <i class="bi bi-search text-muted"></i>
                                        </span>
                                    </div>
                                    <div class="form-text" style="font-size: 0.75rem;">If placing directly under your sponsor, enter the same Sponsor ID.</div>
                                    <div id="placementFeedback" class="mt-2"></div>
                                    @error('placement_parent_tfc_id')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Placement Position / Group (Dedicated NEW ROW) --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small d-flex justify-content-between align-items-center">
                                        <span>Placement Group / Leg <span class="text-danger">*</span></span>
                                        <span class="badge bg-light text-secondary border">Binary Team Placement</span>
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="placement_position" id="posOrange" value="orange" {{ old('placement_position') === 'blue' ? '' : 'checked' }}>
                                            <label class="btn btn-outline-warning w-100 fw-bold text-dark text-center py-2.5 rounded-3 shadow-none border-2" for="posOrange">
                                                <span class="fs-6 me-1">🟠</span> Orange Group
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="placement_position" id="posBlue" value="blue" {{ old('placement_position') === 'blue' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-info w-100 fw-bold text-dark text-center py-2.5 rounded-3 shadow-none border-2" for="posBlue">
                                                <span class="fs-6 me-1">🔵</span> Blue Group
                                            </label>
                                        </div>
                                    </div>
                                    @error('placement_position')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                            {{-- SECTION 2: PERSONAL CREDENTIALS (MOBILE & NAME) --}}
                            <div class="mb-4">
                                <h6 class="fw-bold text-uppercase text-secondary small pb-2 border-bottom" style="letter-spacing: 0.8px;">
                                    <i class="bi bi-person-fill text-primary me-1"></i> 2. Member Information
                                </h6>

                                {{-- Mobile Number with Live Server-side Check --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Mobile Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">+91</span>
                                        <input type="tel" 
                                               name="mobile" 
                                               id="mobileInput"
                                               class="form-control @error('mobile') is-invalid @enderror" 
                                               placeholder="10-digit Indian number (starts with 6-9)" 
                                               maxlength="15" 
                                               value="{{ old('mobile') }}" 
                                               required>
                                        <span class="input-group-text bg-white" id="mobileStatusIcon">
                                            <i class="bi bi-phone text-muted"></i>
                                        </span>
                                    </div>
                                    <div id="mobileFeedback" class="mt-2"></div>
                                    @error('mobile')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Full Legal Name --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Full Legal Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="full_name" 
                                           id="fullNameInput" 
                                           class="form-control @error('full_name') is-invalid @enderror" 
                                           placeholder="As per Government ID (e.g. Aadhaar / PAN)" 
                                           value="{{ old('full_name') }}" 
                                           required>
                                    @error('full_name')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- SECTION 3: AUTOMATIC CREDENTIALS NOTICE VIA WHATSAPP --}}
                            <div class="card bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 p-3 mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                                        <i class="bi bi-whatsapp fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-success">Instant WhatsApp Credentials</h6>
                                        <p class="small text-muted mb-0">
                                            Your unique <strong>Member ID</strong> and auto-generated secure <strong>Password</strong> will be dispatched directly to your mobile number via WhatsApp immediately upon registration.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- STATUTORY DECLARATION --}}
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="termsCheck" value="1" {{ old('terms') ? 'checked' : '' }} required>
                                    <label class="form-check-label small text-muted" for="termsCheck">
                                        I hereby declare that I am at least 18 years of age and legally competent to contract. I agree to the 
                                        <a href="{{ tfc_route('policy', 'direct-selling-agreement') }}" target="_blank" class="text-primary fw-semibold">TFC Direct Seller Agreement</a> and 
                                        <a href="{{ tfc_route('policy', 'code-of-ethics') }}" target="_blank" class="text-primary fw-semibold">Code of Ethics</a>. KYC is optional at registration and can be uploaded later.
                                    </label>
                                    @error('terms')
                                        <div class="text-danger small mt-1 fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- SUBMIT BUTTON --}}
                            <button type="submit" id="btnSubmitRegister" class="btn btn-tfc-primary w-100 py-3 fw-bold fs-6 shadow-sm">
                                <i class="bi bi-person-check-fill me-1.5"></i> Register as Direct Seller
                            </button>
                        </form>
                    </div>

                    <div class="card-footer bg-light py-3 text-center border-top">
                        <span class="text-muted small">Already registered as a TFC Member?</span>
                        <a href="{{ tfc_route('member.login') }}" class="text-primary fw-bold small ms-1">Sign In to Member Portal</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    function initMemberRegistration() {
        const sponsorInput = document.getElementById('sponsor_tfc_id');
        const parentInput = document.getElementById('placement_parent_tfc_id');
        const sponsorFeedback = document.getElementById('sponsorFeedback');
        const placementFeedback = document.getElementById('placementFeedback');
        const sponsorStatusIcon = document.getElementById('sponsorStatusIcon');
        const placementStatusIcon = document.getElementById('placementStatusIcon');
        
        const mobileInput = document.getElementById('mobileInput');
        const mobileFeedback = document.getElementById('mobileFeedback');
        const mobileStatusIcon = document.getElementById('mobileStatusIcon');

        const fullNameInput = document.getElementById('fullNameInput');
        const btnSubmit = document.getElementById('btnSubmitRegister');

        if (!sponsorInput || !parentInput || !mobileInput) {
            console.warn('[TFC Member] Form inputs not ready yet.');
            return;
        }

        // Build bulletproof endpoint URLs matching current page path
        const basePath = window.location.origin + window.location.pathname.replace(/\/register\/?$/i, '');
        const urlVerifySponsor = basePath + '/ajax/verify-sponsor';
        const urlVerifyPlacement = basePath + '/ajax/verify-placement';
        const urlCheckMobile = basePath + '/ajax/check-mobile';

        let sponsorTimeout = null;
        let placementTimeout = null;
        let mobileTimeout = null;
        let isSponsorValid = false;
        let isPlacementValid = false;

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 1. Live Auto-Verify Sponsor as user types
        function runSponsorVerify() {
            const sponsorId = sponsorInput.value.trim().toUpperCase();
            sponsorInput.value = sponsorId;

            if (sponsorId.length === 0) {
                sponsorFeedback.innerHTML = '';
                sponsorStatusIcon.innerHTML = '<i class="bi bi-search text-muted"></i>';
                sponsorInput.classList.remove('is-valid', 'is-invalid');
                isSponsorValid = false;
                return;
            }

            if (sponsorId.length < 3) {
                sponsorStatusIcon.innerHTML = '<i class="bi bi-hourglass-split text-muted"></i>';
                sponsorFeedback.innerHTML = '<div class="text-muted small py-1"><i class="bi bi-info-circle"></i> Type at least 3 characters...</div>';
                isSponsorValid = false;
                return;
            }

            sponsorStatusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
            sponsorFeedback.innerHTML = '<div class="text-primary small py-1"><span class="spinner-border spinner-border-sm me-1"></span> Checking Sponsor ID...</div>';

            fetch(urlVerifySponsor + '?sponsor_id=' + encodeURIComponent(sponsorId), {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.valid && data.sponsor) {
                    isSponsorValid = true;
                    sponsorInput.classList.remove('is-invalid');
                    sponsorInput.classList.add('is-valid');
                    sponsorStatusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                    sponsorFeedback.innerHTML = `
                        <div class="alert alert-success py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-success">
                            <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-success text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Verified Sponsor</div>
                                <div class="fw-bold fs-6 text-dark">${escapeHtml(data.sponsor.name)}</div>
                                <div class="text-muted small font-monospace">Member ID: <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">${escapeHtml(data.sponsor.tfc_member_id)}</span></div>
                            </div>
                        </div>`;

                    // If placement parent is empty, auto-fill with verified sponsor ID and trigger placement check
                    if (parentInput && !parentInput.value.trim()) {
                        parentInput.value = data.sponsor.tfc_member_id;
                        runPlacementVerify();
                    } else if (parentInput && parentInput.value.trim().length >= 3) {
                        runPlacementVerify();
                    }
                } else {
                    isSponsorValid = false;
                    sponsorInput.classList.remove('is-valid');
                    sponsorInput.classList.add('is-invalid');
                    sponsorStatusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                    sponsorFeedback.innerHTML = `
                        <div class="alert alert-danger py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-danger">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-danger text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sponsor Error</div>
                                <div class="fw-semibold text-danger">${escapeHtml(data.message || 'Sponsor ID not found or inactive.')}</div>
                            </div>
                        </div>`;
                }
            })
            .catch(err => {
                sponsorInput.classList.remove('is-valid');
                sponsorInput.classList.add('is-invalid');
                sponsorStatusIcon.innerHTML = '<i class="bi bi-exclamation-circle text-danger fs-5"></i>';
                sponsorFeedback.innerHTML = `
                    <div class="alert alert-danger py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-danger">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                        <div>Could not connect to sponsor verification service.</div>
                    </div>`;
            });
        }

        function triggerSponsor() {
            clearTimeout(sponsorTimeout);
            sponsorTimeout = setTimeout(runSponsorVerify, 200);
        }

        // 2. Live Auto-Verify Placement Parent & Slot as user types
        function runPlacementVerify() {
            const sponsorId = sponsorInput ? sponsorInput.value.trim().toUpperCase() : '';
            const parentId = parentInput.value.trim().toUpperCase();
            parentInput.value = parentId;
            const position = document.querySelector('input[name="placement_position"]:checked')?.value || 'orange';

            if (parentId.length === 0) {
                placementFeedback.innerHTML = '';
                placementStatusIcon.innerHTML = '<i class="bi bi-search text-muted"></i>';
                parentInput.classList.remove('is-valid', 'is-invalid');
                isPlacementValid = false;
                return;
            }

            if (parentId.length < 3) {
                placementStatusIcon.innerHTML = '<i class="bi bi-hourglass-split text-muted"></i>';
                placementFeedback.innerHTML = '<div class="text-muted small py-1"><i class="bi bi-info-circle"></i> Type at least 3 characters...</div>';
                isPlacementValid = false;
                return;
            }

            placementStatusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
            placementFeedback.innerHTML = '<div class="text-primary small py-1"><span class="spinner-border spinner-border-sm me-1"></span> Checking Placement Parent &amp; Slot...</div>';

            const q = `?sponsor_id=${encodeURIComponent(sponsorId)}&placement_parent_id=${encodeURIComponent(parentId)}&position=${encodeURIComponent(position)}`;

            fetch(urlVerifyPlacement + q, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.valid && data.parent) {
                    isPlacementValid = true;
                    parentInput.classList.remove('is-invalid');
                    parentInput.classList.add('is-valid');
                    placementStatusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                    
                    const legBadge = data.parent.position === 'orange'
                        ? '<span class="badge bg-warning text-dark text-uppercase ms-1 px-2 py-1">🟠 Orange Group Available</span>'
                        : '<span class="badge bg-info text-dark text-uppercase ms-1 px-2 py-1">🔵 Blue Group Available</span>';

                    placementFeedback.innerHTML = `
                        <div class="alert alert-success py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-success">
                            <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-success text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Verified Placement Parent</div>
                                <div class="fw-bold fs-6 text-dark">${escapeHtml(data.parent.name)}</div>
                                <div class="text-muted small">
                                    <span class="font-monospace badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">${escapeHtml(data.parent.tfc_member_id)}</span>
                                    ${legBadge}
                                </div>
                            </div>
                        </div>`;
                } else {
                    isPlacementValid = false;
                    parentInput.classList.remove('is-valid');
                    parentInput.classList.add('is-invalid');
                    placementStatusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';

                    const parentInfo = data.parent 
                        ? `<div class="small mt-1 text-muted">Parent: <strong>${escapeHtml(data.parent.name)}</strong> (${escapeHtml(data.parent.tfc_member_id)})</div>` 
                        : '';

                    placementFeedback.innerHTML = `
                        <div class="alert alert-danger py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-danger">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                            <div>
                                <div class="fw-bold text-danger text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Placement Error</div>
                                <div class="fw-semibold text-danger">${escapeHtml(data.message || 'Invalid Placement Parent ID or slot is occupied.')}</div>
                                ${parentInfo}
                            </div>
                        </div>`;
                }
            })
            .catch(err => {
                parentInput.classList.remove('is-valid');
                parentInput.classList.add('is-invalid');
                placementStatusIcon.innerHTML = '<i class="bi bi-exclamation-circle text-danger fs-5"></i>';
                placementFeedback.innerHTML = `
                    <div class="alert alert-danger py-2.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 shadow-sm border-danger">
                        <i class="bi bi-exclamation-circle-fill text-danger fs-5 flex-shrink-0"></i>
                        <div>Could not connect to placement verification service.</div>
                    </div>`;
            });
        }

        function triggerPlacement() {
            clearTimeout(placementTimeout);
            placementTimeout = setTimeout(runPlacementVerify, 200);
        }

        // 3. Live Server-Side Mobile Verification
        function checkMobileServer() {
            let mobile = mobileInput.value.replace(/\D/g, '');
            if (mobile.length > 10) {
                if (mobile.startsWith('0091')) mobile = mobile.substring(4);
                else if (mobile.startsWith('91')) mobile = mobile.substring(2);
                else if (mobile.startsWith('0')) mobile = mobile.substring(1);
            }
            if (mobile.length > 10) {
                mobile = mobile.substring(0, 10);
            }
            mobileInput.value = mobile;

            if (mobile.length === 0) {
                mobileFeedback.innerHTML = '';
                mobileStatusIcon.innerHTML = '<i class="bi bi-phone text-muted"></i>';
                mobileInput.classList.remove('is-valid', 'is-invalid');
                btnSubmit.disabled = false;
                return;
            }

            if (mobile.length !== 10) {
                mobileInput.classList.remove('is-valid');
                mobileStatusIcon.innerHTML = '<i class="bi bi-exclamation-circle text-warning fs-5"></i>';
                mobileFeedback.innerHTML = '<span class="text-muted small">Enter 10 digits (' + (10 - mobile.length) + ' digits remaining)...</span>';
                btnSubmit.disabled = false;
                return;
            }

            mobileStatusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';

            fetch(urlCheckMobile + '?mobile=' + encodeURIComponent(mobile), {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.valid) {
                    mobileInput.classList.remove('is-valid');
                    mobileInput.classList.add('is-invalid');
                    mobileStatusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                    mobileFeedback.innerHTML = `
                        <div class="alert alert-danger py-2 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 border-danger mt-1">
                            <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0"></i>
                            <div class="fw-semibold text-danger">${escapeHtml(data.message)}</div>
                        </div>`;
                    btnSubmit.disabled = true;
                } else {
                    mobileInput.classList.remove('is-invalid');
                    mobileInput.classList.add('is-valid');
                    btnSubmit.disabled = false;
                    if (data.status === 'existing_client') {
                        mobileStatusIcon.innerHTML = '<i class="bi bi-person-check-fill text-primary fs-5"></i>';
                        mobileFeedback.innerHTML = `
                            <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 border-info mt-1">
                                <i class="bi bi-info-circle-fill text-primary flex-shrink-0"></i>
                                <div>
                                    <strong>Existing Customer Account Found:</strong> ${escapeHtml(data.name)}. Your account will be upgraded to Independent Direct Seller with your existing credentials.
                                </div>
                            </div>`;
                        if (data.name && !fullNameInput.value) fullNameInput.value = data.name;
                    } else {
                        mobileStatusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                        mobileFeedback.innerHTML = `
                            <div class="alert alert-success py-1.5 px-3 mb-0 small d-flex align-items-center gap-2 rounded-3 border-success mt-1">
                                <i class="bi bi-check-lg text-success"></i>
                                <div class="text-success">${escapeHtml(data.message)}</div>
                            </div>`;
                    }
                }
            })
            .catch(err => {
                mobileStatusIcon.innerHTML = '<i class="bi bi-phone text-muted"></i>';
            });
        }

        function triggerMobile() {
            clearTimeout(mobileTimeout);
            mobileTimeout = setTimeout(checkMobileServer, 200);
        }

        // Attach listeners directly to DOM elements
        ['input', 'change', 'paste', 'keyup', 'blur'].forEach(evt => {
            if (sponsorInput && !sponsorInput.readOnly) sponsorInput.addEventListener(evt, triggerSponsor);
            if (parentInput) parentInput.addEventListener(evt, triggerPlacement);
            if (mobileInput) mobileInput.addEventListener(evt, triggerMobile);
        });

        document.querySelectorAll('input[name="placement_position"]').forEach(r => {
            r.addEventListener('change', triggerPlacement);
        });

        // Run initial verification if fields have pre-filled values
        if (sponsorInput && sponsorInput.value.trim().length >= 3) {
            runSponsorVerify();
        }
        if (parentInput && parentInput.value.trim().length >= 3) {
            runPlacementVerify();
        }
        if (mobileInput && mobileInput.value.trim().length === 10) {
            checkMobileServer();
        }

        // Form Submit Validation:
        // Do NOT block empty form submissions! Let empty forms reach Laravel server-side validation.
        // Only block if a typed sponsor ID or placement ID is known invalid.
        document.getElementById('memberRegisterForm').addEventListener('submit', function (e) {
            if (sponsorInput && sponsorInput.value.trim().length > 0 && isSponsorValid === false) {
                e.preventDefault();
                sponsorInput.focus();
                runSponsorVerify();
                return false;
            }
            if (parentInput && parentInput.value.trim().length > 0 && isPlacementValid === false) {
                e.preventDefault();
                parentInput.focus();
                runPlacementVerify();
                return false;
            }
            if (mobileInput && mobileInput.classList.contains('is-invalid')) {
                e.preventDefault();
                mobileInput.focus();
                return false;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMemberRegistration);
    } else {
        initMemberRegistration();
    }
})();
</script>
@endpush
