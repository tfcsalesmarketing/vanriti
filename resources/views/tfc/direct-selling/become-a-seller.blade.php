@extends('tfc.layouts.app')

@section('title', 'Become a Direct Seller | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Zero Entry Fee</span>
        <h1 class="display-5 fw-bold text-white mb-2">Become a Direct Seller</h1>
        <p class="text-white-50 lead mb-0">Join a thriving direct-selling wellness network powered by TFC</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 800px;">
        <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4">
            <div class="mb-4 text-center">
                <h4 class="fw-bold text-dark mb-1">Prospective Direct Seller Application</h4>
                <p class="text-muted small">Please submit your details. Our compliance and onboarding team will review your application.</p>
            </div>

            {{-- Statutory Declaration Reminder --}}
            <div class="alert alert-warning border-warning small mb-4">
                <i class="bi bi-info-circle-fill me-1"></i>
                <strong>Statutory Notice:</strong> Registration is free. TFC does not require or accept any joining fee, recruitment fee, or mandatory purchase of starter packages.
            </div>

            <form method="POST" action="{{ tfc_route('become-a-seller.submit') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Full Legal Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="As per PAN / Aadhaar">
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="your.name@example.com">
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Active Mobile Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" required placeholder="+91 98765 43210">
                        @error('phone') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}" required placeholder="City">
                        @error('city') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" class="form-control" value="{{ old('state') }}" required placeholder="State">
                        @error('state') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Experience / Background (Optional)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Tell us about your background or why you are interested in wellness direct selling...">{{ old('message') }}</textarea>
                        @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeCompliance" required>
                            <label class="form-check-label small text-muted" for="agreeCompliance">
                                I understand that TFC operates strictly under the Consumer Protection (Direct Selling) Rules, 2021, and does not charge any registration fee. I agree to operate ethically and truthfully.
                            </label>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tfc-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-send-fill me-2"></i>Submit Application
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
