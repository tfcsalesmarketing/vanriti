@extends('tfc.member.layouts.portal')

@section('title', 'Visual Binary Tree Genealogy')

@push('styles')
<style>
    .genealogy-scroll {
        overflow-x: auto;
        padding: 2rem 1rem;
        min-height: 480px;
    }

    .tree-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 720px;
    }

    .tree-node-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 0 1rem;
        position: relative;
    }

    .tree-node-card {
        width: 170px;
        background: #FFFFFF;
        border: 2px solid var(--tfc-card-border);
        border-radius: 12px;
        padding: 0.85rem;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: all 0.25s ease;
        position: relative;
        z-index: 2;
    }

    .tree-node-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
        border-color: var(--tfc-navy);
    }

    .tree-node-card.root-node {
        border-color: #D49B24;
        background: linear-gradient(180deg, #FFFFFF 0%, #FFFDF7 100%);
    }

    .tree-node-card.vacant-node {
        border: 2px dashed #CBD5E1;
        background: #F8FAFC;
        cursor: default;
    }

    .tree-node-card.vacant-node:hover {
        transform: none;
        box-shadow: none;
    }

    .tree-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        margin: 0 auto 0.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .tree-children-row {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        position: relative;
        width: 100%;
    }

    /* Branch lines */
    .tree-branch-line {
        width: 2px;
        height: 2rem;
        background: #CBD5E1;
        margin: 0 auto;
    }

    .connector-horizontal {
        height: 2px;
        background: #CBD5E1;
        width: 50%;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    {{-- Breadcrumb & Controls --}}
    <div class="member-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark brand-font">Interactive Binary Tree</h4>
                <div class="small text-muted">
                    Viewing Root: <strong class="text-primary">{{ $rootMember->full_name }} ({{ $rootMember->tfc_member_id }})</strong>
                    @if ($rootMember->id !== $currentMember->id)
                        <a href="{{ route('tfc.preview.member.team.tree') }}" class="btn btn-sm btn-outline-secondary ms-2 py-0.5 px-2" style="font-size: 0.75rem;">
                            <i class="bi bi-arrow-return-left"></i> Reset to My Root
                        </a>
                    @endif
                </div>
            </div>

            {{-- Jump to Member Search --}}
            <form method="GET" action="{{ route('tfc.preview.member.team.tree') }}" class="d-flex gap-2">
                <input type="text" name="root" class="form-control form-control-sm font-monospace text-uppercase" placeholder="Search Member ID..." style="width: 190px;" value="{{ request('root') }}">
                <button type="submit" class="btn btn-sm btn-primary">Find</button>
            </form>
        </div>

        {{-- Legend --}}
        <div class="d-flex flex-wrap align-items-center gap-3 pt-3 mt-3 border-top small text-muted">
            <span class="fw-semibold text-dark">Legend:</span>
            <span><i class="bi bi-circle-fill text-warning"></i> 🟠 Orange Leg</span>
            <span><i class="bi bi-circle-fill text-info"></i> 🔵 Blue Leg</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Direct Referral</span>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Spillover Placement</span>
            <span class="badge bg-light text-muted border border-dashed">Open Position</span>
        </div>
    </div>

    {{-- Tree Display Canvas --}}
    <div class="member-card p-4">
        <div class="genealogy-scroll">
            <div class="tree-container">

                {{-- LEVEL 0: ROOT NODE --}}
                <div class="tree-node-wrapper">
                    <div class="tree-node-card root-node" onclick="showNodeDetails({{ $rootMember->id }})">
                        <div class="tree-avatar bg-warning text-dark">
                            {{ strtoupper(substr($rootMember->full_name, 0, 1)) }}
                        </div>
                        <div class="fw-bold text-dark text-truncate small">{{ $rootMember->full_name }}</div>
                        <div class="font-monospace text-primary fw-bold" style="font-size: 0.72rem;">{{ $rootMember->tfc_member_id }}</div>
                        <span class="badge bg-dark text-warning mt-1" style="font-size: 0.65rem;">ROOT</span>
                    </div>

                    {{-- Vertical Stem Below Root --}}
                    @if (isset($treeData['children']['orange']) || isset($treeData['children']['blue']))
                        <div class="tree-branch-line"></div>
                    @endif
                </div>

                {{-- LEVEL 1: IMMEDIATE CHILDREN --}}
                <div class="tree-children-row">
                    {{-- ORANGE BRANCH --}}
                    <div class="tree-node-wrapper">
                        @php $orange1 = $treeData['children']['orange'] ?? null; @endphp
                        @if ($orange1)
                            <div class="tree-node-card" style="border-top: 3px solid #F97316;" onclick="showNodeDetails({{ $orange1['id'] }})">
                                <div class="tree-avatar text-white" style="background: #F97316;">
                                    {{ strtoupper(substr($orange1['name'], 0, 1)) }}
                                </div>
                                <div class="fw-bold text-dark text-truncate small">{{ $orange1['name'] }}</div>
                                <div class="font-monospace text-primary fw-bold" style="font-size: 0.72rem;">{{ $orange1['tfc_member_id'] }}</div>
                                <div class="mt-1">
                                    <span class="badge leg-badge-orange" style="font-size: 0.62rem;">🟠 Orange</span>
                                    @if ($orange1['sponsor_id'] === $rootMember->id)
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.62rem;">Direct</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.62rem;">Spillover</span>
                                    @endif
                                </div>
                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $orange1['tfc_member_id']]) }}" class="btn btn-xs btn-outline-secondary mt-1 py-0 px-1" style="font-size: 0.65rem;">
                                    Drill Down &darr;
                                </a>
                            </div>

                            {{-- Stem to Level 2 --}}
                            @if (isset($orange1['children']['orange']) || isset($orange1['children']['blue']))
                                <div class="tree-branch-line"></div>
                            @endif
                        @else
                            <div class="tree-node-card vacant-node">
                                <div class="tree-avatar bg-light text-muted"><i class="bi bi-plus-lg"></i></div>
                                <div class="text-muted small fw-medium">Open Orange</div>
                                <div class="small text-secondary" style="font-size: 0.68rem;">Vacant Slot</div>
                            </div>
                        @endif

                        {{-- LEVEL 2 UNDER ORANGE --}}
                        @if ($orange1)
                            <div class="tree-children-row" style="margin-top: 1.5rem;">
                                {{-- Orange's Left (Orange) --}}
                                @php $orange2_O = $orange1['children']['orange'] ?? null; @endphp
                                <div class="tree-node-wrapper">
                                    @if ($orange2_O)
                                        <div class="tree-node-card" style="width: 145px; padding: 0.65rem;" onclick="showNodeDetails({{ $orange2_O['id'] }})">
                                            <div class="fw-bold text-dark text-truncate small">{{ $orange2_O['name'] }}</div>
                                            <div class="font-monospace text-primary" style="font-size: 0.68rem;">{{ $orange2_O['tfc_member_id'] }}</div>
                                            <span class="badge leg-badge-orange" style="font-size: 0.6rem;">🟠 Orange</span>
                                            <div class="mt-1">
                                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $orange2_O['tfc_member_id']]) }}" class="text-primary text-decoration-none" style="font-size: 0.65rem;">View &darr;</a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="tree-node-card vacant-node" style="width: 145px; padding: 0.65rem;">
                                            <div class="text-muted small" style="font-size: 0.7rem;">Open Slot</div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Orange's Right (Blue) --}}
                                @php $orange2_B = $orange1['children']['blue'] ?? null; @endphp
                                <div class="tree-node-wrapper">
                                    @if ($orange2_B)
                                        <div class="tree-node-card" style="width: 145px; padding: 0.65rem;" onclick="showNodeDetails({{ $orange2_B['id'] }})">
                                            <div class="fw-bold text-dark text-truncate small">{{ $orange2_B['name'] }}</div>
                                            <div class="font-monospace text-primary" style="font-size: 0.68rem;">{{ $orange2_B['tfc_member_id'] }}</div>
                                            <span class="badge leg-badge-blue" style="font-size: 0.6rem;">🔵 Blue</span>
                                            <div class="mt-1">
                                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $orange2_B['tfc_member_id']]) }}" class="text-primary text-decoration-none" style="font-size: 0.65rem;">View &darr;</a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="tree-node-card vacant-node" style="width: 145px; padding: 0.65rem;">
                                            <div class="text-muted small" style="font-size: 0.7rem;">Open Slot</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- BLUE BRANCH --}}
                    <div class="tree-node-wrapper">
                        @php $blue1 = $treeData['children']['blue'] ?? null; @endphp
                        @if ($blue1)
                            <div class="tree-node-card" style="border-top: 3px solid #0284C7;" onclick="showNodeDetails({{ $blue1['id'] }})">
                                <div class="tree-avatar text-white" style="background: #0284C7;">
                                    {{ strtoupper(substr($blue1['name'], 0, 1)) }}
                                </div>
                                <div class="fw-bold text-dark text-truncate small">{{ $blue1['name'] }}</div>
                                <div class="font-monospace text-primary fw-bold" style="font-size: 0.72rem;">{{ $blue1['tfc_member_id'] }}</div>
                                <div class="mt-1">
                                    <span class="badge leg-badge-blue" style="font-size: 0.62rem;">🔵 Blue</span>
                                    @if ($blue1['sponsor_id'] === $rootMember->id)
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.62rem;">Direct</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.62rem;">Spillover</span>
                                    @endif
                                </div>
                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $blue1['tfc_member_id']]) }}" class="btn btn-xs btn-outline-secondary mt-1 py-0 px-1" style="font-size: 0.65rem;">
                                    Drill Down &darr;
                                </a>
                            </div>

                            {{-- Stem to Level 2 --}}
                            @if (isset($blue1['children']['orange']) || isset($blue1['children']['blue']))
                                <div class="tree-branch-line"></div>
                            @endif
                        @else
                            <div class="tree-node-card vacant-node">
                                <div class="tree-avatar bg-light text-muted"><i class="bi bi-plus-lg"></i></div>
                                <div class="text-muted small fw-medium">Open Blue</div>
                                <div class="small text-secondary" style="font-size: 0.68rem;">Vacant Slot</div>
                            </div>
                        @endif

                        {{-- LEVEL 2 UNDER BLUE --}}
                        @if ($blue1)
                            <div class="tree-children-row" style="margin-top: 1.5rem;">
                                {{-- Blue's Left (Orange) --}}
                                @php $blue2_O = $blue1['children']['orange'] ?? null; @endphp
                                <div class="tree-node-wrapper">
                                    @if ($blue2_O)
                                        <div class="tree-node-card" style="width: 145px; padding: 0.65rem;" onclick="showNodeDetails({{ $blue2_O['id'] }})">
                                            <div class="fw-bold text-dark text-truncate small">{{ $blue2_O['name'] }}</div>
                                            <div class="font-monospace text-primary" style="font-size: 0.68rem;">{{ $blue2_O['tfc_member_id'] }}</div>
                                            <span class="badge leg-badge-orange" style="font-size: 0.6rem;">🟠 Orange</span>
                                            <div class="mt-1">
                                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $blue2_O['tfc_member_id']]) }}" class="text-primary text-decoration-none" style="font-size: 0.65rem;">View &darr;</a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="tree-node-card vacant-node" style="width: 145px; padding: 0.65rem;">
                                            <div class="text-muted small" style="font-size: 0.7rem;">Open Slot</div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Blue's Right (Blue) --}}
                                @php $blue2_B = $blue1['children']['blue'] ?? null; @endphp
                                <div class="tree-node-wrapper">
                                    @if ($blue2_B)
                                        <div class="tree-node-card" style="width: 145px; padding: 0.65rem;" onclick="showNodeDetails({{ $blue2_B['id'] }})">
                                            <div class="fw-bold text-dark text-truncate small">{{ $blue2_B['name'] }}</div>
                                            <div class="font-monospace text-primary" style="font-size: 0.68rem;">{{ $blue2_B['tfc_member_id'] }}</div>
                                            <span class="badge leg-badge-blue" style="font-size: 0.6rem;">🔵 Blue</span>
                                            <div class="mt-1">
                                                <a href="{{ route('tfc.preview.member.team.tree', ['root' => $blue2_B['tfc_member_id']]) }}" class="text-primary text-decoration-none" style="font-size: 0.65rem;">View &darr;</a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="tree-node-card vacant-node" style="width: 145px; padding: 0.65rem;">
                                            <div class="text-muted small" style="font-size: 0.7rem;">Open Slot</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Node Details Modal -->
<div class="modal fade" id="nodeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalMemberName">Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center fs-4" style="width: 50px; height: 50px;" id="modalAvatar">
                        M
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-5" id="modalFullName">—</div>
                        <div class="font-monospace text-primary fw-bold" id="modalMemberId">—</div>
                    </div>
                </div>

                <div class="row g-3 small">
                    <div class="col-6">
                        <span class="text-muted d-block">Sponsor:</span>
                        <strong class="text-dark" id="modalSponsor">—</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Placement Parent:</span>
                        <strong class="text-dark" id="modalParent">—</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Placement Leg:</span>
                        <strong class="text-dark" id="modalLeg">—</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Joined Date:</span>
                        <strong class="text-dark" id="modalJoined">—</strong>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Directs</span>
                            <strong class="fs-6 text-primary" id="modalDirects">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 border" style="background: rgba(249, 115, 22, 0.08);">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Orange Team</span>
                            <strong class="fs-6 text-warning" id="modalOrange">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 border" style="background: rgba(2, 132, 199, 0.08);">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Blue Team</span>
                            <strong class="fs-6 text-info" id="modalBlue">0</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <a href="#" id="modalDrillDownBtn" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-diagram-3 me-1"></i> Make Root of Tree
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showNodeDetails(nodeId) {
    const modal = new bootstrap.Modal(document.getElementById('nodeDetailsModal'));
    
    fetch(`{{ url('tfc/member/portal/team/ajax-node') }}/${nodeId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            document.getElementById('modalAvatar').innerText = data.full_name.charAt(0).toUpperCase();
            document.getElementById('modalFullName').innerText = data.full_name;
            document.getElementById('modalMemberId').innerText = data.tfc_member_id;
            document.getElementById('modalSponsor').innerText = data.sponsor_name;
            document.getElementById('modalParent').innerText = data.placement_parent_name;
            document.getElementById('modalLeg').innerText = data.placement_position;
            document.getElementById('modalJoined').innerText = data.joined_at;
            document.getElementById('modalDirects').innerText = data.direct_referrals;
            document.getElementById('modalOrange').innerText = data.orange_team;
            document.getElementById('modalBlue').innerText = data.blue_team;
            document.getElementById('modalDrillDownBtn').href = `{{ route('tfc.preview.member.team.tree') }}?root=${data.tfc_member_id}`;
            modal.show();
        })
        .catch(err => {
            console.error('Failed to load node info', err);
        });
}
</script>
@endpush
