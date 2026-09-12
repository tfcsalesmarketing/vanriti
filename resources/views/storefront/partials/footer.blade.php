@php
    $logo = setting('store_logo');
    if (! $logo) {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (file_exists(public_path('images/logo.' . $ext))) {
                $logo = 'images/logo.' . $ext;
                break;
            }
        }
    }
    $footerCats = App\Models\Category::with(['children'])
        ->whereNull('parent_id')
        ->where('status', 'active')
        ->orderBy('sort_order')
        ->take(6)
        ->get();
@endphp

<footer class="vr-footer d-none d-lg-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a href="{{ route('home') }}" class="vr-footer-brand mb-3">
                    @if ($logo)
                        <img src="{{ image_url($logo, 'favicon.ico') }}" alt="{{ store_name() }}" height="40" class="vr-logo">
                    @else
                        <span class="vr-brand-name">{{ store_name() }}</span>
                    @endif
                </a>
                <p class="vr-footer-about">{{ setting('store_tagline', 'PURE BY NATURE') }}. Handcrafted skincare, herbal teas and wellness essentials made with nature in mind.</p>
                <ul class="list-unstyled vr-footer-contact mb-0">
                    <li><i class="ri-map-pin-2-line"></i>{{ setting('store_address', '') }}</li>
                    <li><i class="ri-mail-line"></i>{{ setting('store_email', '') }}</li>
                    <li><i class="ri-phone-line"></i>{{ setting('store_phone', '') }}</li>
                </ul>
            </div>

            <div class="col-lg-2">
                <div class="vr-footer-title">Shop</div>
                <ul class="list-unstyled vr-footer-links">
                    <li><a href="{{ route('shop.index') }}">All Products</a></li>
                    @foreach ($footerCats as $cat)
                        <li><a href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="col-lg-2">
                <div class="vr-footer-title">Company</div>
                <ul class="list-unstyled vr-footer-links">
                    <li><a href="{{ route('info.about') }}">About Us</a></li>
                    <li><a href="{{ route('blog.index') }}">Journal</a></li>
                    <li><a href="{{ route('faq.index') }}">FAQs</a></li>
                    <li><a href="{{ route('contact.index') }}">Contact Us</a></li>
                </ul>
            </div>

            <div class="col-lg-2">
                <div class="vr-footer-title">Customer Care</div>
                <ul class="list-unstyled vr-footer-links">
                    <li><a href="{{ route('track') }}">Track Order</a></li>
                    <li><a href="{{ route('account.orders') }}">My Orders</a></li>
                    <li><a href="{{ route('wishlist.index') }}">Wishlist</a></li>
                    <li><a href="{{ route('shop.return-policy') }}">Returns &amp; Refunds</a></li>
                    <li><a href="{{ route('shop.cancellation-policy') }}">Cancellation Policy</a></li>
                </ul>
            </div>

            <div class="col-lg-2">
                <div class="vr-footer-title">Policies</div>
                <ul class="list-unstyled vr-footer-links">
                    <li><a href="{{ route('info.shipping') }}">Shipping &amp; Delivery</a></li>
                    <li><a href="{{ route('info.privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('info.terms') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('info.disclaimer') }}">Disclaimer</a></li>
                </ul>
            </div>
        </div>

        <div class="vr-footer-bottom">
            <span>&copy; {{ date('Y') }} {{ store_name() }}. All rights reserved.</span>
        </div>
    </div>
</footer>

@include('storefront.partials.bottom-bar')
