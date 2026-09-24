@php
    $steps = ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
    $current = $order->order_status ?? 'pending';
    $terminal = in_array($current, ['cancelled', 'failed'], true);
    $currentIdx = $terminal ? -1 : array_search($current, $steps, true);
@endphp

@if ($terminal)
    <div class="vr-status-terminal">
        <i class="bi {{ $current === 'cancelled' ? 'bi-x-octagon' : 'bi-exclamation-octagon' }}"></i>
        <div>
            <strong>{{ ucwords(str_replace('_', ' ', $current)) }}</strong>
            <div class="small text-muted">{{ $current === 'cancelled' ? 'This order has been cancelled.' : 'This order could not be completed.' }}</div>
        </div>
    </div>
@elseif ($currentIdx !== false)
    <ol class="vr-status-steps">
        @foreach ($steps as $i => $s)
            <li class="{{ $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'active' : '') }}">
                <span class="dot">
                    @if ($i < $currentIdx)
                        <i class="bi bi-check"></i>
                    @elseif ($i === $currentIdx)
                        <i class="bi bi-circle-fill"></i>
                    @endif
                </span>
                <span class="label">{{ ucwords(str_replace('_', ' ', $s)) }}</span>
            </li>
        @endforeach
    </ol>
@endif