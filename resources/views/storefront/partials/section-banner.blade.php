@if ($banner)
    @php
        $promoDesktop = image_url($banner->image);
        $promoMobile = $banner->mobile_image ? image_url($banner->mobile_image) : null;
        $promoAlt = $banner->title ?: store_name().' promotional banner';
    @endphp
    <section class="vr-promo">
        <div class="container">
            @if ($banner->link)
                <a href="{{ $banner->link }}" class="vr-promo-link">
                    <picture>
                        @if ($promoMobile)
                            <source media="(max-width: 767.98px)" srcset="{{ $promoMobile }}">
                        @endif
                        <img src="{{ $promoDesktop }}" alt="{{ $promoAlt }}" class="vr-promo-img" loading="lazy" decoding="async">
                    </picture>
                </a>
            @else
                <picture>
                    @if ($promoMobile)
                        <source media="(max-width: 767.98px)" srcset="{{ $promoMobile }}">
                    @endif
                    <img src="{{ $promoDesktop }}" alt="{{ $promoAlt }}" class="vr-promo-img" loading="lazy" decoding="async">
                </picture>
            @endif
        </div>
    </section>
@endif
