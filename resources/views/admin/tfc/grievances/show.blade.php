@extends('admin.layouts.app')

@section('title', 'Process Grievance: ' . $ticket->ticket_number)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">Grievance Ticket: <span class="text-primary">{{ $ticket->ticket_number }}</span></h5>
        <div class="text-muted small">
            Acknowledged on: {{ $ticket->acknowledged_at ? $ticket->acknowledged_at->format('d M Y, h:i A') : $ticket->created_at->format('d M Y, h:i A') }}
            @if ($ticket->resolved_at)
                | Resolved on: <span class="text-success fw-semibold">{{ $ticket->resolved_at->format('d M Y, h:i A') }}</span>
            @endif
        </div>
    </div>
    <a href="{{ route('admin.tfc.grievances.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Tickets
    </a>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Consumer &amp; Complaint Particulars</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Consumer Name</label>
                        <div class="fw-bold fs-6">{{ $ticket->consumer_name }}</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Complaint Category</label>
                        <div>
                            <span class="badge bg-light text-dark border">
                                {{ ucwords(str_replace('_', ' ', $ticket->category)) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Phone Contact</label>
                        <div class="d-flex align-items-center gap-2">
                            <a href="tel:{{ $ticket->phone }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-telephone me-1"></i>{{ $ticket->phone }}
                            </a>
                            <a href="{{ route('admin.tfc.whatsapp.inbox', ['grievance_id' => $ticket->id]) }}" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.72rem;">
                                <i class="bi bi-whatsapp me-1"></i>WhatsApp
                            </a>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Email Address</label>
                        <div>
                            <a href="mailto:{{ $ticket->email }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-envelope me-1"></i>{{ $ticket->email }}
                            </a>
                        </div>
                    </div>
                    @if ($ticket->order_number)
                        <div class="col-12">
                            <label class="text-muted small">Associated Order Reference</label>
                            <div class="fw-semibold text-primary">#{{ $ticket->order_number }}</div>
                        </div>
                    @endif
                    <div class="col-12">
                        <label class="text-muted small">Grievance / Complaint Description</label>
                        <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap;">{{ $ticket->complaint_details }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Statutory Resolution Action (Rule 7)</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tfc.grievances.update', $ticket) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resolution Status</label>
                        <select name="status" class="form-select" required>
                            <option value="acknowledged" {{ $ticket->status === 'acknowledged' ? 'selected' : '' }}>Acknowledged (48h target)</option>
                            <option value="under_investigation" {{ $ticket->status === 'under_investigation' ? 'selected' : '' }}>Under Investigation</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved (30d target)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Official Redressal Notes / Customer Response</label>
                        <textarea name="resolution_notes" class="form-control" rows="6" placeholder="Document the actions taken, replacement/refund reference, or customer communication details..." required>{{ old('resolution_notes', $ticket->resolution_notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-shield-check me-1"></i>Update Resolution Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
