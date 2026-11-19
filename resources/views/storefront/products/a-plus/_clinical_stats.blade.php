@php
    $title = $data['title'] ?? 'Real Formulations, Real Results';
    $subtitle = $data['subtitle'] ?? 'Clinically tested and validated by authentic Ayurvedic practitioners.';
    $stats = $data['stats'] ?? [];
@endphp

@if (!empty($stats) && is_array($stats))
<div class="vr-aplus-module vr-aplus-stats mb-5">
    <div class="vr-aplus-stats-card">
        @if ($title)
            <div class="vr-aplus-stats-header text-center mb-4">
                <span class="vr-aplus-stats-pill">
                    <i class="ri-shield-star-fill"></i> Proven Ayurvedic Performance
                </span>
                <h3 class="vr-aplus-stats-title">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="vr-aplus-stats-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        <div class="vr-aplus-stats-grid">
            @foreach ($stats as $stat)
                @php
                    $num = $stat['number'] ?? '';
                    $label = $stat['label'] ?? '';
                    $sub = $stat['sub'] ?? '';
                @endphp
                <div class="vr-aplus-stat-item">
                    <div class="vr-aplus-stat-num">{{ $num }}</div>
                    <div class="vr-aplus-stat-label">{{ $label }}</div>
                    @if ($sub)
                        <div class="vr-aplus-stat-sub">{{ $sub }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
