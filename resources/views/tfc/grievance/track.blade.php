@extends('tfc.layouts.app')

@section('title', 'Track Grievance Ticket | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Real-Time Redressal</span>
        <h1 class="display-5 fw-bold text-white mb-2">Track Grievance Ticket</h1>
        <p class="text-white-50 lead mb-0">Monitor investigation progress and statutory redressal updates.</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 800px;">
        {{-- Flash message on successful ticket generation --}}
        @if (session('ticket_success'))
            <div class="alert alert-success border-success p-4 rounded-4 mb-4 shadow-sm text-center">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                <h4 class="fw-bold mb-2">Complaint Acknowledged Successfully!</h4>
                <div class="p-3 bg-white rounded-3 border d-inline-block my-2">
                    <span class="text-muted small d-block">Your Official Ticket Reference Number:</span>
                    <span class="display-6 fw-bold text-primary">{{ session('ticket_success')['ticket_number'] }}</span>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    {{ session('ticket_success')['message'] }}
                </p>
            </div>
        @endif

        {{-- Ticket Search Bar --}}
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-5">
            <h5 class="fw-bold text-dark mb-3 text-center">Enter Your Ticket Reference Number</h5>
            <form method="POST" action="{{ tfc_route('grievance.track.status') }}">
                @csrf
                <div class="input-group input-group-lg">
                    <input type="text" name="ticket_number" class="form-control" placeholder="e.g. TFC-GR-20261029-XXXX" value="{{ old('ticket_number', request('ticket_number')) }}" required>
                    <button class="btn btn-tfc-primary px-4 fw-bold" type="submit">
                        <i class="bi bi-search me-1"></i>Lookup Status
                    </button>
                </div>
                @error('ticket_number') <small class="text-danger mt-1 d-block">{{ $message }}</small> @enderror
            </form>
        </div>

        {{-- Lookup Result --}}
        @isset($ticket)
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between border-bottom">
                    <div>
                        <span class="text-muted small">Ticket Reference:</span>
                        <h5 class="fw-bold text-primary mb-0">{{ $ticket->ticket_number }}</h5>
                    </div>
                    <span class="badge {{ $ticket->status === 'resolved' ? 'bg-success' : ($ticket->status === 'under_investigation' ? 'bg-warning text-dark' : 'bg-info text-dark') }} fs-6 px-3 py-2">
                        Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="text-muted small">Consumer Name</label>
                            <div class="fw-bold">{{ $ticket->consumer_name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Category</label>
                            <div>{{ ucwords(str_replace('_', ' ', $ticket->category)) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Acknowledged On</label>
                            <div>{{ $ticket->acknowledged_at ? $ticket->acknowledged_at->format('d M Y, h:i A') : $ticket->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Statutory 30-Day Target</label>
                            <div class="text-primary fw-semibold">{{ $ticket->created_at->addDays(30)->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-4">
                        <label class="text-muted small fw-semibold">Complaint Summary:</label>
                        <p class="small text-dark mb-0">{{ $ticket->complaint_details }}</p>
                    </div>

                    @if ($ticket->status === 'resolved')
                        <div class="alert alert-success border-success p-3 rounded-3 mb-0">
                            <h6 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i>Official Redressal Resolution</h6>
                            <p class="small mb-1">{{ $ticket->resolution_notes }}</p>
                            <small class="text-muted">Resolved on: {{ $ticket->resolved_at ? $ticket->resolved_at->format('d M Y, h:i A') : '—' }}</small>
                        </div>
                    @else
                        <div class="alert alert-info border-info p-3 rounded-3 mb-0">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Your ticket is currently under review by our compliance team. We will contact you at <strong>{{ $ticket->phone }}</strong> / <strong>{{ $ticket->email }}</strong> upon concluding the investigation.
                        </div>
                    @endif
                </div>
            </div>
        @elseif (request()->isMethod('POST'))
            <div class="alert alert-warning border-warning text-center p-4 rounded-4">
                <i class="bi bi-exclamation-circle fs-2 text-warning mb-2 d-block"></i>
                <h5 class="fw-bold mb-1">Ticket Not Found</h5>
                <p class="small text-muted mb-0">Please verify your Ticket Reference Number (e.g., TFC-GR-YYYYMMDD-XXXX) and retry.</p>
            </div>
        @endisset
    </div>
</section>
@endsection
