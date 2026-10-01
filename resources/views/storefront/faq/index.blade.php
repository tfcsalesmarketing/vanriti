@extends('storefront.layouts.app')

@section('title', 'FAQs')
@section('meta_description', 'Answers to common questions about ' . store_name() . ' orders, shipping, returns, and products.')
@section('canonical', route('faq.index'))

@section('content')
@if ($faqs->count())
<script nonce="{{ $cspNonce }}" type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
@foreach ($faqs as $category => $items)
@foreach ($items as $faq)
        {
            "@type": "Question",
            "name": {!! json_encode(strip_tags($faq->question), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "acceptedAnswer": {
                "@type": "Answer",
                "text": {!! json_encode(strip_tags($faq->answer), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            }
        }@if (! ($loop->parent->last && $loop->last)),@endif
@endforeach
@endforeach
    ]
}
</script>
@endif
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container text-center">
        <h1 class="vr-section-title mb-2">Frequently Asked Questions</h1>
        <p class="vr-section-sub">Find quick answers to common questions</p>
    </div>
</div>

<div class="vr-section">
    <div class="container">
        @if ($faqs->count())
            @foreach ($faqs as $category => $items)
                <h2 class="mb-3 mt-4" style="font-weight:700;color:var(--vr-green-dark);font-size:1.05rem;">{{ $category }}</h2>
                <div class="accordion mb-4" id="faq-{{ Str::slug($category) }}">
                    @foreach ($items as $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header" style="font-size:1rem;">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-item-{{ $faq->id }}">
                                    {{ $faq->question }}
                                </button>
                            </h3>
                            <div id="faq-item-{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#faq-{{ Str::slug($category) }}">
                                <div class="accordion-body" style="line-height:1.7;color:var(--vr-text);">
                                    {!! nl2br(e($faq->answer)) !!}

                                    @if ($faq->categories->isNotEmpty() || $faq->products->isNotEmpty())
                                        <div class="faq-related mt-3 pt-3" style="border-top:1px solid var(--vr-border);">
                                            @if ($faq->categories->isNotEmpty())
                                                <div class="mb-2">
                                                    <span class="small fw-semibold text-muted">Related Category:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach ($faq->categories as $linkedCategory)
                                                            <a href="{{ route('shop.category', $linkedCategory) }}" class="vr-chip">{{ $linkedCategory->name }}</a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($faq->products->isNotEmpty())
                                                <div>
                                                    <span class="small fw-semibold text-muted">Related Products:</span>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach ($faq->products->take(4) as $linkedProduct)
                                                            <a href="{{ route('product.show', $linkedProduct) }}" class="vr-chip">{{ $linkedProduct->name }}</a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="vr-empty">
                <i class="bi bi-question-circle"></i>
                <h4 class="mt-3 fw-bold">No FAQs yet</h4>
                <p>Check back soon or reach out to us directly.</p>
            </div>
        @endif

        <div class="text-center mt-5">
            <p class="text-muted mb-3">Can't find what you're looking for?</p>
            <a href="{{ route('contact.index') }}" class="btn-vr">Contact Us</a>
        </div>
    </div>
</div>
@endsection
