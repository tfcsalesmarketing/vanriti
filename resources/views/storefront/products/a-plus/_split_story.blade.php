@php
    $title = $data['title'] ?? '';
    $subtitle = $data['subtitle'] ?? '';
    $content = $data['content'] ?? '';
    $image = $data['image'] ?? null;
    $imageAlt = $data['image_alt'] ?? ($title ?: 'Formulation Detail');
    $reverse = !empty($data['reverse']);
    $bullets = $data['bullets'] ?? [];
@endphp

@if ($title || $content || $image)
<div class="vr-aplus-module vr-aplus-split mb-5 {{ $reverse ? 'is-reversed' : '' }}">
    <div class="vr-aplus-split-row">
        <!-- Media Column -->
        <div class="vr-aplus-split-media">
            @if ($image)
                <div class="vr-aplus-split-img-frame">
                    <img src="{{ image_url($image, 'favicon.ico') }}" alt="{{ $imageAlt }}" class="vr-aplus-split-img" loading="lazy" decoding="async">
                </div>
            @else
                <div class="vr-aplus-split-placeholder">
                    <i class="ri-leaf-line"></i>
                </div>
            @endif
        </div>

        <!-- Text Column -->
        <div class="vr-aplus-split-body">
            @if ($subtitle)
                <span class="vr-aplus-split-sub">
                    <i class="ri-sparkling-line"></i> {{ $subtitle }}
                </span>
            @endif

            @if ($title)
                <h3 class="vr-aplus-split-title">{{ $title }}</h3>
            @endif

            @if ($content)
                <div class="vr-aplus-split-desc">
                    {!! Str::contains($content, ['<p', '<ul', '<ol', '<li', '<b', '<strong', '<div']) ? clean_html($content) : nl2br(e($content)) !!}
                </div>
            @endif

            @if (!empty($bullets) && is_array($bullets))
                <ul class="vr-aplus-split-bullets">
                    @foreach ($bullets as $bullet)
                        @if (!empty($bullet))
                            <li><i class="ri-checkbox-circle-fill text-success"></i> <span>{{ $bullet }}</span></li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endif

