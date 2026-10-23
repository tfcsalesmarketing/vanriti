@extends('storefront.layouts.app')
@section('title', 'Fast Checkout')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="vr-cart-item p-4 p-md-5 text-center shadow-sm rounded-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                        <i class="ri-whatsapp-line"></i>
                    </div>
                    <h3 class="fw-bold mb-2">⚡ 1-Tap Fast Checkout</h3>
                    <p class="text-muted mb-4">Verify your WhatsApp mobile number to complete your purchase in 30 seconds. No password required.</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-vr py-3 fs-6 fw-semibold" id="vrTriggerFastCheckout">
                            <i class="ri-whatsapp-line me-2 fs-5 align-middle"></i> Continue with WhatsApp
                        </button>
                    </div>
                    <div class="mt-3 text-center small text-muted">
                        <span>Prefer password? <a href="{{ route('login') }}" class="vr-link-underline text-dark fw-semibold">Sign in here</a></span>
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <a href="{{ route('cart.index') }}" class="small text-muted vr-link-underline">
                            <i class="bi bi-arrow-left me-1"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>   
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    function launchFastCheckout() {
        if (window.vrLoginModal && typeof window.vrLoginModal.open === 'function') {
            window.vrLoginModal.open({
                title: '⚡ Fast Checkout with WhatsApp',
                subtitle: 'Enter your mobile number to checkout in seconds.',
                purpose: 'fast_checkout',
                redirect: '{{ route('checkout.index') }}'
            });
        }
    }

    var btn = document.getElementById('vrTriggerFastCheckout');
    if (btn) btn.addEventListener('click', launchFastCheckout);

    // Auto-launch on page load for seamless experience
    setTimeout(launchFastCheckout, 350);
});
</script>
@endpush
