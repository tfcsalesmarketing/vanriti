@extends('storefront.layouts.app')

@section('title', 'About ' . store_name())
@section('meta_description', 'Discover the story behind ' . store_name() . ' — handcrafted skincare, herbal teas and wellness essentials, made with nature in mind.')

@section('content')
<div class="vr-section">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>

        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">The {{ store_name() }} Story</h1>

        <div class="d-grid gap-4 lead-parent">
            <p class="lead">At {{ store_name() }}, we believe nature already has the answers. We bring together time-honoured botanicals and modern formulation science to craft skincare, personal care, herbal teas and wellness essentials that are pure, effective and kind to the planet.</p>

            <div class="row g-4 my-2">
                <div class="col-sm-6">
                    <div class="vr-cart-item h-100 p-4">
                        <div class="vr-kicker mb-2">Our Why</div>
                        <h5 class="fw-bold mb-2">Nature-first, always</h5>
                        <p class="small text-muted mb-0">Every product starts with a simple question — would nature approve? We shortlist ingredients by purity, provenance and performance before anything else.</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="vr-cart-item h-100 p-4">
                        <div class="vr-kicker mb-2">Our Promise</div>
                        <h5 class="fw-bold mb-2">Honest & transparent</h5>
                        <p class="small text-muted mb-0">Clear ingredient lists, truthful claims and responsibly sourced botanicals. What's on the label is exactly what's in the jar.</p>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-2">What we make</h5>
            <ul class="mb-0">
                <li><strong>Skincare &amp; personal care</strong> — gentle, actives-led products that respect your skin's natural barrier.</li>
                <li><strong>Herbal teas &amp; infusions</strong> — single-origin and blended botanicals for everyday wellness.</li>
                <li><strong>Wellness essentials</strong> — clean rituals that make self-care feel like coming home.</li>
            </ul>

            <hr>

            <div class="small text-muted mb-0">
                <span class="fw-semibold text-dark">{{ store_name() }}</span><br>
                {{ setting('store_address', '') }}<br>
                {{ setting('store_email', '') }} &middot; {{ setting('store_phone', '') }}
            </div>
        </div>
    </div>
</div>
@endsection