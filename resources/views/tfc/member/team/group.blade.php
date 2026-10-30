@extends('tfc.member.layouts.portal')

@section('title', ucfirst($leg) . ' Group Team')

@section('content')
<div class="container-fluid px-0">

    <div class="member-card p-4 mb-4" style="border-left: 4px solid {{ $leg === 'orange' ? '#F97316' : '#0284C7' }};">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fs-4">{{ $leg === 'orange' ? '🟠' : '🔵' }}</span>
                    <h4 class="fw-bold mb-0 text-dark brand-font">{{ ucfirst($leg) }} Group Team</h4>
                </div>
                <div class="small text-muted">All active downline team members inherited under your {{ ucfirst($leg) }} branch across all depths</div>
            </div>

            <form method="GET" action="{{ route('tfc.preview.member.team.group', $leg) }}" class="d-flex gap-2">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, ID..." value="{{ $search }}" style="width: 220px;">
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                @if ($search)
                    <a href="{{ route('tfc.preview.member.team.group', $leg) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="member-card overflow-hidden">
        @if ($members->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Depth</th>
                            <th>Member ID</th>
                            <th>Full Name</th>
                            <th>Immediate Leg</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @foreach ($members as $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">Level {{ $item->depth }}</span>
                                </td>
                                <td class="font-monospace fw-bold text-primary">{{ $item->tfc_member_id }}</td>
                                <td class="fw-medium text-dark">{{ $item->full_name }}</td>
                                <td>
                                    @if ($item->placement_position === 'orange')
                                        <span class="badge leg-badge-orange">🟠 Orange</span>
                                    @else
                                        <span class="badge leg-badge-blue">🔵 Blue</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $item->joined_at ? \Carbon\Carbon::parse($item->joined_at)->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tfc.preview.member.team.tree', ['root' => $item->tfc_member_id]) }}" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.72rem;">
                                        <i class="bi bi-diagram-3"></i> View Tree
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
                <div class="rounded-circle bg-light d-inline-flex p-3 mb-2 text-secondary fs-3">
                    <i class="bi bi-diagram-2"></i>
                </div>
                <div class="fw-semibold">No team members in your {{ ucfirst($leg) }} group yet</div>
                <p class="small text-muted">Place or direct new members into your {{ ucfirst($leg) }} leg to build this branch.</p>
            </div>
        @endif
    </div>

</div>
@endsection
