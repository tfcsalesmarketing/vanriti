@if (!empty($product->has_a_plus))
@php
    $modules = $product->getEffectiveAPlusModules();
@endphp

@if (!empty($modules))
<section class="vr-aplus-section mt-5 pt-3" id="vrProductAPlus" aria-label="Enhanced Product Story">
    <div class="vr-aplus-master-header text-center mb-5">
        <span class="vr-aplus-master-badge">
            <i class="ri-sparkling-fill"></i> Enhanced Ayurvedic Experience
        </span>
        <h2 class="vr-aplus-master-title">Botanical Care &amp; Formulation Details</h2>
        <p class="vr-aplus-master-subtitle">Explore the botanicals, careful preparation, and traditional beauty rituals behind {{ $product->name }}.</p>
    </div>

    <div class="vr-aplus-container">
        @foreach ($modules as $module)
            @php
                $modType = $module['type'] ?? '';
            @endphp

            @if ($modType && view()->exists('storefront.products.a-plus._' . $modType))
                @include('storefront.products.a-plus._' . $modType, [
                    'data' => $module,
                    'product' => $product,
                ])
            @endif
        @endforeach
    </div>
</section>
@endif
@endif

