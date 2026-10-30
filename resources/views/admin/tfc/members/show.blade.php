@extends('admin.layouts.app')

@section('title', 'Member Details | ' . $member->tfc_member_id)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.tfc.members.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0">Member: {{ $member->full_name }}</h5>
            <span class="font-monospace text-primary fw-bold">{{ $member->tfc_member_id }}</span>
        </div>
    </div>

    {{-- Status Update Form --}}
    <form action="{{ route('admin.tfc.members.status', $member->id) }}" method="POST" class="d-flex align-items-center gap-2">
        @csrf
        <select name="status" class="form-select form-select-sm" style="width: 140px;">
            <option value="active" {{ $member->status === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $member->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ $member->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="terminated" {{ $member->status === 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
    </form>
</div>

<div class="row g-4">
    {{-- Membership Details --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Genealogy &amp; Placement Hierarchy</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Direct Sponsor:</span>
                        @if ($member->sponsor)
                            <strong>{{ $member->sponsor->full_name }}</strong>
                            <div class="text-primary font-monospace">{{ $member->sponsor->tfc_member_id }}</div>
                        @else
                            <span class="text-muted">None (Direct Company Root)</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Placement Parent:</span>
                        @if ($member->placementParent)
                            <strong>{{ $member->placementParent->full_name }}</strong>
                            <div class="text-primary font-monospace">{{ $member->placementParent->tfc_member_id }}</div>
                        @else
                            <span class="text-muted">None (Root)</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Placement Position:</span>
                        <strong class="text-capitalize">{{ $member->placement_position ?? 'Root' }} Leg</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Registration Date:</span>
                        <strong>{{ $member->joined_at ? $member->joined_at->format('d M Y, h:i A') : '—' }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Registration IP:</span>
                        <span class="font-monospace text-dark">{{ $member->registration_ip ?? 'Unknown' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">VANRITI User ID:</span>
                        <span class="font-monospace text-dark">#{{ $member->user_id }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Personal Contact Details --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Contact Information</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Email:</span>
                        <strong class="text-dark">{{ $member->email }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Mobile:</span>
                        <strong class="text-dark">{{ $member->mobile }}</strong>
                    </div>
                    <div class="col-12">
                        <span class="text-muted d-block">Address:</span>
                        <div class="text-dark">
                            {{ $member->address_line ?? 'Not provided' }}
                            @if ($member->city || $member->state || $member->pincode)
                                <br>{{ $member->city }}, {{ $member->state }} - {{ $member->pincode }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Downline Counts & KYC Status --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Real-Time Team Counts</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted d-block small">Direct Referrals</span>
                            <h4 class="fw-bold text-primary mb-0 mt-1">{{ $stats['direct_referrals_count'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-light border">
                            <span class="text-muted d-block small">Total Network</span>
                            <h4 class="fw-bold text-dark mb-0 mt-1">{{ $stats['total_team_count'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 border" style="background: rgba(249, 115, 22, 0.08);">
                            <span class="text-muted d-block small">🟠 Orange Leg</span>
                            <h4 class="fw-bold text-warning mb-0 mt-1">{{ $stats['orange_team_count'] }}</h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 border" style="background: rgba(2, 132, 199, 0.08);">
                            <span class="text-muted d-block small">🔵 Blue Leg</span>
                            <h4 class="fw-bold text-info mb-0 mt-1">{{ $stats['blue_team_count'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KYC Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">KYC Status</h6>
                @if ($member->kyc)
                    <a href="{{ route('admin.tfc.kyc.show', $member->kyc->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.72rem;">Review Submission</a>
                @endif
            </div>
            <div class="card-body p-4 small">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted">Status:</span>
                    <strong class="text-capitalize">{{ $member->kyc_status }}</strong>
                </div>
                @if ($member->kyc)
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted">Doc Type:</span>
                        <span>{{ ucfirst($member->kyc->document_type) }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted">Masked Number:</span>
                        <span class="font-monospace fw-bold">{{ $member->kyc->masked_document_number }}</span>
                    </div>
                @else
                    <div class="text-muted">No KYC submitted yet (Optional).</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
