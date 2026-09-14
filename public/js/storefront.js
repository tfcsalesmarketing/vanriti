document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;

    // ---- Toast ----
    function ensureToastWrap() {
        var wrap = document.getElementById('vrToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'vrToastWrap';
            body.appendChild(wrap);
        }
        return wrap;
    }

    function toast(message, type) {
        var wrap = ensureToastWrap();
        var el = document.createElement('div');
        el.className = 'vr-toast show ' + (type === 'error' ? 'danger' : '');
        el.innerHTML = '<i class="bi ' + (type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle') + '"></i><span></span>';
        var span = el.querySelector('span');
        if (Array.isArray(message)) {
            span.innerHTML = message.join('<br>');
        } else {
            span.textContent = message;
        }
        wrap.appendChild(el);
        var duration = type === 'error' ? 5000 : 2600;
        setTimeout(function () { el.classList.add('hide'); }, duration);
        setTimeout(function () { el.remove(); }, duration + 400);
    }

    window.vrToast = toast;

    // ---- Inline field errors (server-side validation) ----
    window.vrInlineErrors = function (errors, scope) {
        if (!errors || typeof errors !== 'object') return;
        var root = scope || document;
        Object.keys(errors).forEach(function (field) {
            var messages = errors[field];
            var msg = Array.isArray(messages) ? messages[0] : messages;
            if (!msg) return;
            showFieldError(root, field, msg);
        });
    };

    function fieldFeedback(root, input) {
        var fb = input.parentElement.querySelector('.invalid-feedback');
        if (fb) return fb;

        var container = input.closest('.mb-3, .col-md-6, .col-md-3, .col-12, .col-sm-6, .col-md-4, .col-md-5, .row');
        if (container) {
            fb = container.querySelector('.invalid-feedback');
            if (fb) return fb;
        }

        fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        input.parentElement.appendChild(fb);
        return fb;
    }

    function showFieldError(root, field, msg) {
        var input = root.querySelector('[name="' + field + '"]');
        if (!input) return;
        input.classList.add('is-invalid');
        fieldFeedback(root, input).textContent = msg;
    }

    function clearFieldError(root, input) {
        if (!input) return;
        input.classList.remove('is-invalid');
        var fb = fieldFeedback(root || document, input);
        if (fb) fb.textContent = '';
    }

    document.addEventListener('input', function (e) {
        var input = e.target;
        if (!input || !input.name) return;
        clearFieldError(document, input);
        Array.from(input.form ? input.form.elements : []).forEach(function (el) {
            if (el && el.name && el.name.split('[')[0] === input.name.split('[')[0] && el !== input) {
                clearFieldError(document, el);
            }
        });
    });

    // ---- Update header cart badge ----
    function setCartCount(count) {
        document.querySelectorAll('.js-cart-count').forEach(function (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count <= 0);
        });
    }

    // ---- Update header wishlist badge ----
    function setWishCount(count) {
        document.querySelectorAll('.js-wishlist-count').forEach(function (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count <= 0);
        });
    }

    // ---- Quantity steppers (cart page) ----
    var MAX_QTY = 5;

    function formatPrice(amount) {
        return '₹' + Number(amount).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function updateCartTotals(card, qty) {
        var unitPrice = parseFloat(card.dataset.unitPrice) || 0;
        var lineTotal = unitPrice * qty;
        var lineEl = card.querySelector('.vr-line-total');
        if (lineEl) lineEl.textContent = formatPrice(lineTotal);

        var minusBtn = card.querySelector('.qty-minus');
        var plusBtn = card.querySelector('.qty-plus');
        if (minusBtn) minusBtn.disabled = qty <= 1;
        if (plusBtn) plusBtn.disabled = qty >= MAX_QTY;

        var subtotal = 0;
        document.querySelectorAll('.vr-cat-card[data-unit-price]').forEach(function (c) {
            var p = parseFloat(c.dataset.unitPrice) || 0;
            var q = parseInt(c.querySelector('.vr-qty input').value || '1', 10);
            subtotal += p * q;
        });

        var summarySubtotal = document.getElementById('vrSummarySubtotal');
        if (summarySubtotal) summarySubtotal.textContent = formatPrice(subtotal);

        var shippingEl = document.getElementById('vrSummaryShipping');
        var totalEl = document.getElementById('vrSummaryTotal');
        var freeThreshold = parseFloat(document.querySelector('.vr-shipping-banner')?.dataset.freeThreshold) || 499;
        var shippingCharge = subtotal >= freeThreshold ? 0 : 49;
        var total = subtotal + shippingCharge;
        if (shippingEl) {
            shippingEl.textContent = shippingCharge <= 0 ? 'FREE' : formatPrice(shippingCharge);
            shippingEl.className = shippingCharge <= 0 ? 'text-success fw-semibold' : 'text-dark fw-semibold';
        }
        if (totalEl) totalEl.textContent = formatPrice(total);

        var banner = document.querySelector('.vr-shipping-banner');
        if (banner) {
            var diff = Math.max(0, freeThreshold - subtotal);
            var pct = Math.min(100, Math.round((subtotal / freeThreshold) * 100));
            var textEl = banner.querySelector('.d-flex.align-items-center.gap-2');
            if (textEl) {
                if (diff <= 0) {
                    textEl.innerHTML = '<i class="ri-truck-line text-success fs-5"></i><span class="text-success">Congratulations! You\'ve unlocked FREE Shipping!</span>';
                } else {
                    textEl.innerHTML = '<i class="ri-truck-line text-success fs-5"></i><span>Add <strong>' + formatPrice(diff) + '</strong> more to get <strong>FREE Shipping</strong></span>';
                }
            }
            var pctEl = banner.querySelector('.small.fw-bold');
            if (pctEl) pctEl.textContent = pct + '%';
            var barFill = banner.querySelector('.vr-progress-bar-fill');
            if (barFill) barFill.style.width = pct + '%';
        }
    }

    document.querySelectorAll('.vr-qty button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            var step = parseInt(this.dataset.step || (this.classList.contains('qty-minus') ? -1 : 1), 10);
            var current = parseInt(input.value || '1', 10);
            var next = Math.max(1, Math.min(MAX_QTY, current + step));
            if (next === current) return;
            input.value = next;

            var card = input.closest('.vr-cat-card');
            if (card) updateCartTotals(card, next);

            var form = input.closest('form');
            if (form && form.dataset.autoQty) {
                form.requestSubmit();
            }
        });
    });

    // ---- Card quick quantity steppers (product cards) ----
    function cardEl(scope) {
        var wrap = scope.closest('.js-qty-wrap') || scope.closest('.js-add-wrap');
        return wrap ? wrap.closest('.vr-card') : null;
    }

    function cardQtyForm(card) {
        return card ? card.querySelector('form.js-card-qty-form') : null;
    }

    function showCardQty(card, cartItemId, qty) {
        if (!card) return;
        var addWrap = card.querySelector('.js-add-wrap');
        var qtyWrap = card.querySelector('.js-qty-wrap');
        var form = cardQtyForm(card);
        var input = qtyWrap ? qtyWrap.querySelector('input[name="quantity"]') : null;
        if (addWrap) addWrap.classList.add('d-none');
        if (!qtyWrap) return;
        if (form && cartItemId) form.dataset.cartItem = cartItemId;
        if (input && qty) input.value = qty;
        qtyWrap.classList.remove('d-none');
        var plus = qtyWrap.querySelector('.vr-card-qty-plus');
        if (plus) plus.disabled = (parseInt(input ? input.value || '1' : '1', 10) || 1) >= MAX_QTY;
    }

    function showCardAdd(card) {
        if (!card) return;
        var qtyWrap = card.querySelector('.js-qty-wrap');
        var addWrap = card.querySelector('.js-add-wrap');
        if (qtyWrap) qtyWrap.classList.add('d-none');
        if (addWrap) addWrap.classList.remove('d-none');
    }

    function cardRemoveItem(card, form) {
        var itemId = form.dataset.cartItem || '';
        if (!itemId) return;
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var url = (form.dataset.removeUrl || '').replace('__ITEM__', itemId);
        if (!url) return;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
        }).then(function (r) {
            return r.json().catch(function () { return { success: false, message: 'Something went wrong.' }; });
        }).then(function (data) {
            if (data.success) {
                toast(data.message || 'Removed from cart.');
                if (typeof data.cartCount !== 'undefined') setCartCount(data.cartCount);
                showCardAdd(card);
            } else {
                toast(data.message || 'Could not remove item.', 'error');
            }
        }).catch(function () {
            toast('Could not remove item.', 'error');
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.js-qty-wrap .vr-card-qty-btn') : null;
        if (!btn) return;
        var card = cardEl(btn);
        var form = cardQtyForm(card);
        var qtyWrap = card ? card.querySelector('.js-qty-wrap') : null;
        if (!form || !qtyWrap) return;

        var input = qtyWrap.querySelector('input[name="quantity"]');
        var step = parseInt(btn.dataset.step || (btn.classList.contains('vr-card-qty-minus') ? -1 : 1), 10);
        var current = parseInt(input ? input.value || '1' : '1', 10);
        var next = current + step;
        var itemId = form.dataset.cartItem || '';

        if (next < 1) {
            cardRemoveItem(card, form);
            return;
        }
        if (next > MAX_QTY) return;
        if (!itemId) return;

        btn.disabled = true;
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var url = (form.dataset.updateUrl || form.action).replace('__ITEM__', itemId);
        var fd = new FormData(form);
        fd.set('quantity', String(next));

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: fd
        }).then(function (r) {
            return r.json().catch(function () { return { success: false, message: 'Something went wrong.' }; });
        }).then(function (data) {
            btn.disabled = false;
            if (!data.success) {
                toast(data.message || 'Could not update quantity.', 'error');
                return;
            }
            if (input) input.value = next;
            var plus = qtyWrap.querySelector('.vr-card-qty-plus');
            if (plus) plus.disabled = next >= MAX_QTY;
            if (typeof data.cartCount !== 'undefined') setCartCount(data.cartCount);
        }).catch(function () {
            btn.disabled = false;
            toast('Could not update quantity.', 'error');
        });
    });

    // Initialise card stepper states for items already in the cart on load.
    document.querySelectorAll('.js-qty-wrap').forEach(function (qtyWrap) {
        var form = qtyWrap.querySelector('form.js-card-qty-form');
        var input = qtyWrap.querySelector('input[name="quantity"]');
        if (form && form.dataset.cartItem && input) {
            var plus = qtyWrap.querySelector('.vr-card-qty-plus');
            var qty = parseInt(input.value || '1', 10) || 1;
            if (plus) plus.disabled = qty >= MAX_QTY;
        }
    });

    // ---- Product gallery thumbnails ----
    var thumbs = document.querySelectorAll('.vr-gallery-thumb');
    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var target = thumb.dataset.image;
            var main = document.querySelector('#vrMainImage');
            if (main && target) {
                main.src = target;
                thumbs.forEach(function (t) { t.classList.remove('active'); });
                thumb.classList.add('active');
            }
        });
    });

    // ---- Quick add to cart (AJAX) ----
    document.querySelectorAll('.js-add-cart').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (btn.disabled) return;

            var form = btn.closest('form');
            if (!form) return;

            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var url = form.action;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: new FormData(form)
            }).then(function (r) {
                return r.json().catch(function () { return { success: false, message: 'Something went wrong.' }; });
            }).then(function (data) {
                if (data.success) {
                    toast(data.message || 'Added to cart');
                    if (typeof data.cartCount !== 'undefined' && data.cartCount !== null) {
                        setCartCount(data.cartCount);
                    }
                    if (data.cartItemId && !data.redirect_only) {
                        showCardQty(btn.closest('.vr-card'), data.cartItemId, data.quantity || 1);
                    }
                    if (data.analytics) {
                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push(data.analytics);
                    }
                    if (data.redirect_only) {
                        window.location.href = data.redirect;
                    }
                } else {
                    toast(data.message || 'Something went wrong.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }).catch(function () {
                toast('Could not add to cart. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = original;
                form.submit();
            });

            var rearm = setTimeout(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-bag-plus me-1"></i> Add to Cart';
            }, 2000);
            btn.rearm = rearm;
        });
    });

    // ---- Wishlist toggle (AJAX, product cards) ----
    document.addEventListener('submit', function (e) {
        var el = e.target;
        var form = el.closest ? el.closest('.js-wishlist-form') : null;
        if (!form) return;
        e.preventDefault();
        e.stopPropagation();
        var btn = form.querySelector('.vr-wish-btn');
        if (btn) btn.disabled = true;
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: new FormData(form)
        }).then(function (r) {
            return r.json().catch(function () { return { success: false, message: 'Something went wrong.' }; });
        }).then(function (data) {
            if (btn) btn.disabled = false;
            if (!data.success) {
                toast(data.message || 'Could not update wishlist.', 'error');
                return;
            }
            var added = !!data.added;
            if (typeof data.count !== 'undefined') setWishCount(data.count);
            if (btn) {
                btn.classList.toggle('liked', added);
                var icon = btn.querySelector('i');
                if (icon) icon.className = 'bi ' + (added ? 'bi-heart-fill' : 'bi-heart');
                btn.title = added ? 'Remove from wishlist' : 'Add to wishlist';
                btn.setAttribute('aria-label', added ? 'Remove from wishlist' : 'Add to wishlist');
            }
            toast(added ? 'Added to wishlist.' : 'Removed from wishlist.');
        }).catch(function () {
            if (btn) btn.disabled = false;
            toast('Could not update wishlist. Please try again.', 'error');
            window.location.reload();
        });
    });

    // ---- Product gallery lightbox ----
    var mainImgWrap = document.getElementById('vrMainImageWrap');
    var lightbox = document.getElementById('vrLightbox');
    if (mainImgWrap && lightbox) {
        mainImgWrap.addEventListener('click', function () {
            var img = document.getElementById('vrMainImage');
            if (img && img.tagName === 'IMG') {
                document.getElementById('vrLightboxImg').src = img.src;
            }
        });
    }

    // ---- Mobile App Drawer Stagger Animations ----
    document.querySelectorAll('.vr-app-drawer').forEach(function (drawer) {
        drawer.addEventListener('show.bs.offcanvas', function () {
            var items = drawer.querySelectorAll('.vr-drawer-item, .vr-cat-card, .vr-grid-tile');
            items.forEach(function (el, index) {
                el.style.animationDelay = (index * 0.04 + 0.05) + 's';
            });
        });
    });

    // ---- Pincode Lookup (India Post API) ----
    window.vrPincodeLookup = function (pincodeInput, cityInput, stateInput, feedbackEl) {
        if (!pincodeInput || !cityInput || !stateInput) return;

        function setStatus(msg, type) {
            if (feedbackEl) {
                feedbackEl.textContent = msg;
                feedbackEl.className = 'vr-pincode-status ' + (type || '');
            }
        }

        function doLookup(pin) {
            if (pin.length !== 6 || !/^\d{6}$/.test(pin)) return;
            setStatus('Looking up pincode…', 'loading');
            cityInput.readOnly = true;
            stateInput.readOnly = true;

            fetch('https://api.postalpincode.in/pincode/' + pin)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    cityInput.readOnly = false;
                    stateInput.readOnly = false;
                    if (!data || !data[0] || data[0].Status !== 'Success' || !data[0].PostOffice || !data[0].PostOffice.length) {
                        setStatus('Pincode not found. Please enter manually.', 'error');
                        return;
                    }
                    var po = data[0].PostOffice[0];
                    cityInput.value = po.District || '';
                    stateInput.value = po.State || '';
                    cityInput.dispatchEvent(new Event('input'));
                    stateInput.dispatchEvent(new Event('input'));
                    setStatus('✓ ' + (po.District || '') + ', ' + (po.State || ''), 'success');
                })
                .catch(function () {
                    cityInput.readOnly = false;
                    stateInput.readOnly = false;
                    setStatus('Could not fetch pincode. Please enter manually.', 'error');
                });
        }

        pincodeInput.addEventListener('input', function () {
            var pin = this.value.replace(/\D/g, '').slice(0, 6);
            this.value = pin;
            if (pin.length === 6) doLookup(pin);
            else setStatus('', '');
        });

        // Trigger on existing value (edit scenario)
        if (pincodeInput.value && pincodeInput.value.length === 6) {
            doLookup(pincodeInput.value);
        }
    };

    // Auto-init for elements with data-pincode-lookup attribute
    document.querySelectorAll('[data-pincode-input]').forEach(function (pincodeEl) {
        var scope = pincodeEl.closest('form, .vr-pincode-scope') || document;
        var prefix = pincodeEl.dataset.pincodePrefix || '';
        var cityEl = scope.querySelector('[name="' + prefix + 'city"]') || scope.querySelector('[data-pincode-city]');
        var stateEl = scope.querySelector('[name="' + prefix + 'state"]') || scope.querySelector('[data-pincode-state]');
        var feedbackEl = pincodeEl.parentElement.querySelector('.vr-pincode-status') ||
                         (function () { var d = document.createElement('div'); d.className = 'vr-pincode-status'; pincodeEl.parentElement.appendChild(d); return d; })();
        window.vrPincodeLookup(pincodeEl, cityEl, stateEl, feedbackEl);
    });

    // ---- Password visibility toggle ----
    document.querySelectorAll('.password-wrap .password-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            var icon = btn.querySelector('i');
            if (icon) icon.className = show ? 'ri-eye-off-line' : 'ri-eye-line';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
    });

    // ---- Server-side validation on submit (auth pages, reusable) ----
    window.vrLiveServerValidation = function (form, endpoint, opts) {
        opts = opts || {};
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';

        form.addEventListener('submit', function (e) {
            var btn = form.querySelector('[type="submit"]');
            var fields = Array.prototype.slice.call(form.elements).filter(function (el) {
                var name = el.name || '';
                return name && name !== '_token';
            });
            if (!fields.length) return;

            if (btn) btn.disabled = true;

            e.preventDefault();

            var payload = { _token: token, context: opts.context || '' };
            fields.forEach(function (el) {
                payload[el.name] = el.value;
            });

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (btn) btn.disabled = false;
                    if (data && data.valid === false && data.errors && window.vrInlineErrors) {
                        window.vrInlineErrors(data.errors, form);
                        return;
                    }
                    form.submit();
                })
                .catch(function () {
                    if (btn) btn.disabled = false;
                    form.submit();
                });
        });
    };

    });