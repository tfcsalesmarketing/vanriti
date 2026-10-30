@extends('admin.layouts.app')

@section('title', 'TFC Direct Selling Members')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Direct Selling Member Directory</h5>
        <p class="text-muted small mb-0">Manage registered independent direct sellers, placement positions, and active accounts.</p>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.members.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by Member ID, Name, Phone..." value="{{ $search }}">
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ $status === 'terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="leg" class="form-select form-select-sm">
                    <option value="">All Legs</option>
                    <option value="orange" {{ $leg === 'orange' ? 'selected' : '' }}>Orange</option>
                    <option value="blue" {{ $leg === 'blue' ? 'selected' : '' }}>Blue</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('admin.tfc.members.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if ($members->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Member ID</th>
                            <th>Full Name</th>
                            <th>Mobile &amp; Email</th>
                            <th>Sponsor</th>
                            <th>Placement</th>
                            <th>KYC Status</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($members as $item)
                            <tr>
                                <td>
                                    <span class="font-monospace fw-bold text-primary">{{ $item->tfc_member_id }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->full_name }}</div>
                                </td>
                                <td>
                                    <div>{{ $item->mobile }}</div>
                                    <div class="text-muted small">{{ $item->email }}</div>
                                </td>
                                <td>
                                    @if ($item->sponsor)
                                        <div>{{ $item->sponsor->full_name }}</div>
                                        <div class="text-muted font-monospace small">{{ $item->sponsor->tfc_member_id }}</div>
                                    @else
                                        <span class="text-muted">Direct</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->placement_position === 'orange')
                                        <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #C2410C;">🟠 Orange</span>
                                    @elseif ($item->placement_position === 'blue')
                                        <span class="badge" style="background: rgba(2, 132, 199, 0.12); color: #0369A1;">🔵 Blue</span>
                                    @else
                                        <span class="badge bg-secondary">Root</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->kyc_status === 'verified')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Verified</span>
                                    @elseif ($item->kyc_status === 'pending')
                                        <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50">Pending</span>
                                    @elseif ($item->kyc_status === 'rejected')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Rejected</span>
                                    @else
                                        <span class="badge bg-light text-muted border">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif ($item->status === 'suspended')
                                        <span class="badge bg-danger">Suspended</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($item->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $item->joined_at ? $item->joined_at->format('d M Y') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.tfc.members.show', $item->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.75rem;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($members->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $members->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                <div class="fw-semibold">No direct selling members match the search criteria.</div>
            </div>
        @endif
    </div>
</div>
@endsection
