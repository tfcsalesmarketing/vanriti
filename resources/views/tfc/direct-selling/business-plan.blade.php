@extends('tfc.layouts.app')

@section('title', 'Business Plan & Opportunity | ' . tfc_setting('company_name', 'TFC Sales & Marketing'))
@section('meta_description', 'Explore the rewarding binary compensation plan, dual-team synergy, retail margins, and leadership rewards of TFC Sales & Marketing.')

@section('content')
{{-- Light Theme Page Header --}}
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">INCENTIVE PROGRAM</span>
        <h1 class="display-5 fw-bold text-dark mb-2">TFC Binary Compensation Plan</h1>
        <p class="text-muted lead mb-0" style="max-width: 650px; margin: 0 auto;">
            A sustainable, product-led 2-leg direct selling business model designed to reward retail sales, team mentorship, and long-term leadership.
        </p>
    </div>
</section>

{{-- 4 Core Income Streams --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">FOUR INCOME STREAMS</span>
            <h2 class="fw-bold text-dark">How You Earn With TFC</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Earn through genuine product sales to consumers, direct sponsorship, and building a balanced dual-team network.
            </p>
        </div>

        <div class="row g-4">
            {{-- 1. Retail Profit --}}
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="p-3 rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="background: rgba(16, 185, 129, 0.1); color: #10B981; width: 50px; height: 50px;">
                        <i class="ri-money-rupee-circle-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">1. Retail Margin</h5>
                    <div class="fs-4 fw-bold text-success mb-2">Up to 30%</div>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                        Buy genuine consumer formulations at special Distributor Price (DP) and retail them at Maximum Retail Price (MRP).
                    </p>
                </div>
            </div>

            {{-- 2. Direct Sponsor Bonus --}}
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="p-3 rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="background: rgba(37, 99, 235, 0.1); color: #2563EB; width: 50px; height: 50px;">
                        <i class="ri-user-star-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">2. Sponsor Rewards</h5>
                    <div class="fs-4 fw-bold text-primary mb-2">Direct Benefit</div>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                        Receive direct sponsor rewards whenever you introduce new independent direct sellers to the TFC family.
                    </p>
                </div>
            </div>

            {{-- 3. Dual Team Binary Matching --}}
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="p-3 rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="background: rgba(212, 155, 36, 0.15); color: #D49B24; width: 50px; height: 50px;">
                        <i class="ri-git-merge-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">3. Team Matching</h5>
                    <div class="fs-4 fw-bold text-warning mb-2">Dual Leg Match</div>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                        Build an Orange Team and a Blue Team. Earn recurring binary matching bonuses as sales volume balances across both legs.
                    </p>
                </div>
            </div>

            {{-- 4. Leadership Ranks & Recognition --}}
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="p-3 rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6; width: 50px; height: 50px;">
                        <i class="ri-trophy-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">4. Leadership Rewards</h5>
                    <div class="fs-4 fw-bold mb-2" style="color: #8B5CF6;">Lifestyle Awards</div>
                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                        Unlock national leadership conclaves, gadget bonuses, luxury travel retreats, and recognition pins as your team grows.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Visual 2-Leg (Orange & Blue) Model Explanation --}}
<section class="py-5" style="background-color: #F8FAFC;">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">THE DUAL TEAM SYNERGY</span>
                <h2 class="fw-bold text-dark mb-3">Understanding The 2-Leg Binary Structure</h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">
                    Unlike complex multi-level matrices that divide energy across countless individual lines, TFC uses a high-velocity <strong>Dual-Team Structure</strong> (Orange Team on the left, Blue Team on the right).
                </p>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-white border shadow-sm">
                        <div class="p-2 rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="background: #F97316; width: 36px; height: 36px; font-size: 0.85rem;">O</div>
                        <div>
                            <strong class="text-dark d-block">Orange Team (Left Leg)</strong>
                            <p class="text-muted small mb-0">Build, mentor, and activate members in your left organization to accumulate team product points.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-white border shadow-sm">
                        <div class="p-2 rounded-circle text-white fw-bold d-flex align-items-center justify-content-center" style="background: #0284C7; width: 36px; height: 36px; font-size: 0.85rem;">B</div>
                        <div>
                            <strong class="text-dark d-block">Blue Team (Right Leg)</strong>
                            <p class="text-muted small mb-0">Cultivate your right organization. When both legs generate volume, weekly matching bonuses trigger automatically.</p>
                        </div>
                    </div>
                </div>
                <p class="text-muted small">
                    <i class="ri-information-line text-warning me-1"></i> Spillover advantage: Members introduced by your upline can be placed into your organization, boosting team volume.
                </p>
            </div>

            {{-- Binary Diagram Card --}}
            <div class="col-12 col-lg-6 text-center">
                <div class="p-4 p-md-5 rounded-4 shadow-sm bg-white border">
                    {{-- Root Member --}}
                    <div class="d-inline-flex flex-column align-items-center p-3 rounded-3 mb-4 shadow-sm" style="background: #0A1D37; color: #FFFFFF; min-width: 140px;">
                        <i class="ri-user-star-fill text-warning fs-3 mb-1"></i>
                        <span class="fw-bold small">YOU (Distributor)</span>
                    </div>

                    {{-- Tree Branches --}}
                    <div class="position-relative mb-4">
                        <div style="height: 2px; background: #CBD5E1; width: 60%; margin: 0 auto;"></div>
                    </div>

                    {{-- Children --}}
                    <div class="row g-3 justify-content-center">
                        <div class="col-6">
                            <div class="p-3 rounded-3 shadow-sm h-100" style="background: rgba(249, 115, 22, 0.08); border: 2px solid #F97316;">
                                <div class="text-warning fw-bold fs-4 mb-1">🟠</div>
                                <div class="fw-bold text-dark small">Orange Leg</div>
                                <div class="text-muted small" style="font-size: 0.72rem;">Left Team Volume</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 shadow-sm h-100" style="background: rgba(2, 132, 199, 0.08); border: 2px solid #0284C7;">
                                <div class="text-info fw-bold fs-4 mb-1">🔵</div>
                                <div class="fw-bold text-dark small">Blue Leg</div>
                                <div class="text-muted small" style="font-size: 0.72rem;">Right Team Volume</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1.5 fw-semibold">
                            Matching Bonus Triggered on Balanced Sales
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Leadership Ranks Grid --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">LEADERSHIP ADVANCEMENT</span>
            <h2 class="fw-bold text-dark">Ranks &amp; Milestones</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                As you help your team members grow and advance, your leadership rank elevates with lifetime rewards.
            </p>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">⭐</div>
                    <h6 class="fw-bold text-dark mb-1">Star</h6>
                    <small class="text-muted">Active in both legs</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">🥉</div>
                    <h6 class="fw-bold text-dark mb-1">Bronze</h6>
                    <small class="text-muted">Team Builder</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">🥈</div>
                    <h6 class="fw-bold text-dark mb-1">Silver</h6>
                    <small class="text-muted">Regional Leader</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">🥇</div>
                    <h6 class="fw-bold text-dark mb-1">Gold</h6>
                    <small class="text-muted">Executive Leader</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">💎</div>
                    <h6 class="fw-bold text-dark mb-1">Platinum</h6>
                    <small class="text-muted">National Director</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="p-3 rounded-3 text-center border h-100" style="background: #FAFAFA;">
                    <div class="fs-2 mb-2">👑</div>
                    <h6 class="fw-bold text-dark mb-1">Crown</h6>
                    <small class="text-muted">Council Member</small>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Action Call to Action --}}
<section class="py-5" style="background-color: #F8FAFC;">
    <div class="container py-2 text-center">
        <div class="p-4 p-md-5 rounded-4 shadow-sm text-center" style="background: linear-gradient(135deg, #0A1D37 0%, #1E3A8A 100%); color: #FFFFFF; border-radius: 20px !important;">
            <h2 class="display-6 fw-bold text-white mb-2">Take Charge of Your Entrepreneurial Future</h2>
            <p class="text-white-50 lead fs-6 mx-auto mb-4" style="max-width: 600px;">
                No registration fee. No forced kits. Pure product-driven prosperity backed by complete digital tools and live mentorship.
            </p>
            <div class="d-inline-flex flex-wrap gap-3 justify-content-center">
                <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-warning text-dark fw-bold px-4 py-3 rounded-3 shadow-sm">
                    Register as Direct Seller Free &rarr;
                </a>
                <a href="{{ tfc_route('contact') }}" class="btn btn-outline-light px-4 py-3 fw-semibold rounded-3">
                    Contact Corporate Office
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
