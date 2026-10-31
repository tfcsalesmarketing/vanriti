@extends('admin.layouts.app')

@section('title', 'TFC Sales & Marketing Operations Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0A1D37;">TFC Direct Selling Operations</h4>
        <p class="text-muted small mb-0">Overview of independent members, KYC submissions, and incoming inquiries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.tfc.members.index') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-people me-1"></i> View All Members
        </a>
        <a href="{{ route('admin.tfc.kyc.index') }}" class="btn btn-sm btn-outline-warning text-dark">
            <i class="bi bi-shield-check me-1"></i> KYC Review Desk
            @if ($stats['pending_kyc'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $stats['pending_kyc'] }}</span>
            @endif
        </a>
    </div>
</div>

{{-- KPI Metrics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3 border-0 shadow-sm" style="background: #FFFFFF; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Total Members</span>
                <span class="p-2 rounded-3" style="background: rgba(30, 58, 138, 0.1); color: #1E3A8A;"><i class="bi bi-person-badge fs-5"></i></span>
            </div>
            <div class="fs-3 fw-bold text-dark">{{ $stats['total_members'] }}</div>
            <div class="text-muted small mt-1">
                <span>Registered Direct Sellers</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3 border-0 shadow-sm" style="background: #FFFFFF; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Active Members</span>
                <span class="p-2 rounded-3" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class="bi bi-check2-circle fs-5"></i></span>
            </div>
            <div class="fs-3 fw-bold text-success">{{ $stats['active_members'] }}</div>
            <div class="text-muted small mt-1">
                <span>Active direct selling accounts</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3 border-0 shadow-sm" style="background: #FFFFFF; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Pending KYC</span>
                <span class="p-2 rounded-3" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><i class="bi bi-shield-exclamation fs-5"></i></span>
            </div>
            <div class="fs-3 fw-bold text-warning">{{ $stats['pending_kyc'] }}</div>
            <div class="text-muted small mt-1">
                <span>Awaiting document review</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card h-100 p-3 border-0 shadow-sm" style="background: #FFFFFF; border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">WhatsApp Inquiries</span>
                <span class="p-2 rounded-3" style="background: rgba(37, 211, 102, 0.1); color: #25D366;"><i class="bi bi-whatsapp fs-5"></i></span>
            </div>
            <div class="fs-3 fw-bold text-dark">{{ $stats['tfc_wa_conversations'] }}</div>
            <div class="text-muted small mt-1">
                <span>Direct Seller inquiries &amp; chat</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Recent Members --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-0">
                <h6 class="card-title mb-0 fw-bold" style="color: #0A1D37;">Recent Registered Members</h6>
                <a href="{{ route('admin.tfc.members.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member ID</th>
                                <th>Name / Mobile</th>
                                <th>Sponsor</th>
                                <th>Leg</th>
                                <th>KYC</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($recentMembers as $mem)
                                <tr>
                                    <td class="font-monospace fw-bold text-primary">
                                        <a href="{{ route('admin.tfc.members.show', $mem->id) }}" class="text-decoration-none">
                                            {{ $mem->tfc_member_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $mem->full_name }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $mem->mobile }}</div>
                                    </td>
                                    <td>
                                        @if ($mem->sponsor)
                                            <div class="small">{{ $mem->sponsor->full_name }}</div>
                                            <div class="text-muted font-monospace" style="font-size: 0.70rem;">{{ $mem->sponsor->tfc_member_id }}</div>
                                        @else
                                            <span class="text-muted">Direct</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $mem->placement_position === 'orange' ? 'bg-warning text-dark' : ($mem->placement_position === 'blue' ? 'bg-primary' : 'bg-secondary') }}">
                                            {{ ucfirst($mem->placement_position ?? 'Root') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $mem->kyc_status === 'approved' ? 'bg-success' : ($mem->kyc_status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                            {{ ucfirst($mem->kyc_status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">No members registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending KYC Review Desk --}}
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-0">
                <h6 class="card-title mb-0 fw-bold" style="color: #0A1D37;">Pending KYC Verifications</h6>
                <a href="{{ route('admin.tfc.kyc.index') }}" class="btn btn-sm btn-outline-warning text-dark">Review Desk</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Member</th>
                                <th>Documents</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse ($pendingKycs as $kyc)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $kyc->member?->full_name }}</div>
                                        <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $kyc->member?->tfc_member_id }}</div>
                                    </td>
                                    <td>
                                        <div class="small">PAN: {{ $kyc->pan_number ?: '—' }}</div>
                                        <div class="text-muted" style="font-size: 0.70rem;">Aadhaar: {{ $kyc->aadhaar_masked ?: '—' }}</div>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem;">
                                        {{ $kyc->submitted_at ? $kyc->submitted_at->diffForHumans() : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.tfc.kyc.show', $kyc->id) }}" class="btn btn-xs btn-primary py-1 px-2" style="font-size: 0.72rem;">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">No pending KYC applications. All caught up!</td>
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
