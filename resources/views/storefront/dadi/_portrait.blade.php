@php
    $dadiPortrait = 'images/dadi-portrait.svg';
    $hasPortrait = file_exists(public_path($dadiPortrait));
@endphp

<div class="dadi-portrait">
    @if ($hasPortrait)
        <img
            src="{{ asset($dadiPortrait) }}"
            alt="{{ store_name() }} ki Dadi"
            class="dadi-portrait-img"
        >
    @else
        <div class="dadi-portrait-mark" aria-hidden="true">
            <span>D</span>
        </div>
    @endif
    <svg class="dadi-portrait-leaf" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M20 3c.5 5.5-1.4 9.8-6.2 12.4-4.6 2.5-9.6 1.9-12.3-.7.3 4.2 3 7.6 7.3 8.3C12.9 23.5 18 20.7 20 3Z"
            fill="#B58A3A" opacity="0.9"/>
        <path d="M20 3c-.9 6.7-4 11-9.5 12.8" stroke="#F7F4EA" stroke-width="1.4"
            stroke-linecap="round" opacity="0.7"/>
    </svg>
</div>