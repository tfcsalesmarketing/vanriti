@extends('admin.layouts.app')

@section('title', 'Review KYC Submission | ' . $member->tfc_member_id)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.tfc.kyc.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0">KYC Review: {{ $member->full_name }}</h5>
            <span class="font-monospace text-primary fw-bold">{{ $member->tfc_member_id }}</span>
        </div>
    </div>

    {{-- Approval Actions --}}
    @if ($kyc->status !== 'verified')
        <div class="d-flex gap-2">
            <form action="{{ route('admin.tfc.kyc.approve', $kyc->id) }}" method="POST" onsubmit="return confirm('Approve this KYC submission?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3">
                    <i class="bi bi-check-circle-fill me-1"></i> Approve KYC
                </button>
            </form>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectKycModal">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
        </div>
    @else
        <span class="badge bg-success py-2 px-3 fs-6">
            <i class="bi bi-patch-check-fill me-1"></i> Verified on {{ $kyc->verified_at ? $kyc->verified_at->format('d M Y') : '—' }}
        </span>
    @endif
</div>

<div class="row g-4">
    {{-- Member & Document Metadata --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Member &amp; Document Info</h6>
            </div>
            <div class="card-body p-4 small">
                <div class="row g-3">
                    <div class="col-12">
                        <span class="text-muted d-block">Full Legal Name:</span>
                        <strong class="text-dark fs-6">{{ $member->full_name }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">TFC Member ID:</span>
                        <span class="font-monospace text-primary fw-bold">{{ $member->tfc_member_id }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Mobile:</span>
                        <strong>{{ $member->mobile }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Document Type:</span>
                        <span class="badge bg-light text-dark border text-uppercase">{{ $kyc->document_type }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Masked Number:</span>
                        <span class="font-monospace fw-bold">{{ $kyc->masked_document_number }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted d-block">Submitted At:</span>
                        <strong>{{ $kyc->submitted_at ? $kyc->submitted_at->format('d M Y, h:i A') : '—' }}</strong>
                    </div>
                    @if ($kyc->pan_number)
                        <div class="col-12 border-top pt-2">
                            <span class="text-muted d-block">PAN Number:</span>
                            <span class="font-monospace fw-bold text-dark">{{ $kyc->pan_number }}</span>
                        </div>
                    @endif
                    @if ($kyc->rejection_reason)
                        <div class="col-12 border-top pt-2 text-danger">
                            <span class="d-block fw-semibold">Last Rejection Reason:</span>
                            <div>{{ $kyc->rejection_reason }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body p-3 small text-muted">
                <i class="bi bi-shield-lock-fill text-primary"></i> <strong>Confidential Document Streaming:</strong> Files are stored in private disk storage and streamed dynamically with authentication checks. Bank details are not collected at this stage.
            </div>
        </div>
    </div>

    {{-- Document Previews --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Document Front Page</h6>
                @if ($kyc->document_front_path)
                    <a href="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'front']) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.72rem;">
                        <i class="bi bi-box-arrow-up-right"></i> Open Full File
                    </a>
                @endif
            </div>
            <div class="card-body p-3 text-center">
                @if ($kyc->document_front_path)
                    <iframe src="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'front']) }}" style="width: 100%; height: 350px; border: 1px solid #E2E8F0; border-radius: 8px;"></iframe>
                @else
                    <span class="text-muted small">No front page uploaded</span>
                @endif
            </div>
        </div>

        @if ($kyc->document_back_path)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Document Back Page</h6>
                    <a href="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'back']) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.72rem;">
                        <i class="bi bi-box-arrow-up-right"></i> Open Full File
                    </a>
                </div>
                <div class="card-body p-3 text-center">
                    <iframe src="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'back']) }}" style="width: 100%; height: 350px; border: 1px solid #E2E8F0; border-radius: 8px;"></iframe>
                </div>
            </div>
        @endif

        @if ($kyc->pan_document_path)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">PAN Card Document</h6>
                    <a href="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'pan']) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.72rem;">
                        <i class="bi bi-box-arrow-up-right"></i> Open Full File
                    </a>
                </div>
                <div class="card-body p-3 text-center">
                    <iframe src="{{ route('tfc.preview.member.kyc.document', ['id' => $kyc->id, 'type' => 'pan']) }}" style="width: 100%; height: 350px; border: 1px solid #E2E8F0; border-radius: 8px;"></iframe>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectKycModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.tfc.kyc.reject', $kyc->id) }}" method="POST">
            @csrf
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold text-dark">Reject KYC Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label fw-semibold small">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Document image is blurry or first 8 digits of Aadhaar are not masked." required></textarea>
                    <div class="form-text small text-muted">This explanation will be displayed to the member so they can re-upload proper documents.</div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Confirm Rejection</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
