@extends('admin.layouts.app')

@section('title', 'TFC Direct Selling KYC Review Desk')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">Direct Selling KYC Review Desk</h5>
        <p class="text-muted small mb-0">Review submitted member identity documents in compliance with UIDAI regulations.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.kyc.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by Member ID, Name, Phone..." value="{{ $search }}">
            </div>
            <div class="col-6 col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="verified" {{ $status === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('admin.tfc.kyc.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($submissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Member ID</th>
                            <th>Member Name</th>
                            <th>Document Type</th>
                            <th>Masked Number</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($submissions as $item)
                            <tr>
                                <td>
                                    <span class="font-monospace fw-bold text-primary">{{ $item->directSellingMember->tfc_member_id }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->directSellingMember->full_name }}</div>
                                    <div class="text-muted small">{{ $item->directSellingMember->mobile }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase">{{ $item->document_type }}</span>
                                </td>
                                <td>
                                    <span class="font-monospace fw-semibold">{{ $item->masked_document_number }}</span>
                                </td>
                                <td class="text-muted">{{ $item->submitted_at ? $item->submitted_at->format('d M Y, h:i A') : '—' }}</td>
                                <td>
                                    @if ($item->status === 'verified')
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25">Verified</span>
                                    @elseif ($item->status === 'pending')
                                        <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50">Pending Review</span>
                                    @elseif ($item->status === 'rejected')
                                        <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.tfc.kyc.show', $item->id) }}" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 0.75rem;">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($submissions->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $submissions->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-check fs-2 d-block mb-2 text-secondary"></i>
                <div class="fw-semibold">No KYC submissions found matching criteria.</div>
            </div>
        @endif
    </div>
</div>
@endsection
