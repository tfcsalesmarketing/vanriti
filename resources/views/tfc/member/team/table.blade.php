@extends('tfc.member.layouts.portal')

@section('title', 'Direct Referrals')

@section('content')
<div class="container-fluid px-0">

    <div class="member-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark brand-font">Direct Referrals</h4>
                <div class="small text-muted">Members who registered using your unique Sponsor ID ({{ $member->tfc_member_id }})</div>
            </div>

            <form method="GET" action="{{ route('tfc.preview.member.team.direct') }}" class="d-flex gap-2">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, ID..." value="{{ $search }}" style="width: 220px;">
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                @if ($search)
                    <a href="{{ route('tfc.preview.member.team.direct') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="member-card overflow-hidden">
        @if ($directs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Member ID</th>
                            <th>Full Name</th>
                            <th>Mobile</th>
                            <th>Placement Parent</th>
                            <th>Leg</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($directs as $direct)
                            <tr>
                                <td class="font-monospace fw-bold text-primary">{{ $direct->tfc_member_id }}</td>
                                <td class="fw-medium text-dark">{{ $direct->full_name }}</td>
                                <td class="text-muted font-monospace">•••• {{ substr($direct->mobile, -4) }}</td>
                                <td>
                                    @if ($direct->placementParent)
                                        <span class="text-dark">{{ $direct->placementParent->full_name }}</span>
                                        <span class="text-muted font-monospace small">({{ $direct->placementParent->tfc_member_id }})</span>
                                    @else
                                        <span class="text-muted">Direct</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($direct->placement_position === 'orange')
                                        <span class="badge leg-badge-orange">🟠 Orange</span>
                                    @elseif ($direct->placement_position === 'blue')
                                        <span class="badge leg-badge-blue">🔵 Blue</span>
                                    @else
                                        <span class="badge bg-secondary">Root</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $direct->joined_at ? $direct->joined_at->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        {{ ucfirst($direct->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tfc.preview.member.team.tree', ['root' => $direct->tfc_member_id]) }}" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.72rem;">
                                        <i class="bi bi-diagram-3"></i> View Tree
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($directs->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $directs->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 text-muted">
                <div class="rounded-circle bg-light d-inline-flex p-3 mb-2 text-secondary fs-3">
                    <i class="bi bi-person-x"></i>
                </div>
                <div class="fw-semibold">No direct members found</div>
                <p class="small text-muted">Start sharing your referral link to build your personal team.</p>
            </div>
        @endif
    </div>

</div>
@endsection
