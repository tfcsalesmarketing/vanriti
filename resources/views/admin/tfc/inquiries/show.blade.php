@extends('admin.layouts.app')

@section('title', 'Inquiry Details: ' . $inquiry->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">Inquiry: {{ $inquiry->name }}</h5>
        <div class="text-muted small">Received on {{ $inquiry->created_at->format('d M Y, h:i A') }} (IP: {{ $inquiry->ip_address ?: '—' }})</div>
    </div>
    <a href="{{ route('admin.tfc.inquiries.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Inquiries
    </a>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Applicant &amp; Message Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Full Name</label>
                        <div class="fw-bold fs-6">{{ $inquiry->name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Inquiry Classification</label>
                        <div>
                            <span class="badge {{ $inquiry->type === 'seller_application' ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $inquiry->type === 'seller_application' ? 'Prospective Direct Seller Application' : 'General Corporate Inquiry' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Phone Number</label>
                        <div>
                            <a href="tel:{{ $inquiry->phone }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-telephone me-1"></i>{{ $inquiry->phone }}
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Email Address</label>
                        <div>
                            <a href="mailto:{{ $inquiry->email }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-envelope me-1"></i>{{ $inquiry->email }}
                            </a>
                        </div>
                    </div>
                    @if ($inquiry->city || $inquiry->state)
                        <div class="col-12">
                            <label class="text-muted small">Location</label>
                            <div class="fw-semibold">{{ $inquiry->city ?: '—' }}, {{ $inquiry->state ?: '—' }}</div>
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="text-muted small">Subject</label>
                        <div class="fw-semibold">{{ $inquiry->subject ?: 'General' }}</div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Message / Application Details</label>
                        <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap;">{{ $inquiry->message }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Administrative Processing</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tfc.inquiries.update', $inquiry) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Processing Status</label>
                        <select name="status" class="form-select" required>
                            <option value="new" {{ $inquiry->status === 'new' ? 'selected' : '' }}>New (Unreviewed)</option>
                            <option value="in_progress" {{ $inquiry->status === 'in_progress' ? 'selected' : '' }}>In Progress (Contacted)</option>
                            <option value="resolved" {{ $inquiry->status === 'resolved' ? 'selected' : '' }}>Resolved / Onboarded</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Internal Notes / CRM Log</label>
                        <textarea name="notes" class="form-control" rows="5" placeholder="Record interview notes, distributor reference code, or follow-up dates...">{{ old('notes', $inquiry->notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-save me-1"></i>Update Processing Status
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
