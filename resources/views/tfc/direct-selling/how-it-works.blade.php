@extends('tfc.layouts.app')

@section('title', 'How Direct Selling Works | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">The Business Process</span>
        <h1 class="display-5 fw-bold text-white mb-2">How It Works</h1>
        <p class="text-white-50 lead mb-0">Transparent &bull; Compliant &bull; Rewarding</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 960px;">
        <div class="row g-5">
            {{-- Stage 1 --}}
            <div class="col-12 d-flex gap-4 align-items-start">
                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.4rem; flex-shrink: 0;">1</div>
                <div>
                    <h4 class="fw-bold text-dark mb-2">Apply Online with Zero Fees</h4>
                    <p class="text-muted">
                        Complete our prospective direct seller application form. In strict compliance with Indian direct selling rules, you pay zero enrollment fees, zero joining charges, and are never forced to purchase starter kits.
                    </p>
                </div>
            </div>

            {{-- Stage 2 --}}
            <div class="col-12 d-flex gap-4 align-items-start">
                <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.4rem; flex-shrink: 0;">2</div>
                <div>
                    <h4 class="fw-bold text-dark mb-2">Complete Verification &amp; Onboarding</h4>
                    <p class="text-muted">
                        Our administrative compliance team verifies your identity and issues an official Direct Seller Agreement outlining your rights, statutory code of conduct, and cooling-off guarantees.
                    </p>
                </div>
            </div>

            {{-- Stage 3 --}}
            <div class="col-12 d-flex gap-4 align-items-start">
                <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.4rem; flex-shrink: 0;">3</div>
                <div>
                    <h4 class="fw-bold text-dark mb-2">Recommend Genuine Wellness Formulations</h4>
                    <p class="text-muted">
                        Introduce friends, family, and community members to certified VANRITI herbal wellness formulations. Explain genuine ingredients, certified lab test results, and truthful usage directions.
                    </p>
                </div>
            </div>

            {{-- Stage 4 --}}
            <div class="col-12 d-flex gap-4 align-items-start">
                <div class="rounded-circle bg-dark text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px; font-size: 1.4rem; flex-shrink: 0;">4</div>
                <div>
                    <h4 class="fw-bold text-dark mb-2">Earn Fair Retail Margins</h4>
                    <p class="text-muted">
                        Earn lawful margins strictly derived from retail sales to consumers. Customer orders can be fulfilled seamlessly with direct home delivery.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 pt-4 border-top">
            <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-tfc-primary btn-lg">
                Start Your Application Today <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
@endsection
