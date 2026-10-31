@extends('tfc.layouts.app')

@section('title', 'Frequently Asked Questions | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">HELP &amp; SUPPORT</span>
        <h1 class="display-5 fw-bold text-dark mb-2">Frequently Asked Questions</h1>
        <p class="text-muted lead mb-0">Clear answers about direct selling with TFC, products, and distributor opportunities.</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 860px;">
        <div class="accordion" id="tfcFaqAccordion">
            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1">
                        1. Is there any joining fee to become a direct seller with TFC?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse show" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        <strong>No. Absolutely zero.</strong> In strict compliance with Rule 5(1)(b) of the Consumer Protection (Direct Selling) Rules, 2021, TFC Sales &amp; Marketing charges no entry fee, registration fee, compulsory starter kit, or training fee from any prospective direct seller.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2">
                        2. How do direct sellers earn income?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        Direct sellers earn retail margins and team matching incentives strictly derived from the sale and consumption of genuine wellness and personal care products. Earnings are tied directly to actual product sales volume and leadership mentoring.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3">
                        3. What is the return and refund policy?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        Customers and sellers enjoy an official satisfaction guarantee and cooling-off period during which unused products in original packaging can be returned for a refund in accordance with company policy.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading4">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4">
                        4. How do I purchase products as a retail consumer?
                    </button>
                </h2>
                <div id="faqCollapse4" class="accordion-collapse collapse" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        You can explore our product catalogue directly on this website and order online for doorstep delivery across India, or connect with an authorized independent TFC direct seller in your region.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading5">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5">
                        5. How do I lodge a consumer grievance?
                    </button>
                </h2>
                <div id="faqCollapse5" class="accordion-collapse collapse" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        You can lodge a complaint online through our dedicated <a href="{{ tfc_route('grievance') }}" class="fw-semibold text-primary">Grievance Portal</a>. Your ticket will be formally acknowledged with a unique tracking number within 48 hours and resolved within 30 days pursuant to Rule 7 of the Direct Selling Rules, 2021.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
