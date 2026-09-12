@extends('storefront.layouts.app')

@section('title', 'Contact Us')

@section('content')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ContactPage",
    "name": "Contact Us",
    "url": {!! json_encode(route('contact.index'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    @if (setting('support_email'))"email": {!! json_encode(setting('support_email'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    @if (setting('store_phone'))"telephone": {!! json_encode(setting('store_phone'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    "mainEntity": {
        "@@type": "Organization",
        "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "url": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    }
}
</script>
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container text-center">
        <h1 class="vr-section-title mb-2">Contact Us</h1>
        <p class="vr-section-sub">We'd love to hear from you</p>
    </div>
</div>

<div class="vr-section">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="vr-card p-4">
                    <h5 class="fw-bold mb-4" style="color:var(--vr-green-dark);">Get in Touch</h5>

                    @if (setting('support_email'))
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <i class="bi bi-envelope-fill" style="color:var(--vr-green);font-size:1.2rem;margin-top:2px;"></i>
                            <div>
                                <div class="fw-semibold small">Email</div>
                                <a href="mailto:{{ setting('support_email') }}">{{ setting('support_email') }}</a>
                            </div>
                        </div>
                    @endif

                    @if (setting('store_phone'))
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <i class="bi bi-telephone-fill" style="color:var(--vr-green);font-size:1.2rem;margin-top:2px;"></i>
                            <div>
                                <div class="fw-semibold small">Phone</div>
                                <a href="tel:{{ setting('store_phone') }}">{{ setting('store_phone') }}</a>
                            </div>
                        </div>
                    @endif

                    @if (setting('whatsapp_number'))
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <i class="bi bi-whatsapp" style="color:var(--vr-green);font-size:1.2rem;margin-top:2px;"></i>
                            <div>
                                <div class="fw-semibold small">WhatsApp</div>
                                <a href="https://wa.me/{{ setting('whatsapp_number') }}" target="_blank" rel="noopener">{{ setting('whatsapp_number') }}</a>
                            </div>
                        </div>
                    @endif

                    @if (setting('store_address'))
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <i class="bi bi-geo-alt-fill" style="color:var(--vr-green);font-size:1.2rem;margin-top:2px;"></i>
                            <div>
                                <div class="fw-semibold small">Address</div>
                                <span>{{ setting('store_address') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="vr-card p-4">
                    <h5 class="fw-bold mb-4" style="color:var(--vr-green-dark);">Send a Message</h5>
                    <form action="{{ route('contact.store') }}" method="POST" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email *</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Subject</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Message *</label>
                                <textarea name="message" class="form-control" rows="5">{{ old('message') }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-vr">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
