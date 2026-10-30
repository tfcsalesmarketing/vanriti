@extends('tfc.layouts.app')

@section('title', tfc_setting('incentive_meta_title', 'TFC Incentive & Rewards Program | ' . tfc_setting('company_name', 'TFC Sales & Marketing')))
@section('meta_description', 'Discover the transparent, product-led TFC Incentive & Rewards Program. Learn about Product Credits (PC), activation packages, binary matching, booster incentives, and daily caps.')

@section('content')
{{-- Section 1: Hero & Introduction --}}
<section class="py-5" style="background: linear-gradient(135deg, #061224 0%, #0A1D37 60%, #132D54 100%); color: #FFFFFF;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-8">
                <span class="badge tfc-badge-gold mb-3">
                    <i class="bi bi-graph-up-arrow me-1"></i>Official Business Plan &bull; Sales-Based Structure
                </span>
                <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -0.02em; line-height: 1.2;">
                    Recognising Genuine Sales. <br>
                    <span style="color: var(--tfc-gold);">Encouraging Consistent Growth.</span>
                </h1>
                <p class="lead text-white-50 mb-4" style="max-width: 650px; line-height: 1.7;">
                    The TFC Incentive &amp; Rewards Program is designed around eligible product purchases and authentic commercial activity. We reward independent direct sellers for introducing and retailing certified VANRITI botanical formulations to consumers.
                </p>

                {{-- Prominent Mandatory Disclosure --}}
                <div class="p-3 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-15 text-white-50 small mb-4" style="max-width: 720px; line-height: 1.6;">
                    <i class="bi bi-shield-exclamation text-warning me-1"></i>
                    <strong>Statutory Earning Disclaimer:</strong> Participation in TFC's direct-selling activity does not guarantee earnings. Actual incentives, where earned, depend strictly upon qualifying business activity, customer demand, individual effort, and approved incentive rules.
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a href="#product-credit" class="btn btn-tfc-primary btn-lg">
                        <i class="bi bi-diagram-3-fill me-2"></i>Explore the Incentive Plan
                    </a>
                    <a href="{{ tfc_route('direct-selling') }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-info-circle me-2"></i>About Direct Selling
                    </a>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="p-4 rounded-4 shadow-lg" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(12px);">
                    <h6 class="text-warning fw-bold text-uppercase mb-3" style="letter-spacing: 0.5px; font-size: 0.82rem;">
                        Core Program Pillars
                    </h6>
                    <div class="d-flex flex-column gap-3 text-white-50 small">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">1</span>
                            <span><strong>Zero Registration Fee:</strong> Free enrollment for all.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">2</span>
                            <span><strong>Product Credit (PC):</strong> Sales-volume measurement.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">3</span>
                            <span><strong>Team Incentives:</strong> Direct Sponsor &amp; Binary Matching.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">4</span>
                            <span><strong>Daily Settlement:</strong> 11:50 PM IST daily cutoff.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 2: Understand Product Credit (PC) --}}
<section id="product-credit" class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Volume Measurement</div>
            <h2 class="tfc-section-title">Understanding Product Credit (PC)</h2>
            <p class="text-muted mx-auto" style="max-width: 680px;">
                Product Credit (PC) is the standard business-volume metric assigned to eligible VANRITI products for incentive calculations.
            </p>
        </div>

        <div class="row align-items-center g-5 mb-4">
            <div class="col-12 col-lg-7">
                <div class="table-responsive shadow-sm rounded-3 border">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-dark" style="background-color: var(--tfc-navy);">
                            <tr>
                                <th class="py-3 text-start ps-4">Product Selling Price</th>
                                <th class="py-3">Proposed PC %</th>
                                <th class="py-3 text-end pe-4">Allocated Product Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">&#8377;1,000</td>
                                <td><span class="badge bg-light text-dark border px-2 py-1">50% PC</span></td>
                                <td class="text-end pe-4 fw-bold text-primary">500 PC</td>
                            </tr>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">&#8377;1,000</td>
                                <td><span class="badge bg-light text-dark border px-2 py-1">100% PC</span></td>
                                <td class="text-end pe-4 fw-bold text-primary">1,000 PC</td>
                            </tr>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">&#8377;1,000</td>
                                <td><span class="badge bg-light text-dark border px-2 py-1">150% PC</span></td>
                                <td class="text-end pe-4 fw-bold text-primary">1,500 PC</td>
                            </tr>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">&#8377;1,000</td>
                                <td><span class="badge bg-light text-dark border px-2 py-1">200% PC</span></td>
                                <td class="text-end pe-4 fw-bold text-primary">2,000 PC</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="p-4 bg-light rounded-3 border">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle-fill text-warning me-2"></i>Key Facts About Product Credit</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 small text-muted mb-0">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-dot fs-4 text-warning lh-1"></i>
                            <span><strong>Business Volume:</strong> PC is an internal accounting unit of volume, not cash or legal tender.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-dot fs-4 text-warning lh-1"></i>
                            <span><strong>No Automatic 1:1 Parity:</strong> 500 PC does not automatically equal &#8377;500. Payout conversions are determined strictly by approved incentive formula rules.</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-dot fs-4 text-warning lh-1"></i>
                            <span><strong>Formulation-Specific:</strong> Different products carry different PC percentages based on botanical manufacturing cost and approved margins.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 3: Activation Packages --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border); border-bottom: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Product-Based Activation</div>
            <h2 class="tfc-section-title">Activation Packages</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Registration is completely free. Activation is achieved through the genuine purchase of eligible VANRITI wellness products.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 text-center p-4">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Starter Level</div>
                    <h4 class="fw-bold text-dark mb-3">Quarter Sale</h4>
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <span class="display-6 fw-bold text-dark">&#8377;2,000</span>
                        <div class="small text-muted mt-1">Product Purchase Value</div>
                    </div>
                    <ul class="list-unstyled text-muted small mb-4 d-flex flex-column gap-2 text-start">
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Eligible VANRITI Herbal Products</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Free Account Activation</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Daily Cap: <strong>&#8377;2,000</strong></span>
                        </li>
                    </ul>
                    <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-outline-dark mt-auto">Choose Quarter</a>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 text-center p-4 border-warning shadow-sm position-relative">
                    <span class="badge bg-warning text-dark position-absolute top-0 start-50 translate-middle px-3 py-1 fw-bold">Popular Choice</span>
                    <div class="text-warning small text-uppercase fw-bold mb-1">Intermediate Level</div>
                    <h4 class="fw-bold text-dark mb-3">Half Sale</h4>
                    <div class="p-3 bg-warning bg-opacity-10 rounded-3 mb-3">
                        <span class="display-6 fw-bold text-dark">&#8377;5,000</span>
                        <div class="small text-muted mt-1">Product Purchase Value</div>
                    </div>
                    <ul class="list-unstyled text-muted small mb-4 d-flex flex-column gap-2 text-start">
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Comprehensive Wellness Range</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Free Account Activation</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Daily Cap: <strong>&#8377;5,000</strong></span>
                        </li>
                    </ul>
                    <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-tfc-primary mt-auto">Choose Half Sale</a>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 text-center p-4">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Advanced Level</div>
                    <h4 class="fw-bold text-dark mb-3">Full Sale</h4>
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <span class="display-6 fw-bold text-dark">&#8377;10,000</span>
                        <div class="small text-muted mt-1">Product Purchase Value</div>
                    </div>
                    <ul class="list-unstyled text-muted small mb-4 d-flex flex-column gap-2 text-start">
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Complete Botanical Collection</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Free Account Activation</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg text-success"></i>
                            <span>Daily Cap: <strong>&#8377;10,000</strong></span>
                        </li>
                    </ul>
                    <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-outline-dark mt-auto">Choose Full Sale</a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check text-success me-1"></i>Product purchases are not merely an entry fee. Members receive 100% genuine products with complete consumer protections.
            </small>
        </div>
    </div>
</section>

{{-- Section 4: Direct Sponsor Incentive (10%) --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Direct Recommendation</div>
                <h2 class="tfc-section-title">Direct Sponsor Incentive</h2>
                <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill bg-warning bg-opacity-20 text-dark fw-bold mb-3">
                    <i class="bi bi-award-fill text-warning"></i> Proposed Incentive Rate: 10%
                </div>
                <p class="text-muted mb-4">
                    Direct sellers who introduce new prospective members to TFC can receive a 10% Direct Sponsor Incentive on qualifying product purchases made by their directly sponsored members, calculated per approved volume rules.
                </p>

                {{-- Visual Flow Diagram --}}
                <div class="p-4 bg-light rounded-3 border">
                    <h6 class="fw-bold mb-3 text-dark">Sponsor Incentive Flow</h6>
                    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-center">
                        <div class="p-3 bg-white rounded-3 shadow-sm border w-100">
                            <i class="bi bi-person-fill text-primary fs-3"></i>
                            <div class="fw-bold mt-1 small">You (Sponsor)</div>
                        </div>
                        <i class="bi bi-arrow-right fs-4 text-warning d-none d-sm-block"></i>
                        <i class="bi bi-arrow-down fs-4 text-warning d-block d-sm-none"></i>
                        <div class="p-3 bg-white rounded-3 shadow-sm border w-100">
                            <i class="bi bi-person-plus-fill text-success fs-3"></i>
                            <div class="fw-bold mt-1 small">Direct Member</div>
                        </div>
                        <i class="bi bi-arrow-right fs-4 text-warning d-none d-sm-block"></i>
                        <i class="bi bi-arrow-down fs-4 text-warning d-block d-sm-none"></i>
                        <div class="p-3 bg-white rounded-3 shadow-sm border w-100">
                            <i class="bi bi-box2-heart-fill text-warning fs-3"></i>
                            <div class="fw-bold mt-1 small">Product Sale</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 border bg-light shadow-sm">
                    <h5 class="fw-bold text-dark mb-3">Operational Clarification</h5>
                    <p class="text-muted small mb-3">
                        Incentives are awarded strictly upon verified product transactions. No payment is ever earned solely for registering a member without qualifying retail purchases.
                    </p>
                    <div class="p-3 bg-white rounded-3 border small text-muted">
                        <strong class="text-dark d-block mb-1">Approved Calculation Base:</strong>
                        The 10% rate applies to the eligible calculation base (such as qualifying Product Credit) as established in the approved plan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 5: Binary Matching Incentive (15% with 60% Cap) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Two-Team Structure</div>
            <h2 class="tfc-section-title">Binary Matching Incentive</h2>
            <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill bg-warning bg-opacity-20 text-dark fw-bold mb-3">
                <i class="bi bi-diagram-2-fill text-warning"></i> Proposed Matching Rate: 15% (Up to 60% Maximum Base Limit)
            </div>
            <p class="text-muted mx-auto" style="max-width: 680px;">
                TFC utilizes an Orange and Blue two-team structure to measure organizational product distribution and reward balanced team sales.
            </p>
        </div>

        {{-- Professional Binary Diagram --}}
        <div class="p-4 p-md-5 bg-white rounded-4 border shadow-sm mb-5 text-center">
            <h5 class="fw-bold text-dark mb-4">Organizational Binary Team Layout</h5>
            <div class="tfc-binary-diagram mx-auto" style="max-width: 650px;">
                {{-- Member at Top --}}
                <div class="d-inline-block p-3 px-4 rounded-3 text-white shadow-sm mb-4" style="background: var(--tfc-navy);">
                    <i class="bi bi-person-circle fs-3 d-block mb-1"></i>
                    <strong>Independent Direct Seller</strong>
                    <div class="small text-warning">Center Account</div>
                </div>

                {{-- Connector Line --}}
                <div class="position-relative mb-4">
                    <div class="border-top border-2 border-warning w-75 mx-auto"></div>
                </div>

                {{-- Teams Row --}}
                <div class="row g-4 text-center">
                    {{-- Orange Team (Left) --}}
                    <div class="col-6">
                        <div class="p-4 rounded-3 border shadow-sm" style="background-color: #FFF7ED; border-color: #FDBA74 !important;">
                            <span class="badge px-3 py-1 mb-2 text-white" style="background-color: #EA580C;">Orange Team (Left)</span>
                            <div class="fw-bold text-dark mb-1">Eligible Volume Accumulation</div>
                            <small class="text-muted d-block mb-3">Sales generated across left team organizations</small>
                            <div class="p-2 bg-white rounded border small text-dark fw-semibold">
                                <i class="bi bi-stack me-1 text-warning"></i>Left Group PC
                            </div>
                        </div>
                    </div>

                    {{-- Blue Team (Right) --}}
                    <div class="col-6">
                        <div class="p-4 rounded-3 border shadow-sm" style="background-color: #EFF6FF; border-color: #93C5FD !important;">
                            <span class="badge px-3 py-1 mb-2 text-white" style="background-color: #2563EB;">Blue Team (Right)</span>
                            <div class="fw-bold text-dark mb-1">Eligible Volume Accumulation</div>
                            <small class="text-muted d-block mb-3">Sales generated across right team organizations</small>
                            <div class="p-2 bg-white rounded border small text-dark fw-semibold">
                                <i class="bi bi-stack me-1 text-primary"></i>Right Group PC
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Matching Explanation Box --}}
            <div class="alert alert-light border mt-4 p-3 text-start mx-auto" style="max-width: 720px; font-size: 0.82rem; color: #4A5568;">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong>Matching Concept:</strong> Eligible business volumes matching across the Orange and Blue groups are processed at the proposed 15% rate, subject to the applicable maximum matching payout limit (stated up to 60% of an eligible base) and daily package caps.
            </div>
        </div>
    </div>
</section>

{{-- Section 6, 7, 8: Booster, Prime Booster & Rewards --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Performance Accelerators</div>
            <h2 class="tfc-section-title">Booster, Prime Booster &amp; Rewards</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Additional incentive components designed to recognize consistent business building and verified sales volume milestones.
            </p>
        </div>

        <div class="row g-4">
            {{-- Booster Incentive (2%) --}}
            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 p-4 border text-center">
                    <div class="p-3 rounded-circle bg-warning bg-opacity-20 text-warning mx-auto fs-3 mb-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-1 mx-auto mb-2 fw-bold">Proposed Rate: 2%</span>
                    <h5 class="fw-bold text-dark mb-2">Booster Incentive</h5>
                    <p class="text-muted small mb-0">
                        An additional 2% incentive rate associated with qualifying business activity and performance tiers under the approved direct-selling plan.
                    </p>
                </div>
            </div>

            {{-- Prime Booster Incentive (3%) --}}
            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 p-4 border text-center">
                    <div class="p-3 rounded-circle bg-primary bg-opacity-20 text-primary mx-auto fs-3 mb-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <span class="badge bg-primary text-white px-3 py-1 mx-auto mb-2 fw-bold">Proposed Rate: 3%</span>
                    <h5 class="fw-bold text-dark mb-2">Prime Booster Incentive</h5>
                    <p class="text-muted small mb-0">
                        A specialized 3% performance incentive allocated for higher-tier qualifying business volume milestones per approved plan terms.
                    </p>
                </div>
            </div>

            {{-- Rewards Program (10%) --}}
            <div class="col-12 col-md-4">
                <div class="card tfc-card h-100 p-4 border text-center">
                    <div class="p-3 rounded-circle bg-success bg-opacity-20 text-success mx-auto fs-3 mb-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <span class="badge bg-success text-white px-3 py-1 mx-auto mb-2 fw-bold">Proposed Allocation: 10%</span>
                    <h5 class="fw-bold text-dark mb-2">Rewards Program</h5>
                    <p class="text-muted small mb-0">
                        A dedicated 10% rewards component intended to recognize sustained leadership, retail consistency, and organization compliance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 9 & 10: Daily Earning Caps & Carry-Forward --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border); border-bottom: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="row g-5 align-items-center">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Financial Safeguards</div>
                <h2 class="tfc-section-title">Daily Earning Caps</h2>
                <p class="text-muted mb-4">
                    Daily earning caps are statutory safety thresholds established to maintain long-term corporate sustainability and protect distributor margins.
                </p>

                <div class="table-responsive rounded-3 border bg-white shadow-sm mb-3">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-dark" style="background-color: var(--tfc-navy);">
                            <tr>
                                <th class="py-3 text-start ps-4">Activation Package</th>
                                <th class="py-3 text-end pe-4">Proposed Daily Cap</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">Quarter Sale</td>
                                <td class="text-end pe-4 fw-bold text-primary">&#8377;2,000 / Day</td>
                            </tr>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">Half Sale</td>
                                <td class="text-end pe-4 fw-bold text-primary">&#8377;5,000 / Day</td>
                            </tr>
                            <tr>
                                <td class="text-start ps-4 fw-semibold text-dark">Full Sale</td>
                                <td class="text-end pe-4 fw-bold text-primary">&#8377;10,000 / Day</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted d-block">
                    <i class="bi bi-info-circle me-1"></i>Caps represent maximum permissible daily earnings, not guaranteed minimums.
                </small>
            </div>

            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 border bg-white shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-arrow-repeat text-warning me-2"></i>Carry-Forward vs. Cap Expiry</h5>
                    <div class="d-flex flex-column gap-3 small text-muted">
                        <div class="p-3 bg-light rounded-3">
                            <strong class="text-dark d-block mb-1">Unmatched Business Volume:</strong>
                            Eligible business volume that has not yet matched in a daily cycle may carry forward to the next cycle under applicable binary terms.
                        </div>
                        <div class="p-3 bg-light rounded-3">
                            <strong class="text-dark d-block mb-1">Earnings Above Daily Cap:</strong>
                            Earnings exceeding the applicable daily cap for your package tier are intended to expire per standard direct-selling policy.
                        </div>
                        <div class="p-2 text-warning fw-semibold">
                            Volume carry-forward is strictly distinct from carrying forward cash payouts.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 11 & 12: Daily Settlement & Payout Deductions --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="row g-5">
            {{-- Section 11: Daily Settlement Process --}}
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Operational Routine</div>
                <h3 class="fw-bold text-dark mb-3">Daily Settlement Process</h3>
                <p class="text-muted small mb-4">
                    TFC follows a systematic end-of-day settlement cycle to review eligible sales and reconcile incentive ledgers transparently.
                </p>
                <div class="d-flex flex-column gap-3 small text-muted">
                    <div class="d-flex align-items-start gap-3">
                        <span class="badge bg-dark rounded-circle p-2">1</span>
                        <div>
                            <strong class="text-dark d-block">Daily Cutoff at 11:50 PM IST</strong>
                            All orders placed, paid, and verified up to 11:50 PM IST are batched for daily calculation.
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="badge bg-dark rounded-circle p-2">2</span>
                        <div>
                            <strong class="text-dark d-block">Ledger Reconciliation</strong>
                            Incentives are computed and recorded in the distributor's pending ledger after cutoff review.
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="badge bg-dark rounded-circle p-2">3</span>
                        <div>
                            <strong class="text-dark d-block">Payout Disbursement</strong>
                            Eligible payouts are intended for bank transfer the following day, subject to banking clearing and KYC verification.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 12: Payout and Deductions --}}
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Statutory Deductions</div>
                <h3 class="fw-bold text-dark mb-3">Payout &amp; Applicable Deductions</h3>
                <p class="text-muted small mb-4">
                    In compliance with Indian taxation laws and banking infrastructure costs, all distributor disbursements reflect transparent deductions.
                </p>
                <div class="p-4 bg-light rounded-3 border">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-white rounded border text-center">
                                <span class="fs-4 fw-bold text-dark">2%</span>
                                <div class="small text-muted mt-1">Processing Fee</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded border text-center">
                                <span class="fs-4 fw-bold text-dark">2%</span>
                                <div class="small text-muted mt-1">Proposed TDS</div>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted d-block" style="font-size: 0.78rem;">
                        <i class="bi bi-info-circle me-1"></i>Actual tax deductions (TDS) are administered per Section 194H / relevant sections of the Indian Income Tax Act. Every distributor receives a clear digital payout statement showing gross incentive and net disbursement.
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 13: Important Business Disclosures --}}
<section class="py-5" style="background-color: #FEF2F2; border-top: 1px solid #FCA5A5; border-bottom: 1px solid #FCA5A5;">
    <div class="container py-3">
        <div class="p-4 rounded-4 bg-white border border-danger shadow-sm">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-shield-exclamation text-danger fs-1 flex-shrink-0"></i>
                <div>
                    <h4 class="fw-bold text-danger mb-2">Important Direct Selling &amp; Earnings Disclosures</h4>
                    <ul class="mb-0 small text-muted d-flex flex-column gap-2" style="line-height: 1.6;">
                        <li><strong>No Guaranteed Earnings:</strong> TFC does not promise or guarantee any income, return on investment, or fixed financial reward.</li>
                        <li><strong>Product-Sales Grounded:</strong> Earnings are generated strictly from the sale of genuine consumer wellness goods. Recruitment alone creates zero entitlement to compensation.</li>
                        <li><strong>Product Credit (PC) Is Not Currency:</strong> Product Credit is an internal volume unit used solely for plan calculations.</li>
                        <li><strong>Return &amp; Cancellation Adjustments:</strong> Product returns, customer refunds, or order cancellations during cooling-off periods will adjust corresponding volume credits.</li>
                        <li><strong>Voluntary Participation:</strong> Direct sellers operate as independent contractors, not employees. Registration is 100% free with zero mandatory entry fees.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 14: Frequently Asked Questions (15 Items) --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Frequently Asked Questions</div>
            <h2 class="tfc-section-title">Incentive Plan &amp; Payout FAQs</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Detailed answers regarding the TFC compensation model, Product Credits, teams, and settlement.
            </p>
        </div>

        <div class="accordion tfc-accordion mx-auto" id="incentiveFaqAccordion" style="max-width: 880px;">
            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH1">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC1" aria-expanded="true" aria-controls="incC1">
                        1. What is Product Credit (PC)?
                    </button>
                </h2>
                <div id="incC1" class="accordion-collapse collapse show" aria-labelledby="incH1" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Product Credit (PC) is an internal business-volume measurement assigned to eligible VANRITI products to calculate organizational sales and incentive allocations.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH2">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC2" aria-expanded="false" aria-controls="incC2">
                        2. Is PC the same as cash?
                    </button>
                </h2>
                <div id="incC2" class="accordion-collapse collapse" aria-labelledby="incH2" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        No. PC is not currency, legal tender, or cash. It is used strictly for plan calculations. Payouts are calculated based on approved business plan formulas.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH3">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC3" aria-expanded="false" aria-controls="incC3">
                        3. What are the activation packages?
                    </button>
                </h2>
                <div id="incC3" class="accordion-collapse collapse" aria-labelledby="incH3" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Activation is based on real product purchases: Quarter Sale (&#8377;2,000), Half Sale (&#8377;5,000), and Full Sale (&#8377;10,000). Members receive full-value genuine wellness products.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH4">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC4" aria-expanded="false" aria-controls="incC4">
                        4. What is the Direct Sponsor Incentive?
                    </button>
                </h2>
                <div id="incC4" class="accordion-collapse collapse" aria-labelledby="incH4" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        A proposed 10% incentive associated with qualifying product purchases made by members whom you have directly introduced to the platform.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH5">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC5" aria-expanded="false" aria-controls="incC5">
                        5. How does Orange and Blue binary business work?
                    </button>
                </h2>
                <div id="incC5" class="accordion-collapse collapse" aria-labelledby="incH5" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Your direct-selling organization develops two sales teams: Orange (Left) and Blue (Right). Eligible product purchases generated across both groups accumulate towards matching calculations.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH6">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC6" aria-expanded="false" aria-controls="incC6">
                        6. What is the Binary Matching Incentive?
                    </button>
                </h2>
                <div id="incC6" class="accordion-collapse collapse" aria-labelledby="incH6" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        A proposed 15% matching incentive applied to matching business volume across Orange and Blue teams, subject to the stated 60% maximum base limit.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH7">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC7" aria-expanded="false" aria-controls="incC7">
                        7. What are Booster and Prime Booster?
                    </button>
                </h2>
                <div id="incC7" class="accordion-collapse collapse" aria-labelledby="incH7" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Booster (proposed 2%) and Prime Booster (proposed 3%) are additional performance incentives designated for qualifying sales volumes under approved plan conditions.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH8">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC8" aria-expanded="false" aria-controls="incC8">
                        8. What is the Rewards component?
                    </button>
                </h2>
                <div id="incC8" class="accordion-collapse collapse" aria-labelledby="incH8" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        A proposed 10% allocation intended to recognize verified leadership and consistent sales milestones according to officially approved business terms.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH9">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC9" aria-expanded="false" aria-controls="incC9">
                        9. What is a daily earning cap?
                    </button>
                </h2>
                <div id="incC9" class="accordion-collapse collapse" aria-labelledby="incH9" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        A daily earning cap is the maximum incentive an account can earn in a single settlement cycle: &#8377;2,000 (Quarter), &#8377;5,000 (Half), or &#8377;10,000 (Full Sale).
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH10">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC10" aria-expanded="false" aria-controls="incC10">
                        10. Does unmatched business carry forward?
                    </button>
                </h2>
                <div id="incC10" class="accordion-collapse collapse" aria-labelledby="incH10" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Yes, unmatched eligible sales volume may carry forward into the next daily cycle under standard binary terms.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH11">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC11" aria-expanded="false" aria-controls="incC11">
                        11. What happens to earnings above the daily cap?
                    </button>
                </h2>
                <div id="incC11" class="accordion-collapse collapse" aria-labelledby="incH11" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Incentive earnings generated in excess of the applicable daily package cap are scheduled to expire under the company's financial risk management rules.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH12">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC12" aria-expanded="false" aria-controls="incC12">
                        12. When are incentives settled?
                    </button>
                </h2>
                <div id="incC12" class="accordion-collapse collapse" aria-labelledby="incH12" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Settlement calculation occurs daily after the 11:50 PM IST cutoff for all completed, paid, and verified transactions.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH13">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC13" aria-expanded="false" aria-controls="incC13">
                        13. How are payouts processed?
                    </button>
                </h2>
                <div id="incC13" class="accordion-collapse collapse" aria-labelledby="incH13" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Disbursements are made directly to the distributor's verified bank account via NEFT/IMPS following daily settlement and deduction of applicable processing fees and TDS.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH14">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC14" aria-expanded="false" aria-controls="incC14">
                        14. Are earnings guaranteed?
                    </button>
                </h2>
                <div id="incC14" class="accordion-collapse collapse" aria-labelledby="incH14" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        <strong>No.</strong> Direct selling involves genuine commercial effort. Earnings depend entirely on actual consumer demand, sales volume, and approved eligibility rules.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="incH15">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#incC15" aria-expanded="false" aria-controls="incC15">
                        15. Where can I read the official terms?
                    </button>
                </h2>
                <div id="incC15" class="accordion-collapse collapse" aria-labelledby="incH15" data-bs-parent="#incentiveFaqAccordion">
                    <div class="accordion-body text-muted small">
                        Official terms are published in our <a href="{{ tfc_route('policy', 'statutory-disclosures') }}" class="text-warning fw-semibold">Statutory Disclosures</a> and <a href="{{ tfc_route('policy', 'direct-seller-agreement') }}" class="text-warning fw-semibold">Direct Seller Agreement</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 15: Final CTA --}}
<section class="py-5" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
    <div class="container py-4 text-center">
        <h2 class="display-6 fw-bold mb-3">Understand the Plan. Explore the Products. Build Responsibly.</h2>
        <p class="text-white-50 mx-auto mb-4" style="max-width: 620px;">
            A sustainable micro-entrepreneurship opportunity founded on genuine Ayurvedic wellness products and transparent commercial principles.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-primary btn-lg">
                <i class="bi bi-grid-fill me-2"></i>Discover VANRITI Products
            </a>
            <a href="{{ tfc_route('direct-selling') }}" class="btn btn-outline-light btn-lg">
                <i class="bi bi-book me-2"></i>Learn About Direct Selling
            </a>
            <a href="{{ tfc_route('contact') }}" class="btn btn-outline-light btn-lg">
                <i class="bi bi-envelope me-2"></i>Contact TFC
            </a>
        </div>
    </div>
</section>
@endsection
