@extends('admin.layouts.app')

@section('title', 'TFC Sales & Marketing Settings & Disclosures')

@section('content')
<div class="mb-4">
    <h5 class="fw-bold mb-1">TFC Website Settings &amp; Statutory Disclosures</h5>
    <p class="text-muted small mb-0">Configure corporate identity, direct selling compliance declarations, and statutory officer designations.</p>
</div>

<form method="POST" action="{{ route('admin.tfc.settings.update') }}">
    @csrf

    {{-- Tabs --}}
    <ul class="nav nav-pills mb-4" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">Corporate Profile</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold text-danger" id="statutory-tab" data-bs-toggle="pill" data-bs-target="#tab-statutory" type="button" role="tab">
                <i class="bi bi-shield-check me-1"></i>Statutory Direct Selling Disclosures
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="contact-tab" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button" role="tab">Contact &amp; Support</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="plan-tab" data-bs-toggle="pill" data-bs-target="#tab-plan" type="button" role="tab">Direct Selling &amp; Incentive Plan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="social-tab" data-bs-toggle="pill" data-bs-target="#tab-social" type="button" role="tab">Social Media</button>
        </li>
    </ul>

    <div class="tab-content" id="settingsTabContent">
        {{-- Tab 1: Corporate Profile --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Company Branding</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Corporate Tagline</label>
                            <input type="text" name="company_tagline" class="form-control" value="{{ tfc_setting('company_tagline', 'CONNECTING PRODUCTS. CREATING POSSIBILITIES.') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Logo Image URL</label>
                            <input type="url" name="logo_url" class="form-control" value="{{ tfc_setting('logo_url') }}">
                            <small class="text-muted">Direct AWS S3 URL or relative asset path.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 2: Statutory Disclosures & Direct Selling Rules 2021/2023 --}}
        <div class="tab-pane fade" id="tab-statutory" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-exclamation me-1"></i>Mandatory Direct-Selling Disclosures</h6>
                        <small class="text-muted">Consumer Protection (Direct Selling) Rules, 2021 (G.S.R. 889(E)) &amp; 2023 Amendment</small>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2">
                        Status: {{ tfc_setting('statutory_status', 'DRAFT_PENDING_CONFIRMATION') }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-info mb-4">
                        <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i>Legal Safeguard Directive:</div>
                        <div class="small">
                            Per strict instructions, placeholder officer details must <strong>never</strong> be published live. Leave officer contact fields blank until formal administrative appointments and corporate incorporation documents are finalized and verified.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Corporate Identity Number (CIN) or LLPIN</label>
                            <input type="text" name="cin_or_llpin" class="form-control" value="{{ tfc_setting('cin_or_llpin') }}" placeholder="e.g., U... / AAA-...">
                            <small class="text-muted">Leave blank or placeholder in draft until officially registered.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ tfc_setting('pan_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">GSTIN</label>
                            <input type="text" name="gstin_number" class="form-control" value="{{ tfc_setting('gstin_number') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Official Registered Office Address</label>
                            <textarea name="registered_office" class="form-control" rows="2">{{ tfc_setting('registered_office') }}</textarea>
                        </div>

                        <div class="col-12"><hr class="my-3"></div>

                        {{-- Grievance Redressal Officer (Rule 7) --}}
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-1">Grievance Redressal Officer (Rule 7, Direct Selling Rules 2021)</h6>
                            <p class="text-muted small">Must acknowledge consumer complaints within 48 hours and resolve within 30 days.</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Grievance Officer Name</label>
                            <input type="text" name="grievance_officer_name" class="form-control" value="{{ tfc_setting('grievance_officer_name') }}" placeholder="Pending Corporate Appointment">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Grievance Officer Email</label>
                            <input type="email" name="grievance_officer_email" class="form-control" value="{{ tfc_setting('grievance_officer_email') }}" placeholder="grievance@tfcsalesmarketing.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Grievance Officer Phone</label>
                            <input type="text" name="grievance_officer_phone" class="form-control" value="{{ tfc_setting('grievance_officer_phone') }}" placeholder="+91 ...">
                        </div>

                        <div class="col-12"><hr class="my-3"></div>

                        {{-- Nodal Officer (Rule 5(6)) --}}
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-1">Nodal Officer for Law Enforcement (Rule 5(6), Direct Selling Rules 2021)</h6>
                            <p class="text-muted small">Designated for 24x7 coordination with government authorities and law enforcement.</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nodal Officer Name</label>
                            <input type="text" name="nodal_officer_name" class="form-control" value="{{ tfc_setting('nodal_officer_name') }}" placeholder="Pending Corporate Appointment">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nodal Officer Email</label>
                            <input type="email" name="nodal_officer_email" class="form-control" value="{{ tfc_setting('nodal_officer_email') }}" placeholder="nodal@tfcsalesmarketing.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nodal Officer Phone</label>
                            <input type="text" name="nodal_officer_phone" class="form-control" value="{{ tfc_setting('nodal_officer_phone') }}" placeholder="+91 ...">
                        </div>

                        <div class="col-12"><hr class="my-3"></div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Mandatory Direct-Selling Statutory Declaration</label>
                            <textarea name="statutory_notice" class="form-control" rows="3">{{ tfc_setting('statutory_notice', 'Direct selling involves sale of genuine products. TFC prohibits pyramid/money circulation schemes and does not charge any registration or recruitment fee.') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 3: Contact & Support --}}
        <div class="tab-pane fade" id="tab-contact" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Public Customer Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">General Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" value="{{ tfc_setting('contact_email', 'contact@tfcsalesmarketing.com') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Support Helpline Phone</label>
                            <input type="text" name="support_phone" class="form-control" value="{{ tfc_setting('support_phone', '+91 98765 43210') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Corporate Office Address</label>
                            <textarea name="office_address" class="form-control" rows="3">{{ tfc_setting('office_address', 'TFC Sales & Marketing Corporate Office, India') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Direct Selling & Incentive Plan Settings --}}
        <div class="tab-pane fade" id="tab-plan" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Direct Selling Page Headlines</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Page Headline Title</label>
                            <input type="text" name="direct_selling_headline" class="form-control" value="{{ tfc_setting('direct_selling_headline', 'More Than Products. A Business Built on Relationships.') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Hero Supporting Subtitle</label>
                            <input type="text" name="direct_selling_subtitle" class="form-control" value="{{ tfc_setting('direct_selling_subtitle', 'Discover a More Personal Way to Experience Wellness.') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Incentive &amp; Business Plan Settings</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Incentive Page Headline</label>
                            <input type="text" name="incentive_headline" class="form-control" value="{{ tfc_setting('incentive_headline', 'Recognising Genuine Sales. Encouraging Consistent Growth.') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Daily Settlement Cutoff</label>
                            <input type="text" name="plan_settlement_cutoff" class="form-control" value="{{ tfc_setting('plan_settlement_cutoff', '11:50 PM IST') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Direct Sponsor Rate (%)</label>
                            <input type="text" name="plan_direct_sponsor_rate" class="form-control" value="{{ tfc_setting('plan_direct_sponsor_rate', '10') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Binary Matching Rate (%)</label>
                            <input type="text" name="plan_binary_matching_rate" class="form-control" value="{{ tfc_setting('plan_binary_matching_rate', '15') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Booster Rate (%)</label>
                            <input type="text" name="plan_booster_rate" class="form-control" value="{{ tfc_setting('plan_booster_rate', '2') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Prime Booster Rate (%)</label>
                            <input type="text" name="plan_prime_booster_rate" class="form-control" value="{{ tfc_setting('plan_prime_booster_rate', '3') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rewards Allocation (%)</label>
                            <input type="text" name="plan_rewards_rate" class="form-control" value="{{ tfc_setting('plan_rewards_rate', '10') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Processing Fee (%)</label>
                            <input type="text" name="plan_processing_fee" class="form-control" value="{{ tfc_setting('plan_processing_fee', '2') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Proposed TDS (%)</label>
                            <input type="text" name="plan_tds_fee" class="form-control" value="{{ tfc_setting('plan_tds_fee', '2') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Income &amp; Financial Disclaimer Note</label>
                            <textarea name="plan_income_disclaimer" class="form-control" rows="3">{{ tfc_setting('plan_income_disclaimer', 'Participation in direct selling does not guarantee earnings. Actual incentives, where earned, depend strictly upon qualifying business activity, customer demand, individual effort, and approved incentive rules.') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab 4: Social Media --}}
        <div class="tab-pane fade" id="tab-social" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Social Media Profiles</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Facebook URL</label>
                            <input type="url" name="facebook_url" class="form-control" value="{{ tfc_setting('facebook_url') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Instagram URL</label>
                            <input type="url" name="instagram_url" class="form-control" value="{{ tfc_setting('instagram_url') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" value="{{ tfc_setting('linkedin_url') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">YouTube Channel URL</label>
                            <input type="url" name="youtube_url" class="form-control" value="{{ tfc_setting('youtube_url') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-5">
        <button type="submit" class="btn btn-primary px-4 fw-semibold">
            <i class="bi bi-save me-1"></i>Save TFC Settings
        </button>
    </div>
</form>
@endsection
