@extends('tfc.layouts.app')

@section('title', 'Contact Us | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">GET IN TOUCH</span>
        <h1 class="display-5 fw-bold text-dark mb-2">Contact TFC Sales &amp; Marketing</h1>
        <p class="text-muted lead mb-0">We are here to support our independent direct sellers and valued customers.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            {{-- Contact Information --}}
            <div class="col-12 col-lg-5">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <h4 class="fw-bold text-warning mb-4">Corporate Office</h4>
                    
                    <div class="mb-4 d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt-fill text-warning fs-4 mt-1"></i>
                        <div>
                            <div class="fw-bold text-white">Office Address</div>
                            <small class="text-white-50">{{ tfc_setting('office_address', 'TFC Sales & Marketing Corporate Office, India') }}</small>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-start gap-3">
                        <i class="bi bi-envelope-fill text-warning fs-4 mt-1"></i>
                        <div>
                            <div class="fw-bold text-white">Email Address</div>
                            <small class="text-white-50">{{ tfc_setting('contact_email', 'contact@tfcsalesmarketing.com') }}</small>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-start gap-3">
                        <i class="bi bi-telephone-fill text-warning fs-4 mt-1"></i>
                        <div>
                            <div class="fw-bold text-white">Telephone Support</div>
                            <small class="text-white-50">{{ tfc_setting('support_phone', '+91 98765 43210') }}</small>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 border-top border-white border-opacity-10">
                        <div class="fw-semibold text-warning mb-1"><i class="bi bi-shield-check me-1"></i>Consumer Grievances</div>
                        <p class="text-white-50 small mb-2">For formal statutory complaints under Rule 7, Direct Selling Rules 2021:</p>
                        <a href="{{ tfc_route('grievance') }}" class="btn btn-sm btn-outline-warning">
                            Visit Grievance Portal
                        </a>
                    </div>
                </div>
            </div>

            {{-- Inquiry Form --}}
            <div class="col-12 col-lg-7">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                    <h4 class="fw-bold text-dark mb-4">Send a Message</h4>

                    <form method="POST" action="{{ tfc_route('contact.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Your Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Your full name">
                                @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Your Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="name@example.com">
                                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="+91 98765 43210">
                                @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Subject</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="Subject of inquiry">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Message <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="5" required placeholder="How can we assist you?">{{ old('message') }}</textarea>
                                @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-tfc-primary btn-lg w-100 fw-bold">
                                    <i class="bi bi-send-fill me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
