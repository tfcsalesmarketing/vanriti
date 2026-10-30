@extends('tfc.member.layouts.portal')

@section('title', 'KYC Verification')

@section('content')
<div class="container-fluid px-0">

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">

            {{-- Status Card --}}
            <div class="member-card p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h4 class="fw-bold mb-0 text-dark brand-font">Identity Verification (KYC)</h4>
                    @if ($kyc && $kyc->status === 'verified')
                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-3 py-1.5 fs-6">
                            <i class="bi bi-patch-check-fill me-1"></i> Verified
                        </span>
                    @elseif ($kyc && $kyc->status === 'pending')
                        <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50 px-3 py-1.5 fs-6">
                            <i class="bi bi-hourglass-split me-1"></i> Pending Verification
                        </span>
                    @elseif ($kyc && $kyc->status === 'rejected')
                        <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 px-3 py-1.5 fs-6">
                            <i class="bi bi-x-circle-fill me-1"></i> Rejected
                        </span>
                    @else
                        <span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-3 py-1.5 fs-6">
                            <i class="bi bi-shield-slash me-1"></i> Optional / Not Submitted
                        </span>
                    @endif
                </div>

                @if ($kyc && $kyc->status === 'verified')
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-shield-fill-check fs-4"></i>
                        <div>
                            <strong>Congratulations!</strong> Your KYC documents were reviewed and verified on {{ $kyc->verified_at ? $kyc->verified_at->format('d M Y') : 'N/A' }}.
                            <div class="small mt-0.5">Document: {{ ucfirst($kyc->document_type) }} ({{ $kyc->masked_document_number }})</div>
                        </div>
                    </div>
                @elseif ($kyc && $kyc->status === 'pending')
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">
                        <i class="bi bi-clock-history fs-4"></i>
                        <div>
                            Your KYC submission is currently under review by our compliance team. Review typically completes within 24–48 working hours.
                            <div class="small mt-0.5">Submitted: {{ $kyc->submitted_at ? $kyc->submitted_at->format('d M Y, h:i A') : 'N/A' }} | {{ ucfirst($kyc->document_type) }} ({{ $kyc->masked_document_number }})</div>
                        </div>
                    </div>
                @elseif ($kyc && $kyc->status === 'rejected')
                    <div class="alert alert-danger mb-0" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <strong>Submission Rejected</strong>
                        </div>
                        <p class="small mb-0">Reason: {{ $kyc->rejection_reason ?? 'Document unreadable or invalid.' }}. Please review the guidelines below and re-submit clear documents.</p>
                    </div>
                @else
                    <p class="text-muted small mb-0">
                        KYC is <strong>optional</strong> at initial enrollment. You can submit your government-issued identity documents now or anytime from this portal. In accordance with UIDAI requirements, please upload masked Aadhaar or valid identity proof.
                    </p>
                @endif
            </div>

            {{-- Submission Form (Shown if not verified or rejected) --}}
            @if (! $kyc || $kyc->status !== 'verified')
                <div class="member-card p-4 p-md-5">
                    <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                        <i class="bi bi-file-earmark-person-fill text-primary me-1"></i> Submit KYC Documents
                    </h5>

                    <form method="POST" action="{{ route('tfc.preview.member.kyc.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Document Type --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Identity Document Type <span class="text-danger">*</span></label>
                            <select name="document_type" id="docTypeSelect" class="form-select @error('document_type') is-invalid @enderror" required>
                                <option value="aadhaar" {{ old('document_type') === 'aadhaar' ? 'selected' : '' }}>Aadhaar Card (UIDAI Masked)</option>
                                <option value="pan" {{ old('document_type') === 'pan' ? 'selected' : '' }}>PAN Card</option>
                                <option value="voter_id" {{ old('document_type') === 'voter_id' ? 'selected' : '' }}>Voter ID Card</option>
                                <option value="passport" {{ old('document_type') === 'passport' ? 'selected' : '' }}>Indian Passport</option>
                            </select>
                            <div class="form-text small text-muted" id="aadhaarHint">
                                <i class="bi bi-info-circle text-primary"></i> <strong>UIDAI Notice:</strong> Please upload a Masked Aadhaar displaying only the last 4 digits with the first 8 digits masked/blacked out.
                            </div>
                        </div>

                        {{-- Document Number --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Document Number <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="document_number" 
                                   class="form-control @error('document_number') is-invalid @enderror" 
                                   placeholder="Enter document number" 
                                   value="{{ old('document_number') }}" 
                                   required>
                            @error('document_number')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Document Files (Front & Back) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Document Front Page <span class="text-danger">*</span></label>
                                <input type="file" 
                                       name="document_front" 
                                       class="form-control @error('document_front') is-invalid @enderror" 
                                       accept=".jpg,.jpeg,.png,.pdf" 
                                       required>
                                <div class="form-text" style="font-size: 0.72rem;">JPG, PNG, or PDF (Max 4MB)</div>
                                @error('document_front')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Document Back Page (Optional)</label>
                                <input type="file" 
                                       name="document_back" 
                                       class="form-control @error('document_back') is-invalid @enderror" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <div class="form-text" style="font-size: 0.72rem;">JPG, PNG, or PDF (Max 4MB)</div>
                                @error('document_back')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Optional PAN Details --}}
                        <div class="p-3 mb-4 rounded-3 bg-light border">
                            <h6 class="fw-bold small text-dark mb-2">PAN Card Details (Optional for Direct Sellers)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">PAN Number</label>
                                    <input type="text" 
                                           name="pan_number" 
                                           class="form-control @error('pan_number') is-invalid @enderror text-uppercase" 
                                           placeholder="e.g. ABCDE1234F" 
                                           maxlength="10" 
                                           value="{{ old('pan_number') }}">
                                    @error('pan_number')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">PAN Card Copy</label>
                                    <input type="file" 
                                           name="pan_document" 
                                           class="form-control @error('pan_document') is-invalid @enderror" 
                                           accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                            </div>
                        </div>

                        {{-- Data Protection & UIDAI Compliance Notice --}}
                        <div class="d-flex align-items-start gap-2 mb-4 p-3 rounded-3" style="background: rgba(10, 29, 55, 0.04); border: 1px solid rgba(10, 29, 55, 0.1);">
                            <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
                            <div class="small text-muted">
                                <strong>Data Security &amp; Privacy:</strong> Your documents are encrypted and stored in private, non-public storage. They are accessible strictly by authorized compliance officers for verification. Bank details will only be requested when an approved payout process is active.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-tfc-primary w-100 py-3 fw-bold">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Submit KYC for Verification
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
