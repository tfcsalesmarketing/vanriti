@php
    $title = $data['title'] ?? 'The Ayurvedic Daily Ritual';
    $subtitle = $data['subtitle'] ?? 'Mindful application steps to maximize absorption and energetic benefits.';
    $steps = $data['steps'] ?? [];
    $rawContent = $data['raw_content'] ?? ($product->how_to_use ?: ($product->directions ?? ''));
    $tip = $data['tip'] ?? 'For optimal results, apply consistently during your morning and evening skincare ritual.';
@endphp

<div class="vr-aplus-module vr-aplus-ritual mb-5">
    <div class="vr-aplus-sec-header text-center mb-4">
        <h3 class="vr-aplus-sec-title">
            <i class="ri-sparkling-2-line text-warning me-1"></i> {{ $title }}
        </h3>
        @if ($subtitle)
            <p class="vr-aplus-sec-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if (!empty($steps) && is_array($steps))
        <div class="vr-aplus-steps-grid">
            @foreach ($steps as $index => $step)
                @php
                    $stepNum = $step['step'] ?? sprintf('%02d', $index + 1);
                    $stepTitle = $step['title'] ?? '';
                    $stepDesc = $step['desc'] ?? '';
                    $stepImg = $step['image'] ?? null;
                @endphp
                <div class="vr-aplus-step-card">
                    <div class="vr-aplus-step-header">
                        <span class="vr-aplus-step-badge">STEP {{ $stepNum }}</span>
                    </div>

                    @if ($stepImg)
                        <div class="vr-aplus-step-media">
                            <img src="{{ image_url($stepImg, 'favicon.ico') }}" alt="{{ $stepTitle }}" class="vr-aplus-step-img" loading="lazy">
                        </div>
                    @endif

                    <h4 class="vr-aplus-step-title">{{ $stepTitle }}</h4>
                    <p class="vr-aplus-step-desc">{{ $stepDesc }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($rawContent)
        <div class="vr-aplus-ritual-raw-box {{ !empty($steps) ? 'mt-4' : '' }}">
            <div class="vr-aplus-ritual-raw-inner">
                {!! Str::contains($rawContent, ['<p', '<ul', '<ol', '<li', '<b', '<strong', '<div']) ? clean_html($rawContent) : nl2br(e($rawContent)) !!}
            </div>
        </div>
    @endif

    @if ($tip)
        <div class="vr-aplus-ritual-tip mt-3">
            <i class="ri-lightbulb-line text-warning me-1"></i>
            <span><strong>Ayurvedic Tip:</strong> {{ $tip }}</span>
        </div>
    @endif
</div>

