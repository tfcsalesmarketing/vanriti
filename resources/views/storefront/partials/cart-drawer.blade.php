{{-- Luxury Slide-Over Cart Drawer --}}
<div class="vr-cart-drawer-backdrop" id="vrCartDrawerBackdrop"></div>
<div class="vr-cart-drawer" id="vrCartDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Shopping Bag">
    <div class="vr-drawer-header d-flex align-items-center justify-content-between p-3 border-bottom bg-white">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bag text-success fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Your Shopping Bag</h6>
            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 small js-drawer-count"></span>
        </div>
        <button type="button" class="vr-drawer-close-btn" id="vrCartDrawerClose" aria-label="Close Bag">
            <i class="ri-close-line"></i>
        </button>
    </div>
    <div class="vr-drawer-body d-flex flex-column" id="vrCartDrawerBody" style="height: calc(100% - 65px);">
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
})();
</script>
