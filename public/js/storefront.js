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

    // ---- Custom Confirm / Alert Modal ----
    function buildVrModal(options) {
        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.className = 'vr-confirm-overlay';

            var icon = options.icon || 'bi-exclamation-triangle-fill';
            var iconClass = 'vr-confirm-icon' + (options.iconClass ? ' ' + options.iconClass : '');

            var buttonsHtml = '';
            if (!options.cancelOnly) {
                buttonsHtml += '<button type="button" class="btn btn-outline-secondary btn-sm vr-confirm-cancel">'
                    + (options.cancelLabel || 'Cancel') + '</button>';
            }
            buttonsHtml += '<button type="button" class="btn btn-sm ' + (options.okClass || 'btn-danger')
                + ' vr-confirm-ok">' + (options.okLabel || 'Yes, proceed') + '</button>';

            overlay.innerHTML =
                '<div class="vr-confirm-dialog">'
                + '<div class="vr-confirm-header">'
                +   '<div class="' + iconClass + '"><i class="bi ' + icon + '"></i></div>'
                +   '<h6 class="vr-confirm-title"></h6>'
                + '</div>'
                + '<div class="vr-confirm-body"></div>'
                + '<div class="vr-confirm-actions">' + buttonsHtml + '</div>'
                + '</div>';

            overlay.querySelector('.vr-confirm-title').textContent = options.title || 'Are you sure?';
            overlay.querySelector('.vr-confirm-body').textContent = options.message || '';
            document.body.appendChild(overlay);
            requestAnimationFrame(function () { overlay.classList.add('active'); });

            function close(value) {
                document.removeEventListener('keydown', keyHandler);
                overlay.classList.remove('active');
                setTimeout(function () { overlay.remove(); }, 200);
                resolve(value);
            }

            function keyHandler(e) {
                if (e.key === 'Escape') close(options.escapeValue !== undefined ? options.escapeValue : null);
                if (e.key === 'Enter') close(options.enterValue !== undefined ? options.enterValue : true);
            }

            overlay.querySelector('.vr-confirm-ok').addEventListener('click', function () { close(true); });
            if (!options.cancelOnly) {
                overlay.querySelector('.vr-confirm-cancel').addEventListener('click', function () { close(false); });
            }
            overlay.addEventListener('click', function (e) { if (e.target === overlay) close(options.backdropValue !== undefined ? options.backdropValue : null); });
            document.addEventListener('keydown', keyHandler);
            overlay.querySelector('.vr-confirm-ok').focus();
        });
    }

    // Confirmation dialog: resolves true on confirm, false on cancel.
    window.vrConfirm = function (message, options) {
        options = options || {};
        return buildVrModal({
            title: options.title,
            message: message,
            okLabel: options.okLabel,
            cancelLabel: options.cancelLabel,
            icon: options.icon,
            iconClass: options.type === 'primary' ? 'vr-icon-primary'
                : (options.type === 'success' ? 'vr-icon-success' : 'vr-icon-danger'),
            okClass: options.type === 'primary' ? 'btn-success'
                : (options.type === 'success' ? 'btn-success' : 'btn-danger'),
            enterValue: true,
            escapeValue: false,
            backdropValue: false
        });
    };

    // Alert dialog: resolves true when dismissed.
    window.vrAlert = function (message, options) {
        options = options || {};
        var type = options.type || 'primary';
        return buildVrModal({
            title: options.title || 'Notice',
            message: message,
            okLabel: options.okLabel || 'OK',
            cancelOnly: true,
            icon: options.icon || 'bi-info-circle-fill',
            iconClass: type === 'danger' ? 'vr-icon-danger'
                : (type === 'success' ? 'vr-icon-success' : 'vr-icon-primary'),
            okClass: type === 'danger' ? 'btn-danger'
                : (type === 'success' ? 'btn-success' : 'btn-primary'),
            enterValue: true,
            escapeValue: true,
            backdropValue: true
        });
    };

    // Intercept forms carrying data-confirm.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.dataset.confirm) return;
        e.preventDefault();
        window.vrConfirm(form.dataset.confirm).then(function (ok) {
            if (ok) form.submit();
        });
    });

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
        document.querySelectorAll('.vr-cat-card[data-unit-price], .vr-cart-item-card[data-unit-price]').forEach(function (c) {
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

            var card = input.closest('.vr-cart-item-card') || input.closest('.vr-cat-card');
            if (card) updateCartTotals(card, next);

            var form = input.closest('form');
            if (form && form.hasAttribute('data-auto-qty')) {
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
                    if (data && data.auth_required) {
                        if (btn) { btn.disabled = false; btn.innerHTML = original; }
                        if (window.vrLoginModal && window.vrLoginModal.open) {
                            // Guest hit a login-required action → open the login modal
                            // and let it replay this exact add request after auth.
                            window.vrLoginModal.pending = form;
                            window.vrLoginModal.open();
                        }
                        return;
                    }
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
    var zoomContainer = document.getElementById('vrZoomContainer');
    var mainImageWrap = document.getElementById('vrMainImageWrap');
    var stageVideo = document.getElementById('vrStageVideo');
    var pdpVideo = document.getElementById('vrPdpVideo');
    var plyrInstance = null;

    function getPlyrIconUrl() {
        if (window.vrPlyrIconUrl) return window.vrPlyrIconUrl;
        var base = window.location.origin;
        if (window.location.pathname.indexOf('/vanriti') === 0) {
            base += '/vanriti';
        }
        return base + '/vendor/plyr/plyr.svg';
    }

    function mimeFromUrl(url) {
        var path = (url || '').split('#')[0].split('?')[0].toLowerCase();
        if (/\.webm$/.test(path)) return 'video/webm';
        if (/\.og[gv]$/.test(path)) return 'video/ogg';
        if (/\.mov$/.test(path)) return 'video/quicktime';
        if (/\.3gp$/.test(path)) return 'video/3gpp';
        return 'video/mp4';
    }

    function pauseMainVideo() {
        if (plyrInstance) {
            try { plyrInstance.pause(); } catch (e) {}
        }
        if (pdpVideo && typeof pdpVideo.pause === 'function') {
            try { pdpVideo.pause(); } catch (e) {}
        }
    }

    // data: {url, provider, embedId, poster}
    function playVideo(data) {
        if (!stageVideo || !pdpVideo) return;

        if (!window.Plyr) {
            if (pdpVideo.tagName === 'VIDEO' && data.url) {
                pdpVideo.src = data.url;
            }
            try { if (pdpVideo.play) pdpVideo.play(); } catch (e) {}
            return;
        }

        if (!plyrInstance) {
            var opts = {
                ratio: '1:1',
                hideControls: true,
                resetOnEnd: true,
                iconUrl: getPlyrIconUrl(),
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'mute',
                    'volume',
                    'fullscreen'
                ]
            };
            if (data.provider && data.embedId) {
                opts.poster = data.poster || undefined;
            }
            try {
                plyrInstance = new window.Plyr(pdpVideo, opts);
            } catch (err) {
                console.warn('Plyr initialization failed, using native controls', err);
            }
        }

        if (plyrInstance) {
            if (data.provider && data.embedId) {
                plyrInstance.source = {
                    type: 'video',
                    provider: data.provider,
                    sources: [{ src: data.embedId, provider: data.provider }],
                };
                if (data.poster) plyrInstance.poster = data.poster;
            } else if (data.url && pdpVideo.tagName === 'VIDEO') {
                plyrInstance.source = {
                    type: 'video',
                    sources: [{ src: data.url, type: mimeFromUrl(data.url) }]
                };
            }
            try { plyrInstance.play(); } catch (e) {}
        } else if (pdpVideo.tagName === 'VIDEO' && data.url) {
            pdpVideo.src = data.url;
            try { pdpVideo.play(); } catch (e) {}
        }
    }

    function showVideoStage(thumb) {
        if (!stageVideo) return;
        if (mainImageWrap) mainImageWrap.classList.add('d-none');
        stageVideo.classList.remove('d-none');
        if (zoomContainer) zoomContainer.classList.add('is-video');

        playVideo({
            url: thumb.dataset.videoUrl,
            provider: thumb.dataset.videoProvider,
            embedId: thumb.dataset.videoEmbedId,
            poster: thumb.dataset.poster,
        });
    }

    function showImageStage(thumb) {
        var target = thumb.dataset.image;
        var main = document.querySelector('#vrMainImage');
        if (main && target) {
            main.src = target;
        }
        pauseMainVideo();
        if (stageVideo) stageVideo.classList.add('d-none');
        if (mainImageWrap) mainImageWrap.classList.remove('d-none');
        if (zoomContainer) zoomContainer.classList.remove('is-video');
    }


    // A product with a video but no images shows the player immediately.
    if (stageVideo && !thumbs.length) {
        if (zoomContainer) zoomContainer.classList.add('is-video');
        playVideo({
            url: stageVideo.getAttribute('data-video-url'),
            provider: stageVideo.getAttribute('data-video-provider'),
            embedId: stageVideo.getAttribute('data-video-embed-id'),
            poster: stageVideo.getAttribute('data-video-poster'),
        });
    }

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
                if (data && data.auth_required) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                    if (window.vrLoginModal && typeof window.vrLoginModal.open === 'function') {
                        window.vrLoginModal.pendingForm = form;
                        window.vrLoginModal.open();
                    } else {
                        toast(data.message || 'Please login to continue.', 'error');
                    }
                    return;
                }
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
                        if (window.vrMeta) {
                            window.vrMeta.trackAddToCartFromGa4(data.analytics);
                        }
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
            if (data && data.auth_required) {
                if (window.vrLoginModal && window.vrLoginModal.open) {
                    // Guest hit wishlist toggle → open the login modal; replay this
                    // same wishlist toggle after the user authenticates.
                    window.vrLoginModal.pendingForm = form;
                    window.vrLoginModal.open();
                }
                return;
            }
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

    // ---- Product gallery lightbox with prev/next for images and videos ----
    var lightbox = document.getElementById('vrLightbox');
    var lightboxStage = document.getElementById('vrLightboxStage');
    var lightboxCounter = document.getElementById('vrLightboxCounter');
    var lightboxPrev = document.querySelector('.vr-lightbox-prev');
    var lightboxNext = document.querySelector('.vr-lightbox-next');

    var galleryItems = [];
    var galleryDataEl = document.getElementById('vrGalleryData');
    if (galleryDataEl) {
        try {
            var parsed = JSON.parse(galleryDataEl.textContent || '[]');
            if (Array.isArray(parsed) && parsed.length > 0) {
                galleryItems = parsed;
            }
        } catch (err) {
            galleryItems = [];
        }
    }

    if (!galleryItems.length) {
        document.querySelectorAll('.vr-gallery-thumb').forEach(function (thumb) {
            var item = null;
            if (thumb.hasAttribute('data-video')) {
                item = {
                    kind: 'video',
                    url: thumb.getAttribute('data-video-url') || '',
                    provider: thumb.getAttribute('data-video-provider') || '',
                    embedId: thumb.getAttribute('data-video-embed-id') || '',
                    poster: thumb.getAttribute('data-video-poster') || '',
                    alt: (thumb.querySelector('img') && thumb.querySelector('img').alt) || ''
                };
            } else if (thumb.getAttribute('data-image')) {
                item = {
                    kind: 'image',
                    url: thumb.getAttribute('data-image') || '',
                    alt: (thumb.querySelector('img') && thumb.querySelector('img').alt) || ''
                };
            }
            if (item && item.url) {
                galleryItems.push(item);
            }
        });
    }

    if (!galleryItems.length) {
        var mainImg = document.getElementById('vrMainImage');
        if (mainImg && mainImg.tagName === 'IMG' && mainImg.src) {
            galleryItems.push({
                kind: 'image',
                url: mainImg.src,
                alt: mainImg.alt || ''
            });
        }
    }

    var currentGalleryIndex = 0;
    var lightboxIndex = 0;

    function embedUrl(item) {
        var eid = item.embedId || item.embed_id || '';
        if (item.provider === 'youtube' && eid) {
            return 'https://www.youtube.com/embed/' + encodeURIComponent(eid) + '?rel=0&autoplay=1';
        }
        if (item.provider === 'vimeo' && eid) {
            return 'https://player.vimeo.com/video/' + encodeURIComponent(eid) + '?autoplay=1';
        }
        return item.url;
    }

    function selectGalleryItem(index, shouldPlayVideo) {
        if (typeof shouldPlayVideo === 'undefined') shouldPlayVideo = true;
        if (!galleryItems.length) return;
        index = (index + galleryItems.length) % galleryItems.length;
        currentGalleryIndex = index;
        var item = galleryItems[index];

        // Sync active state on all thumbnail rows (both vertical desktop and horizontal mobile)
        document.querySelectorAll('.vr-gallery-thumb').forEach(function (t) {
            var idxAttr = t.getAttribute('data-gallery-index');
            if (idxAttr !== null) {
                t.classList.toggle('active', parseInt(idxAttr, 10) === index);
            } else {
                var url = t.getAttribute('data-video-url') || t.getAttribute('data-image');
                t.classList.toggle('active', url === item.url);
            }
        });

        if (item.kind === 'video') {
            if (mainImageWrap) mainImageWrap.classList.add('d-none');
            if (stageVideo) stageVideo.classList.remove('d-none');
            if (zoomContainer) zoomContainer.classList.add('is-video');
            if (shouldPlayVideo) {
                playVideo({
                    url: item.url,
                    provider: item.provider,
                    embedId: item.embedId || item.embed_id,
                    poster: item.poster
                });
            } else {
                pauseMainVideo();
            }
        } else {
            var main = document.querySelector('#vrMainImage');
            if (main && item.url) {
                main.src = item.url;
                if (item.alt) main.alt = item.alt;
            }
            pauseMainVideo();
            if (stageVideo) stageVideo.classList.add('d-none');
            if (mainImageWrap) mainImageWrap.classList.remove('d-none');
            if (zoomContainer) zoomContainer.classList.remove('is-video');
        }
    }

    function stopLightboxMedia() {
        if (!lightboxStage) return;
        lightboxStage.querySelectorAll('video').forEach(function (v) {
            try {
                v.pause();
                v.removeAttribute('src');
                v.load();
            } catch (e) {}
        });
        lightboxStage.querySelectorAll('iframe').forEach(function (f) {
            try { f.src = 'about:blank'; } catch (e) {}
        });
        lightboxStage.innerHTML = '';
    }

    function renderLightbox() {
        if (!lightboxStage || !galleryItems.length) {
            return;
        }
        // Always stop existing media and pause main-stage video before rendering next slide
        stopLightboxMedia();
        pauseMainVideo();

        var item = galleryItems[lightboxIndex];

        if (item.kind === 'video') {
            var embedIdVal = item.embedId || item.embed_id;
            if (item.provider && embedIdVal) {
                var iframe = document.createElement('iframe');
                iframe.src = embedUrl(item);
                iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                iframe.setAttribute('allowfullscreen', '');
                iframe.style.width = '100%';
                iframe.style.height = '68vh';
                iframe.style.border = '0';
                lightboxStage.appendChild(iframe);
            } else if (item.url) {
                var video = document.createElement('video');
                video.src = item.url;
                video.controls = true;
                video.playsInline = true;
                video.autoplay = true;
                video.poster = item.poster || '';
                video.style.maxWidth = '100%';
                video.style.maxHeight = '72vh';
                video.style.borderRadius = '8px';
                video.style.background = '#000';
                lightboxStage.appendChild(video);
            }
        } else {
            var img = document.createElement('img');
            img.src = item.url;
            img.alt = item.alt || '';
            img.className = 'img-fluid';
            lightboxStage.appendChild(img);
        }

        if (lightboxCounter) {
            lightboxCounter.textContent = (lightboxIndex + 1) + ' / ' + galleryItems.length;
        }

        // Toggle nav arrows if only 1 item
        if (lightboxPrev && lightboxNext) {
            var showArrows = galleryItems.length > 1;
            lightboxPrev.style.display = showArrows ? 'inline-flex' : 'none';
            lightboxNext.style.display = showArrows ? 'inline-flex' : 'none';
        }

        // Highlight active lightbox thumb & scroll it into view
        document.querySelectorAll('.vr-lightbox-thumb').forEach(function (lt) {
            var ltIdx = parseInt(lt.getAttribute('data-lightbox-index'), 10);
            var isCurrent = ltIdx === lightboxIndex;
            lt.classList.toggle('active', isCurrent);
            if (isCurrent && typeof lt.scrollIntoView === 'function') {
                lt.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });

        // Keep page stage thumbnails aligned, BUT DO NOT AUTOPLAY MAIN STAGE!
        selectGalleryItem(lightboxIndex, false);
    }

    function openLightbox(index) {
        if (!lightbox || !galleryItems.length) {
            return;
        }
        pauseMainVideo();
        lightboxIndex = (index + galleryItems.length) % galleryItems.length;
        renderLightbox();
        var BootstrapModal = (window.bootstrap && window.bootstrap.Modal)
            || (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            || null;
        var modal = BootstrapModal ? BootstrapModal.getOrCreateInstance(lightbox) : null;
        if (modal) {
            modal.show();
        } else if (lightbox) {
            lightbox.classList.add('show');
            lightbox.style.display = 'block';
            document.body.classList.add('modal-open');
        }
    }

    function stepLightbox(direction) {
        if (!galleryItems.length) return;
        lightboxIndex = (lightboxIndex + direction + galleryItems.length) % galleryItems.length;
        renderLightbox();
    }

    function findGalleryIndex(thumb) {
        var idxAttr = thumb.getAttribute('data-gallery-index');
        if (idxAttr !== null) {
            var parsedIdx = parseInt(idxAttr, 10);
            if (!isNaN(parsedIdx)) return parsedIdx;
        }
        var url = thumb.hasAttribute('data-video') ? thumb.getAttribute('data-video-url') : thumb.getAttribute('data-image');
        var kind = thumb.hasAttribute('data-video') ? 'video' : 'image';
        for (var i = 0; i < galleryItems.length; i++) {
            if (galleryItems[i].kind === kind && galleryItems[i].url === url) {
                return i;
            }
        }
        return 0;
    }

    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            stepLightbox(-1);
        });
    }
    if (lightboxNext) {
        lightboxNext.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            stepLightbox(1);
        });
    }

    // Lightbox thumb click
    document.addEventListener('click', function (e) {
        var lt = e.target.closest ? e.target.closest('.vr-lightbox-thumb') : null;
        if (!lt) return;
        e.preventDefault();
        e.stopPropagation();
        var idx = parseInt(lt.getAttribute('data-lightbox-index'), 10);
        if (!isNaN(idx)) {
            lightboxIndex = idx;
            renderLightbox();
        }
    });

    // Keyboard navigation: ArrowLeft / ArrowRight
    document.addEventListener('keydown', function (e) {
        if (!lightbox) return;
        var isOpen = lightbox.classList.contains('show') || lightbox.style.display === 'block';
        if (!isOpen) return;

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            stepLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            stepLightbox(1);
        }
    });

    // Touch swipe navigation for mobile
    var touchStartX = 0;
    var touchStartY = 0;
    if (lightboxStage) {
        lightboxStage.addEventListener('touchstart', function (e) {
            if (!e.touches || !e.touches[0]) return;
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        lightboxStage.addEventListener('touchend', function (e) {
            if (!e.changedTouches || !e.changedTouches[0]) return;
            var diffX = e.changedTouches[0].clientX - touchStartX;
            var diffY = e.changedTouches[0].clientY - touchStartY;
            if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
                if (diffX < 0) {
                    stepLightbox(1);
                } else {
                    stepLightbox(-1);
                }
            }
        }, { passive: true });
    }

    // Main image click opens lightbox at current viewed index
    var mainImageLink = mainImageWrap ? mainImageWrap.querySelector('a') : null;
    if (mainImageLink && lightbox) {
        mainImageLink.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openLightbox(currentGalleryIndex);
        });
    }

    if (lightbox) {
        lightbox.addEventListener('hide.bs.modal', stopLightboxMedia);
        lightbox.addEventListener('hidden.bs.modal', stopLightboxMedia);
        lightbox.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .vr-lightbox-close').forEach(function (btn) {
            btn.addEventListener('click', stopLightboxMedia);
        });
    }

    // Thumbnail click updates main stage image only (does NOT pop up modal)
    document.querySelectorAll('.vr-gallery-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function (e) {
            e.preventDefault();
            var idx = findGalleryIndex(thumb);
            selectGalleryItem(idx);
        });
    });

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