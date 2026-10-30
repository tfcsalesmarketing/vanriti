@extends('tfc.layouts.app')

@section('title', 'Frequently Asked Questions | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Support &amp; Answers</span>
        <h1 class="display-5 fw-bold text-white mb-2">Frequently Asked Questions</h1>
        <p class="text-white-50 lead mb-0">Clear answers about direct selling with TFC, consumer rights, and products.</p>
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
                        Direct sellers earn lawful retail commissions strictly derived from the sale of genuine VANRITI consumer wellness products to real retail customers. We strictly prohibit pyramid schemes, head-hunting bounties, or recruitment-based incentives.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded shadow-sm">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3">
                        3. What is the cooling-off period policy?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" data-bs-parent="#tfcFaqAccordion">
                    <div class="accordion-body text-muted">
                        In accordance with direct selling rules, customers and sellers enjoy a statutory cooling-off period during which unused products in marketable condition can be returned for a full or prescribed refund. (Specific commercial buy-back terms are detailed in the official policy section).
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
                        You can explore products on this website and purchase them securely through the official <a href="{{ config('tfc.vanriti_store_url', 'https://vanriti.com') }}" target="_blank" class="fw-semibold text-primary">VANRITI Store</a>, or order directly through an authorized independent TFC direct seller.
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
