{{-- Luxury Slide-Over Cart Drawer --}}
<div class="vr-cart-drawer-backdrop" id="vrCartDrawerBackdrop"></div>
<div class="vr-cart-drawer" id="vrCartDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Shopping Bag">
    {{-- Botanical Foliage Top Right Corner --}}
    <div class="vr-drawer-foliage-top" aria-hidden="true">
        <svg viewBox="0 0 180 180" width="180" height="180" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="vrLeafGrad1" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#557548" />
                    <stop offset="60%" stop-color="#739764" />
                    <stop offset="100%" stop-color="#99b78a" />
                </linearGradient>
                <linearGradient id="vrLeafGrad2" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#648856" />
                    <stop offset="70%" stop-color="#8ba87e" />
                    <stop offset="100%" stop-color="#b6cca9" />
                </linearGradient>
                <linearGradient id="vrLeafGrad3" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#46633b" />
                    <stop offset="50%" stop-color="#678a59" />
                    <stop offset="100%" stop-color="#88aa7b" />
                </linearGradient>
            </defs>
            <!-- Branch stem -->
            <path d="M190 -10 C155 35 125 90 95 145" stroke="#4a693f" stroke-width="2.2" stroke-linecap="round" fill="none" opacity="0.6"/>
            <!-- Eucalyptus Leaves spreading out and curving into drawer -->
            <!-- Leaf 1 (Top edge) -->
            <path d="M185 15 C160 22 135 50 142 80 C160 74 178 50 185 15Z" fill="url(#vrLeafGrad1)" opacity="0.95" />
            <path d="M142 80 C140 55 160 30 185 15" stroke="#37522d" stroke-width="0.7" fill="none" opacity="0.3"/>
            <!-- Leaf 2 (Upper left curl) -->
            <path d="M155 35 C130 20 98 32 94 65 C114 68 138 55 155 35Z" fill="url(#vrLeafGrad2)" opacity="0.9" />
            <path d="M94 65 C110 42 134 32 155 35" stroke="#37522d" stroke-width="0.7" fill="none" opacity="0.25"/>
            <!-- Leaf 3 (Mid-left broad eucalyptus) -->
            <path d="M125 70 C104 60 72 75 70 108 C88 108 112 95 125 70Z" fill="url(#vrLeafGrad3)" opacity="0.92" />
            <path d="M70 108 C86 86 108 76 125 70" stroke="#37522d" stroke-width="0.7" fill="none" opacity="0.25"/>
            <!-- Leaf 4 (Lower left drooping) -->
            <path d="M105 105 C85 98 62 118 66 148 C82 144 100 128 105 105Z" fill="url(#vrLeafGrad2)" opacity="0.88" />
            <!-- Leaf 5 (Far right lower) -->
            <path d="M175 55 C185 80 174 118 150 135 C144 110 156 80 175 55Z" fill="url(#vrLeafGrad1)" opacity="0.9" />
            <!-- Leaf 6 (Center bottom tip) -->
            <path d="M138 110 C130 135 110 160 85 170 C88 145 108 128 138 110Z" fill="url(#vrLeafGrad3)" opacity="0.8" />
        </svg>
    </div>

    {{-- Botanical Foliage Bottom Left Corner --}}
    <div class="vr-drawer-foliage-bottom" aria-hidden="true">
        <svg viewBox="0 0 160 160" width="160" height="160" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="vrBLeafGrad1" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#46633b" />
                    <stop offset="60%" stop-color="#739764" />
                    <stop offset="100%" stop-color="#9cb98f" />
                </linearGradient>
                <linearGradient id="vrBLeafGrad2" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#557548" />
                    <stop offset="70%" stop-color="#8ba87e" />
                    <stop offset="100%" stop-color="#b6cca9" />
                </linearGradient>
            </defs>
            <!-- Branch stem -->
            <path d="M-10 175 C20 140 55 100 90 60" stroke="#4a693f" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.55"/>
            <!-- Eucalyptus Leaves curling upwards -->
            <path d="M-5 150 C12 120 42 108 60 126 C42 144 18 160 -5 150Z" fill="url(#vrBLeafGrad1)" opacity="0.92" />
            <path d="M18 122 C32 88 68 76 86 100 C68 116 40 130 18 122Z" fill="url(#vrBLeafGrad2)" opacity="0.88" />
            <path d="M42 94 C54 60 90 52 106 76 C84 92 60 104 42 94Z" fill="url(#vrBLeafGrad1)" opacity="0.85" />
            <path d="M-6 100 C6 70 36 64 50 84 C34 96 12 108 -6 100Z" fill="url(#vrBLeafGrad2)" opacity="0.78" />
            <path d="M68 66 C78 38 112 32 124 54 C104 68 82 76 68 66Z" fill="url(#vrBLeafGrad1)" opacity="0.7" />
        </svg>
    </div>

    {{-- Header --}}
    <div class="vr-drawer-header">
        <div class="vr-drawer-header-left">
            <svg class="vr-drawer-bag-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <h5 class="vr-drawer-heading">Your Shopping Bag</h5>
            <span class="vr-drawer-pill-badge js-drawer-count"></span>
        </div>
        <button type="button" class="vr-drawer-close-circle" id="vrCartDrawerClose" aria-label="Close Bag">
            <i class="ri-close-line" style="font-size: 1.3rem;"></i>
        </button>
    </div>

    {{-- Dynamic Body --}}
    <div class="vr-drawer-body" id="vrCartDrawerBody">
        <div class="d-flex align-items-center justify-content-center h-100 py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading…</span>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var drawer = document.getElementById('vrCartDrawer');
    var backdrop = document.getElementById('vrCartDrawerBackdrop');
    var closeBtn = document.getElementById('vrCartDrawerClose');
    var body = document.getElementById('vrCartDrawerBody');

    function openDrawer(optionalHtml) {
        if (!drawer || !backdrop) {
            drawer = document.getElementById('vrCartDrawer');
            backdrop = document.getElementById('vrCartDrawerBackdrop');
        }
        if (!drawer || !backdrop) return;

        drawer.classList.add('show');
        backdrop.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (optionalHtml) {
            body.innerHTML = optionalHtml;
            updateCountBadge();
        } else {
            loadDrawerContent();
        }
    }

    function closeDrawer() {
        if (!drawer || !backdrop) return;
        drawer.classList.remove('show');
        backdrop.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function updateCountBadge() {
        if (!body) return;
        var countBadge = document.querySelector('.js-drawer-count');
        var items = body.querySelectorAll('.vr-drawer-item');
        var total = 0;
        items.forEach(function (it) {
            var qtyEl = it.querySelector('.js-drawer-qty-val');
            total += qtyEl ? (parseInt(qtyEl.textContent.trim(), 10) || 1) : 1;
        });
        if (countBadge) {
            countBadge.textContent = total > 0 ? total + (total === 1 ? ' item' : ' items') : '';
        }
        document.querySelectorAll('.js-cart-count').forEach(function (el) {
            el.textContent = total;
            if (total > 0) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
    }

    function loadDrawerContent() {
        if (!body) return;
        body.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 py-5"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading…</span></div></div>';
        fetch('{{ route('cart.drawer') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.html) {
                body.innerHTML = data.html;
                var countBadge = document.querySelector('.js-drawer-count');
                if (countBadge) {
                    countBadge.textContent = data.count > 0 ? data.count + (data.count === 1 ? ' item' : ' items') : '';
                }
                document.querySelectorAll('.js-cart-count').forEach(function (el) {
                    el.textContent = data.count;
                    if (data.count > 0) el.classList.remove('d-none');
                    else el.classList.add('d-none');
                });
            } else {
                body.innerHTML = '<div class="text-center p-4 text-danger small">Unable to load bag.</div>';
            }
        })
        .catch(function () {
            body.innerHTML = '<div class="text-center p-4 text-danger small">Network error. Please try again.</div>';
        });
    }

    // Expose global controller
    window.vrCartDrawer = function (html) { openDrawer(html); };
    window.vrCartDrawer.open = openDrawer;
    window.vrCartDrawer.close = closeDrawer;
    window.vrCartDrawer.load = loadDrawerContent;

    // Close button & backdrop handlers
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });

    // Intercept quantity steps & removals inside drawer
    if (body) {
        body.addEventListener('click', function (e) {
            var token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

            // Plus
            var plusBtn = e.target.closest('.js-drawer-qty-plus');
            if (plusBtn) {
                e.preventDefault();
                var itemRow = plusBtn.closest('.vr-drawer-item');
                var qtyVal = itemRow.querySelector('.js-drawer-qty-val');
                var currentQty = parseInt(qtyVal.textContent.trim(), 10) || 1;
                var max = parseInt(plusBtn.dataset.max || '5', 10);
                if (currentQty >= max) return;

                itemRow.classList.add('is-loading');
                fetch(plusBtn.dataset.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: currentQty + 1 })
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.drawerHtml) {
                        body.innerHTML = res.drawerHtml;
                        updateCountBadge();
                    } else {
                        loadDrawerContent();
                    }
                })
                .catch(function () { itemRow.classList.remove('is-loading'); });
                return;
            }

            // Minus
            var minusBtn = e.target.closest('.js-drawer-qty-minus');
            if (minusBtn) {
                e.preventDefault();
                var itemRow = minusBtn.closest('.vr-drawer-item');
                var qtyVal = itemRow.querySelector('.js-drawer-qty-val');
                var currentQty = parseInt(qtyVal.textContent.trim(), 10) || 1;

                if (currentQty <= 1) {
                    var removeBtn = itemRow.querySelector('.js-drawer-remove');
                    if (removeBtn) removeBtn.click();
                    return;
                }

                itemRow.classList.add('is-loading');
                fetch(minusBtn.dataset.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: currentQty - 1 })
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.drawerHtml) {
                        body.innerHTML = res.drawerHtml;
                        updateCountBadge();
                    } else {
                        loadDrawerContent();
                    }
                })
                .catch(function () { itemRow.classList.remove('is-loading'); });
                return;
            }

            // Remove
            var removeBtn = e.target.closest('.js-drawer-remove');
            if (removeBtn) {
                e.preventDefault();
                var itemRow = removeBtn.closest('.vr-drawer-item');
                itemRow.classList.add('is-loading');

                fetch(removeBtn.dataset.removeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.drawerHtml) {
                        body.innerHTML = res.drawerHtml;
                        updateCountBadge();
                    } else {
                        loadDrawerContent();
                    }
                })
                .catch(function () { itemRow.classList.remove('is-loading'); });
                return;
            }
        });
    }

    // Global click listener to open drawer from any cart icon or trigger
    document.addEventListener('click', function (e) {
        var el = e.target && e.target.nodeType === 1 ? e.target : (e.target ? e.target.parentElement : null);
        if (!el || !el.closest) return;

        var cartTrigger = el.closest('.js-cart-drawer-trigger, a[href$="/cart"], a[href$="/cart/"], a[href*="/cart"]');
        if (!cartTrigger) return;

        // Never intercept links inside the drawer itself (like "View Full Bag" or "Checkout")
        if (cartTrigger.closest('#vrCartDrawer')) return;

        // If currently on cart or checkout pages, allow natural navigation
        var isCartOrCheckout = /(?:^|\/)(cart|checkout)(?:\/|$)/i.test(window.location.pathname);
        if (isCartOrCheckout) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        openDrawer();
    });

    // Intercept Proceed to Checkout inside drawer for fast 1-tap WhatsApp checkout
    document.addEventListener('click', function (e) {
        var el = e.target && e.target.nodeType === 1 ? e.target : (e.target ? e.target.parentElement : null);
        if (!el || !el.closest) return;

        var checkoutBtn = el.closest('#vrDrawerProceedCheckoutBtn, .js-cart-drawer-checkout');
        if (!checkoutBtn) return;

        var guestMeta = document.querySelector('meta[name="vr-is-guest"]');
        var isGuest = guestMeta && guestMeta.getAttribute('content') === '1';

        if (isGuest) {
            e.preventDefault();
            e.stopPropagation();
            closeDrawer();
            if (window.vrLoginModal && typeof window.vrLoginModal.open === 'function') {
                window.vrLoginModal.open({
                    title: '⚡ Fast Checkout with WhatsApp',
                    subtitle: 'Enter your mobile number to checkout in seconds.',
                    purpose: 'fast_checkout',
                    redirect: '{{ route('checkout.index') }}'
                });
            } else {
                window.location.href = checkoutBtn.href;
            }
        }
    });
})();
</script>
