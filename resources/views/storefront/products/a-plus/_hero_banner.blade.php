@php
    $eyebrow = $data['eyebrow'] ?? 'Ayurvedic Formulations';
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $image = $data['image'] ?? null;
    $imageAlt = $data['image_alt'] ?? ($product->name ?? 'Vanriti Formulation');
@endphp

@if ($title || $image)
<div class="vr-aplus-module vr-aplus-hero-banner mb-5">
    <div class="vr-aplus-hero-card">
        @if ($image)
            <div class="vr-aplus-hero-media">
                <img src="{{ image_url($image, 'favicon.ico') }}" alt="{{ $imageAlt }}" class="vr-aplus-hero-img" loading="lazy" decoding="async">
            </div>
        @endif

        <div class="vr-aplus-hero-content">
            @if ($eyebrow)
                <span class="vr-aplus-eyebrow">
                    <i class="ri-sparkling-fill"></i> {{ $eyebrow }}
                </span>
            @endif

            @if ($title)
                <h3 class="vr-aplus-hero-title">{{ $title }}</h3>
            @endif

            @if ($description)
                <p class="vr-aplus-hero-desc">{!! nl2br(e($description)) !!}</p>
            @endif
        </div>
    </div>
</div>
@endif
