@php
    $title = $data['title'] ?? 'The Vanriti Purity Promise';
    $subtitle = $data['subtitle'] ?? 'Classical Ayurvedic science formulated for modern cellular wellness.';
    $items = $data['items'] ?? [];
@endphp

@if (!empty($items))
<div class="vr-aplus-module vr-aplus-features mb-5">
    @if ($title)
        <div class="vr-aplus-sec-header text-center mb-4">
            <h3 class="vr-aplus-sec-title">{{ $title }}</h3>
            @if ($subtitle)
                <p class="vr-aplus-sec-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="vr-aplus-features-grid">
        @foreach ($items as $item)
            @php
                $itemIcon = $item['icon'] ?? 'ri-leaf-line';
                $itemImg = $item['image'] ?? null;
                $itemTitle = $item['title'] ?? '';
                $itemDesc = $item['description'] ?? '';
            @endphp
            <div class="vr-aplus-feat-card">
                <div class="vr-aplus-feat-icon-wrap">
                    @if ($itemImg)
                        <img src="{{ image_url($itemImg, 'favicon.ico') }}" alt="{{ $itemTitle }}" class="vr-aplus-feat-custom-img" loading="lazy">
                    @else
                        <i class="{{ $itemIcon }} vr-aplus-feat-icon"></i>
                    @endif
                </div>
                <h4 class="vr-aplus-feat-title">{{ $itemTitle }}</h4>
                <p class="vr-aplus-feat-desc">{!! nl2br(e($itemDesc)) !!}</p>
            </div>
        @endforeach
    </div>
</div>
@endif

