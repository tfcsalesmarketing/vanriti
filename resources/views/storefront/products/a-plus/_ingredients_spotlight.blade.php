@php
    $title = $data['title'] ?? 'Key Botanical Actives';
    $subtitle = $data['subtitle'] ?? 'Handpicked medicinal herbs working in synergistic harmony.';
    $items = $data['items'] ?? [];
    $rawContent = $data['raw_content'] ?? ($product->ingredients ?? '');
@endphp

<div class="vr-aplus-module vr-aplus-ingredients mb-5">
    <div class="vr-aplus-sec-header text-center mb-4">
        <h3 class="vr-aplus-sec-title">
            <i class="ri-leaf-line text-success me-1"></i> {{ $title }}
        </h3>
        @if ($subtitle)
            <p class="vr-aplus-sec-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if (!empty($items) && is_array($items))
        <div class="vr-aplus-herb-grid">
            @foreach ($items as $herb)
                @php
                    $herbName = $herb['name'] ?? '';
                    $herbBotanical = $herb['botanical'] ?? '';
                    $herbBenefit = $herb['benefit'] ?? '';
                    $herbImg = $herb['image'] ?? null;
                @endphp
                <div class="vr-aplus-herb-card">
                    <div class="vr-aplus-herb-avatar">
                        @if ($herbImg)
                            <img src="{{ image_url($herbImg, 'favicon.ico') }}" alt="{{ $herbName }}" class="vr-aplus-herb-img" loading="lazy">
                        @else
                            <div class="vr-aplus-herb-fallback-icon">
                                <i class="ri-plant-line"></i>
                            </div>
                        @endif
                    </div>
                    <h4 class="vr-aplus-herb-name">{{ $herbName }}</h4>
                    @if ($herbBotanical)
                        <div class="vr-aplus-herb-botanical"><em>{{ $herbBotanical }}</em></div>
                    @endif
                    @if ($herbBenefit)
                        <p class="vr-aplus-herb-benefit">{{ $herbBenefit }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif ($rawContent)
        <div class="vr-aplus-raw-ingredients-box">
            <div class="vr-aplus-raw-content">
                {!! Str::contains($rawContent, ['<p', '<ul', '<ol', '<li', '<b', '<strong', '<div']) ? clean_html($rawContent) : nl2br(e($rawContent)) !!}
            </div>
        </div>
    @endif
</div>
