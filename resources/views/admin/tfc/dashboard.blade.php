@extends('admin.layouts.app')

@section('title', 'TFC Sales & Marketing Dashboard')

@section('content')
{{-- Statutory Safeguards Alert Header --}}
<div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-shield-exclamation fs-3 text-warning"></i>
        <div>
            <div class="fw-bold text-dark">Statutory Direct-Selling Disclosure Notice</div>
            <div class="small text-muted">
                Per Consumer Protection (Direct Selling) Rules 2021 &amp; 2023 Amendment: Incomplete legal policies and officer declarations remain strictly in <strong>Draft</strong> state until official administrative details are confirmed.
            </div>
        </div>
    </div>
    <a href="{{ route('admin.tfc.settings.index') }}" class="btn btn-sm btn-warning text-dark fw-semibold text-nowrap">
        <i class="bi bi-gear-fill me-1"></i>Configure Disclosures
    </a>
</div>

{{-- KPI Metrics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Live Synced Products</span>
                <span class="stat-icon" style="background:#ECFDF5; color:#047857;"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="stat-value">{{ $stats['total_synced_products'] }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>Directly from VANRITI catalogue</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Statutory &amp; CMS Pages</span>
                <span class="stat-icon" style="background:#FFFBEB; color:#B45309;"><i class="bi bi-file-earmark-richtext"></i></span>
            </div>
            <div class="stat-value">{{ $stats['total_pages'] }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $stats['draft_pages'] }} in Draft</span>
                <span>Safeguarded</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Seller Inquiries</span>
                <span class="stat-icon" style="background:#EEF4FF; color:#3538CD;"><i class="bi bi-people"></i></span>
            </div>
            <div class="stat-value">{{ $stats['new_inquiries'] }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <i class="bi bi-bell-fill text-primary"></i>
                <span>Pending review</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Grievances (Rule 7)</span>
                <span class="stat-icon" style="background:#FEF2F2; color:#B91C1C;"><i class="bi bi-shield-exclamation"></i></span>
            </div>
            <div class="stat-value text-danger">{{ $stats['pending_grievances'] }}</div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
                <i class="bi bi-clock-history text-danger"></i>
                <span>48h ack / 30d resolution rule</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Recent Inquiries & Direct-Seller Applications --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold">Recent Inquiries &amp; Applications</h6>
                <a href="{{ route('admin.tfc.inquiries.index') }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Applicant</th>
                                <th>Type</th>
                                <th>City / State</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentInquiries as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">{{ $item->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $item->phone }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->type === 'seller_application' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $item->type === 'seller_application' ? 'Seller App' : 'General' }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $item->city ?: '—' }}, {{ $item->state ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $item->status === 'new' ? 'bg-danger' : ($item->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-success') }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tfc.inquiries.show', $item) }}" class="btn btn-sm btn-light">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">No inquiries received yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Consumer Grievance Tickets (Rule 7) --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold">Consumer Grievance Tickets</h6>
                <a href="{{ route('admin.tfc.grievances.index') }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket #</th>
                                <th>Consumer</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentGrievances as $ticket)
                                <tr>
                                    <td class="fw-bold small text-primary">{{ $ticket->ticket_number }}</td>
                                    <td>
                                        <div class="small fw-semibold">{{ $ticket->consumer_name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $ticket->phone }}</div>
                                    </td>
                                    <td class="small">{{ ucwords(str_replace('_', ' ', $ticket->category)) }}</td>
                                    <td>
                                        <span class="badge {{ $ticket->status === 'resolved' ? 'bg-success' : ($ticket->status === 'under_investigation' ? 'bg-warning text-dark' : 'bg-info text-dark') }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tfc.grievances.show', $ticket) }}" class="btn btn-sm btn-light">Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">No grievances filed.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
