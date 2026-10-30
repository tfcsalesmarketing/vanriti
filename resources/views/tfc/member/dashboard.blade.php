@extends('tfc.member.layouts.portal')

@section('title', 'Member Dashboard')

@section('content')
<div class="container-fluid px-0">

    {{-- Welcome & ID Card Banner --}}
    <div class="card border-0 rounded-4 overflow-hidden mb-4 shadow-sm" style="background: linear-gradient(135deg, #0A1D37 0%, #1E3A8A 100%); color: #FFFFFF;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-warning text-dark fw-bold px-2.5 py-1">Direct Selling Member</span>
                        <span class="badge bg-success bg-opacity-75 text-white">Active</span>
                    </div>
                    <h2 class="fw-bold mb-1 text-white brand-font">Welcome back, {{ $member->full_name }}!</h2>
                    <p class="text-light opacity-90 small mb-3">
                        Member since {{ $member->joined_at ? $member->joined_at->format('d M Y') : 'N/A' }} | 
                        Sponsor: <strong>{{ $member->sponsor ? $member->sponsor->full_name . ' (' . $member->sponsor->tfc_member_id . ')' : 'Direct TFC' }}</strong>
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="bg-black bg-opacity-25 rounded-3 px-3 py-2 border border-white border-opacity-15 font-monospace text-warning fw-bold fs-6">
                            ID: {{ $member->tfc_member_id }}
                        </div>
                        <button class="btn btn-warning text-dark fw-semibold btn-sm d-flex align-items-center gap-1.5 py-2 px-3 shadow-sm" onclick="copyReferralLink('{{ $referralUrl }}', this)">
                            <i class="bi bi-link-45deg fs-6"></i>
                            <span>Copy Referral Link</span>
                        </button>
                        <a href="{{ route('tfc.preview.member.team.tree') }}" class="btn btn-outline-light btn-sm py-2 px-3">
                            <i class="bi bi-diagram-3 me-1"></i> Open Binary Tree
                        </a>
                    </div>
                </div>

                {{-- Digital ID Card Preview --}}
                <div class="col-lg-5">
                    <div class="p-3.5 rounded-4 border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold small text-warning" style="letter-spacing: 0.5px;">TFC SALES &amp; MARKETING</span>
                            <i class="bi bi-qr-code text-light fs-4"></i>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center fs-5" style="width: 50px; height: 50px;">
                                {{ strtoupper(substr($member->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold text-white fs-6">{{ $member->full_name }}</div>
                                <div class="text-warning small font-monospace">{{ $member->tfc_member_id }}</div>
                            </div>
                        </div>
                        <div class="row g-2 text-white small border-top border-white border-opacity-15 pt-2" style="font-size: 0.76rem;">
                            <div class="col-6">
                                <span class="text-light opacity-75">Placement Leg:</span> 
                                <strong class="text-capitalize">{{ $member->placement_position ?? 'Root' }}</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-light opacity-75">KYC:</span> 
                                <strong class="text-capitalize text-warning">{{ $member->kyc_status }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Team Statistics Cards --}}
    <div class="row g-3 mb-4">
        {{-- Direct Referrals --}}
        <div class="col-sm-6 col-xl-3">
            <div class="member-card p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Direct Referrals</span>
                    <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">{{ $stats['direct_referrals_count'] }}</h3>
                <div class="text-muted small">
                    <a href="{{ route('tfc.preview.member.team.direct') }}" class="text-primary text-decoration-none fw-medium">
                        View Direct Members <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Orange Team Count --}}
        <div class="col-sm-6 col-xl-3">
            <div class="member-card p-4" style="border-left: 4px solid #F97316;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">🟠 Orange Team</span>
                    <span class="p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #F97316;">
                        <i class="bi bi-diagram-2-fill fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">{{ $stats['orange_team_count'] }}</h3>
                <div class="text-muted small">
                    <a href="{{ route('tfc.preview.member.team.group', 'orange') }}" class="text-decoration-none fw-medium" style="color: #EA580C;">
                        Explore Orange Leg <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Blue Team Count --}}
        <div class="col-sm-6 col-xl-3">
            <div class="member-card p-4" style="border-left: 4px solid #0284C7;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">🔵 Blue Team</span>
                    <span class="p-2 rounded-3" style="background: rgba(2, 132, 199, 0.12); color: #0284C7;">
                        <i class="bi bi-diagram-2-fill fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">{{ $stats['blue_team_count'] }}</h3>
                <div class="text-muted small">
                    <a href="{{ route('tfc.preview.member.team.group', 'blue') }}" class="text-decoration-none fw-medium" style="color: #0369A1;">
                        Explore Blue Leg <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Total Downline Network --}}
        <div class="col-sm-6 col-xl-3">
            <div class="member-card p-4" style="border-left: 4px solid #D49B24;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Total Network</span>
                    <span class="p-2 rounded-3 bg-warning bg-opacity-15 text-warning">
                        <i class="bi bi-globe2 fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1 text-dark">{{ $stats['total_team_count'] }}</h3>
                <div class="text-muted small">Active Downline Members</div>
            </div>
        </div>
    </div>

    {{-- Immediate Binary Placement Status & Recent Directs --}}
    <div class="row g-4 mb-4">
        {{-- Immediate Two Positions Card --}}
        <div class="col-lg-5">
            <div class="member-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">Immediate Placements</h5>
                    <a href="{{ route('tfc.preview.member.team.tree') }}" class="btn btn-sm btn-outline-primary">Tree View</a>
                </div>
                <p class="text-muted small mb-4">Your direct left (Orange) and right (Blue) binary placements:</p>

                {{-- Orange Child Slot --}}
                <div class="p-3 mb-3 rounded-3 border d-flex align-items-center justify-content-between" style="background: rgba(249, 115, 22, 0.04);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 42px; height: 42px; background: #F97316;">
                            O
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">Orange Position</div>
                            @if ($orangeChild)
                                <div class="text-dark small fw-semibold">{{ $orangeChild->full_name }}</div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $orangeChild->tfc_member_id }}</div>
                            @else
                                <div class="text-muted small">Vacant / Available</div>
                            @endif
                        </div>
                    </div>
                    @if ($orangeChild)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Occupied</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Open Slot</span>
                    @endif
                </div>

                {{-- Blue Child Slot --}}
                <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between" style="background: rgba(2, 132, 199, 0.04);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 42px; height: 42px; background: #0284C7;">
                            B
                        </div>
                        <div>
                            <div class="fw-bold text-dark small">Blue Position</div>
                            @if ($blueChild)
                                <div class="text-dark small fw-semibold">{{ $blueChild->full_name }}</div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $blueChild->tfc_member_id }}</div>
                            @else
                                <div class="text-muted small">Vacant / Available</div>
                            @endif
                        </div>
                    </div>
                    @if ($blueChild)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Occupied</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Open Slot</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Direct Referrals List --}}
        <div class="col-lg-7">
            <div class="member-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark">Recent Direct Referrals</h5>
                    <a href="{{ route('tfc.preview.member.team.direct') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>

                @if ($recentDirects->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Member ID</th>
                                    <th>Full Name</th>
                                    <th>Leg</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach ($recentDirects as $direct)
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary">{{ $direct->tfc_member_id }}</td>
                                        <td class="fw-medium text-dark">{{ $direct->full_name }}</td>
                                        <td>
                                            @if ($direct->placement_position === 'orange')
                                                <span class="badge leg-badge-orange">Orange</span>
                                            @elseif ($direct->placement_position === 'blue')
                                                <span class="badge leg-badge-blue">Blue</span>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <div class="rounded-circle bg-light d-inline-flex p-3 mb-2 text-secondary fs-3">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="fw-semibold">No direct referrals yet</div>
                        <p class="small text-muted mb-3">Share your personal referral link to sponsor new independent members into your team.</p>
                        <button class="btn btn-sm btn-primary" onclick="copyReferralLink('{{ $referralUrl }}', this)">
                            <i class="bi bi-share me-1"></i> Copy Referral Link
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
