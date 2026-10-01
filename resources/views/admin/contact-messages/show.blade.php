@extends('admin.layouts.app')

@section('title', 'View Inquiry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Contact Inquiry</h5>
    <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Message Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Name</label>
                    <div>{{ $message->name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Email</label>
                    <div><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Mobile</label>
                    <div>{{ $message->mobile ?: '—' }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Subject</label>
                    <div>{{ $message->subject ?: '—' }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Message</label>
                    <div class="p-3 bg-light rounded">{{ $message->message }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Status</label>
                    <div><span class="badge {{ $message->status === 'new' ? 'bg-warning' : 'bg-success' }}">{{ ucfirst($message->status) }}</span></div>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold text-muted">Received</label>
                    <div>{{ $message->created_at->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <a href="mailto:{{ $message->email }}?subject=Re: {{ $message->subject }}" class="btn btn-sm btn-primary"><i class="bi bi-reply me-1"></i>Reply by Email</a>
    <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" data-confirm="Delete this inquiry?">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete Inquiry</button>
    </form>
</div>
@endsection
