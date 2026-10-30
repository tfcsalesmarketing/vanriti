@extends('admin.layouts.app')

@section('title', 'Consumer Grievance Tickets (Rule 7 Direct Selling Rules)')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">Consumer Grievance Redressal Tickets</h5>
        <p class="text-muted small mb-0">
            Mandatory redressal under <strong>Rule 7 of Consumer Protection (Direct Selling) Rules, 2021</strong>: 48-hour acknowledgment, 30-day resolution timeline.
        </p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.grievances.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search ticket #, consumer name, email or order #..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                    <option value="under_investigation" {{ request('status') === 'under_investigation' ? 'selected' : '' }}>Under Investigation</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('admin.tfc.grievances.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ticket Ref #</th>
                        <th>Consumer</th>
                        <th>Category</th>
                        <th>Order Ref</th>
                        <th>Filed Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">{{ $ticket->ticket_number }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $ticket->consumer_name }}</div>
                                <div class="text-muted small">
                                    <i class="bi bi-telephone me-1"></i>{{ $ticket->phone }} |
                                    <i class="bi bi-envelope me-1"></i>{{ $ticket->email }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ ucwords(str_replace('_', ' ', $ticket->category)) }}
                                </span>
                            </td>
                            <td class="small">{{ $ticket->order_number ?: '—' }}</td>
                            <td class="small text-muted">{{ $ticket->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="badge {{ $ticket->status === 'resolved' ? 'bg-success' : ($ticket->status === 'under_investigation' ? 'bg-warning text-dark' : 'bg-info text-dark') }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.tfc.grievances.show', $ticket) }}" class="btn btn-sm btn-outline-primary" title="Process Ticket">
                                    <i class="bi bi-gear"></i> Process
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No grievance tickets recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($tickets->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
@endsection
