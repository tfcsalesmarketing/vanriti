@extends('tfc.layouts.app')

@section('title', 'Consumer Grievance Redressal | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Rule 7 Statutory Redressal</span>
        <h1 class="display-5 fw-bold text-white mb-2">Consumer Grievance Portal</h1>
        <p class="text-white-50 lead mb-0">Under Consumer Protection (Direct Selling) Rules, 2021 &bull; G.S.R. 889(E)</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 900px;">
        {{-- Statutory Mandate Info Card --}}
        <div class="card border-0 shadow-sm rounded-4 mb-5 p-4" style="background-color: var(--tfc-gold-light); border: 1px solid var(--tfc-gold-border) !important;">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-shield-check text-warning fs-2 mt-1"></i>
                <div>
                    <h5 class="fw-bold text-dark mb-1">Our Statutory Commitment to Consumers</h5>
                    <p class="small text-muted mb-2">
                        Pursuant to <strong>Rule 7 of the Consumer Protection (Direct Selling) Rules, 2021</strong>, every consumer or direct-seller grievance lodged through this portal is guaranteed:
                    </p>
                    <ul class="small text-muted mb-0 ps-3">
                        <li><strong>48-Hour Acknowledgment:</strong> Immediate issuance of a unique Ticket Tracking ID within 48 hours.</li>
                        <li><strong>30-Day Resolution:</strong> Formal redressal, investigation, and written resolution within thirty (30) days of receipt.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-5">
            {{-- Lodge Grievance Form --}}
            <div class="col-12 col-lg-8">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                    <h4 class="fw-bold text-dark mb-1">Submit a Grievance Ticket</h4>
                    <p class="text-muted small mb-4">Please provide detailed information to facilitate swift investigation.</p>

                    <form method="POST" action="{{ tfc_route('grievance.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Consumer Name <span class="text-danger">*</span></label>
                                <input type="text" name="consumer_name" class="form-control" value="{{ old('consumer_name') }}" required placeholder="Your full name">
                                @error('consumer_name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="your.email@example.com">
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Phone Contact <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="+91 98765 43210">
                                @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Order / Invoice Number (Optional)</label>
                                <input type="text" name="order_number" class="form-control" value="{{ old('order_number') }}" placeholder="e.g. VAN-2026-000123">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Grievance Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="product_defect" {{ old('category') === 'product_defect' ? 'selected' : '' }}>Product Quality or Defect</option>
                                    <option value="delivery_delay" {{ old('category') === 'delivery_delay' ? 'selected' : '' }}>Delivery or Shipment Delay</option>
                                    <option value="direct_seller_conduct" {{ old('category') === 'direct_seller_conduct' ? 'selected' : '' }}>Direct Seller Conduct or Representation</option>
                                    <option value="refund_issue" {{ old('category') === 'refund_issue' ? 'selected' : '' }}>Refund or Buy-Back Request</option>
                                    <option value="other" {{ old('category', 'other') === 'other' ? 'selected' : '' }}>Other Consumer Query</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Complaint Particulars <span class="text-danger">*</span></label>
                                <textarea name="complaint_details" class="form-control" rows="5" required placeholder="Describe your grievance clearly, including dates, distributor names, or order details...">{{ old('complaint_details') }}</textarea>
                                @error('complaint_details') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-tfc-primary btn-lg w-100 fw-bold">
                                    <i class="bi bi-shield-check me-2"></i>Submit Grievance &amp; Get Ticket Number
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar: Ticket Lookup & Officer Details --}}
            <div class="col-12 col-lg-4">
                {{-- Quick Track Box --}}
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 text-center" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <i class="bi bi-search text-warning fs-1 mb-2"></i>
                    <h5 class="fw-bold text-white mb-2">Already Have a Ticket?</h5>
                    <p class="text-white-50 small mb-4">Track real-time status of your previously submitted complaint.</p>
                    <a href="{{ tfc_route('grievance.track') }}" class="btn btn-tfc-primary w-100">
                        Track Ticket Status
                    </a>
                </div>

                {{-- Statutory Officer Notice (Safeguarded per Requirement #1) --}}
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-light">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-badge text-primary me-2"></i>Grievance Officer Designation</h6>
                    @if (tfc_setting('grievance_officer_name'))
                        <div class="small text-muted mb-1"><strong>Officer:</strong> {{ tfc_setting('grievance_officer_name') }}</div>
                        <div class="small text-muted mb-1"><strong>Email:</strong> {{ tfc_setting('grievance_officer_email') }}</div>
                        <div class="small text-muted"><strong>Phone:</strong> {{ tfc_setting('grievance_officer_phone') }}</div>
                    @else
                        <div class="alert alert-warning border-warning p-2 small mb-0">
                            <strong>Notice:</strong> Officer appointment notification is currently pending formal regulatory filing. Complaints are processed directly by the central compliance desk.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
