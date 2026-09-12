/* =============================================================
 * VANRITI · DADI — Customer experience script.
 *
 * Presentation, input and in-flight UI state ONLY. The script never
 * understands the conversation: it submits what the customer typed and
 * renders exactly what Laravel returns. On any non-200 answer it rolls
 * back to a safe, honest state (draft restored, nothing invented).
 * ============================================================= */
(function () {
    'use strict';

    var app = document.getElementById('dadiApp');
    if (!app) {
        return;
    }

    var endpoint = app.getAttribute('data-endpoint') || '';
    var clickEndpoint = app.getAttribute('data-click-endpoint') || '';
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '';

    var stage = document.getElementById('dadiStage');
    var form = document.getElementById('dadiForm');
    var input = document.getElementById('dadiMessage');
    var sendBtn = document.getElementById('dadiSend');
    var counter = document.getElementById('dadiCount');
    var errorZone = document.getElementById('dadiError');
    var chips = Array.prototype.slice.call(document.querySelectorAll('.dadi-chip'));
    var emptyState = document.getElementById('dadiEmpty');

    var maxLength = parseInt(input ? input.getAttribute('maxlength') : '1000', 10) || 1000;
    var conversationId = app.getAttribute('data-conversation-id')
        ? parseInt(app.getAttribute('data-conversation-id'), 10)
        : null;
    var busy = false;

    function dismissEmptyState() {
        if (!emptyState || emptyState.parentNode !== stage) {
            return;
        }
        var reduced = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduced) {
            emptyState.remove();
            return;
        }
        emptyState.classList.add('dadi-empty-leave');
        window.setTimeout(function () {
            if (emptyState && emptyState.parentNode) {
                emptyState.remove();
            }
        }, 300);
    }

    /* ---------------- helpers ---------------- */

    function detectedLocale() {
        var lang = (window.navigator.language || 'en').toLowerCase();
        return lang.indexOf('hi') === 0 ? 'hi' : 'en';
    }

    function bubbleFor(role, content) {
        var wrap = document.createElement('div');
        wrap.className = 'dadi-msg ' + (role === 'user' ? 'user' : 'dadi');

        var roleLabel = document.createElement('span');
        roleLabel.className = 'dadi-msg-role';
        roleLabel.textContent = role === 'user' ? 'Aap' : 'Dadi';

        var inner = document.createElement('div');
        inner.className = 'dadi-msg-inner';
        inner.textContent = content;

        wrap.appendChild(roleLabel);
        wrap.appendChild(inner);
        return wrap;
    }

    function thinkingBubble() {
        var wrap = document.createElement('div');
        wrap.className = 'dadi-msg dadi';
        wrap.setAttribute('aria-hidden', 'true');

        var inner = document.createElement('div');
        inner.className = 'dadi-msg-inner dadi-thinking-inner';

        var dots = document.createElement('span');
        dots.className = 'dadi-typing';
        dots.innerHTML = '<i></i><i></i><i></i>';

        var label = document.createElement('span');
        label.className = 'dadi-thinking-label';
        label.textContent = 'Dadi soch rahi hain\u2026';

        inner.appendChild(dots);
        inner.appendChild(label);
        wrap.appendChild(inner);
        return wrap;
    }

    function cardElement(rec) {
        var card = document.createElement('div');
        card.className = 'dadi-card';
        card.setAttribute('data-dadi-ref', rec.reference || '');
        card.setAttribute('data-add-url', (rec.addable && rec.add_url) ? rec.add_url : '');
        card.setAttribute('data-variant-id', rec.variant_id || '');

        var media = document.createElement('a');
        media.className = 'dadi-card-media';
        media.href = rec.product_url;
        media.setAttribute('aria-label', 'Dekho: ' + rec.name);
        media.setAttribute('data-dadi-click', '');

        var img = document.createElement('img');
        img.className = 'dadi-card-img';
        img.src = rec.image_url;
        img.alt = '';
        img.loading = 'lazy';

        media.appendChild(img);

        var body = document.createElement('div');
        body.className = 'dadi-card-body';

        var name = document.createElement('span');
        name.className = 'dadi-card-name';
        name.textContent = rec.name;

        var price = document.createElement('span');
        price.className = 'dadi-card-price';

        var now = document.createElement('span');
        now.className = 'dadi-card-now';
        now.textContent = rec.price_formatted;

        price.appendChild(now);

        if (parseFloat(rec.mrp) > parseFloat(rec.price)) {
            var mrp = document.createElement('del');
            mrp.className = 'dadi-card-mrp';
            mrp.textContent = rec.mrp_formatted;
            price.appendChild(mrp);
        }

        var meta = document.createElement('span');
        meta.className = 'dadi-card-meta';

        var stock = document.createElement('span');
        stock.className = 'dadi-card-stock ' + (rec.available ? 'is-available' : 'is-unavailable');
        stock.textContent = rec.available ? 'Stock mein hai' : 'Abhi stock khatam';

        meta.appendChild(stock);

        body.appendChild(name);
        body.appendChild(price);
        body.appendChild(meta);

        if (rec.why) {
            var why = document.createElement('p');
            why.className = 'dadi-card-why';
            why.textContent = rec.why;
            body.appendChild(why);
        }

        var actions = document.createElement('div');
        actions.className = 'dadi-card-actions';

        if (rec.addable && rec.add_url) {
            var add = document.createElement('button');
            add.setAttribute('type', 'button');
            add.className = 'dadi-card-btn js-dadi-add';
            add.textContent = 'Cart mein rakho';

            var buy = document.createElement('button');
            buy.setAttribute('type', 'button');
            buy.className = 'dadi-card-btn dadi-card-btn-primary js-dadi-buy-now';
            buy.textContent = 'Abhi kharidein';

            actions.appendChild(add);
            actions.appendChild(buy);
        }

        var linkRow = document.createElement('span');
        linkRow.className = 'dadi-card-actions-row';

        var link = document.createElement('a');
        link.className = 'dadi-card-link';
        link.href = rec.product_url;
        link.setAttribute('data-dadi-click', '');
        link.appendChild(document.createTextNode('Dekho '));
        var icon = document.createElement('i');
        icon.className = 'ri-arrow-right-line';
        link.appendChild(icon);

        linkRow.appendChild(link);
        actions.appendChild(linkRow);

        var status = document.createElement('p');
        status.className = 'dadi-card-status';
        status.setAttribute('data-dadi-status', '');
        status.setAttribute('aria-live', 'polite');
        status.hidden = true;

        card.appendChild(media);
        card.appendChild(body);
        card.appendChild(actions);
        card.appendChild(status);
        return card;
    }

    function recommendationsRow(recs) {
        var row = document.createElement('div');
        row.className = 'dadi-recs';
        row.setAttribute('aria-label', recs.length === 1 ? 'Recommended product' : 'Recommended products');
        recs.forEach(function (rec) {
            row.appendChild(cardElement(rec));
        });
        return row;
    }

    function scrollToLatest() {
        window.requestAnimationFrame(function () {
            window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'smooth' });
        });
    }

    function showError(text, asHtml) {
        if (!errorZone) {
            return;
        }
        errorZone.hidden = false;
        var textEl = errorZone.querySelector('.dadi-error-text');
        if (textEl) {
            textEl.textContent = text;
        }
        if (asHtml && textEl) {
            textEl.innerHTML = asHtml;
        }
    }

    function clearError() {
        if (errorZone) {
            errorZone.hidden = true;
        }
    }

    function setBusy(state) {
        busy = state;
        input.disabled = state;
        sendBtn.disabled = state;
        chips.forEach(function (chip) {
            chip.disabled = state;
        });
        if (state) {
            app.setAttribute('aria-busy', 'true');
            stage.classList.add('dadi-is-running');
        } else {
            app.removeAttribute('aria-busy');
            stage.classList.remove('dadi-is-running');
        }
    }

    function updateCounter() {
        if (counter) {
            counter.textContent = input.value.length + ' / ' + maxLength;
        }
    }

    function autogrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight + 2, 132) + 'px';
    }

    /* ---------------- submit flow ---------------- */

    function restoreDraft(value) {
        input.value = value;
        autogrow();
        updateCounter();
    }

    function submitMessage(text, chipTriggered) {
        var value = (text !== undefined && text !== null) ? String(text).trim() : input.value.trim();

        if (!value || busy) {
            return;
        }

        if (!chipTriggered) {
            input.value = '';
            autogrow();
            updateCounter();
        }

        clearError();
        setBusy(true);

        var optimistic = bubbleFor('user', value);
        stage.appendChild(optimistic);

        var thinking = thinkingBubble();
        stage.appendChild(thinking);
        scrollToLatest();

        var payload = {
            conversation_id: conversationId,
            locale: detectedLocale(),
            message: value,
        };

        var controller = new AbortController();
        var timeout = window.setTimeout(function () { controller.abort(); }, 30000);

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            window.clearTimeout(timeout);
            if (response.status === 409 || response.status === 403) {
                return response.json().then(function (body) {
                    return { locked: true, status: response.status, body: body };
                });
            }
            if (!response.ok) {
                return { status: response.status, body: null };
            }
            return response.json();
        }).then(function (data) {
            thinking.remove();

            if (data && data.locked) {
                optimistic.remove();
                var lockedText = (data.body && data.body.message) || 'Beta, yeh baatein ab aage nahi badh sakti.';
                showError(lockedText);
                setBusy(false);
                if (data.status === 409) {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                } else {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1800);
                }
                return;
            }

            if (!data || !data.message || !data.message.content) {
                optimistic.remove();
                restoreDraft(value);
                showError('Beta, Dadi abhi jawab nahi de paayi. Ek pal baad phir se try karein.');
                setBusy(false);
                input.focus();
                return;
            }

            var reply = data.message.content;

            if (data.conversation && data.conversation.id) {
                conversationId = parseInt(data.conversation.id, 10);
                app.setAttribute('data-conversation-id', String(conversationId));
            }

            stage.appendChild(bubbleFor('assistant', reply));
            dismissEmptyState();

            if (data.recommendations && data.recommendations.length) {
                stage.appendChild(recommendationsRow(data.recommendations));
            }

            scrollToLatest();
            setBusy(false);
            input.focus();
        }).catch(function (err) {
            window.clearTimeout(timeout);
            thinking.remove();
            optimistic.remove();
            if (err && err.name === 'AbortError') {
                showError('Beta, Dadi abhi jawab nahi de paayi — thoda waqt zyada lag gaya. Ek baar phir se try karein.');
            } else {
                restoreDraft(value);
                showError('<button type="button" class="btn dadi-error-retry" id="dadiRetry">Dobara try karein</button><span>Baithiye beta, kuch gadbad ho gayi — aapka sandesh nahi bheja gaya.</span>', true);
            }
            setBusy(false);
            input.focus();
        });
    }

    /* ---------------- events ---------------- */

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitMessage();
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                submitMessage();
            }
        });

        input.addEventListener('input', function () {
            updateCounter();
            autogrow();
        });
    }

    if (errorZone) {
        errorZone.addEventListener('click', function (event) {
            if (event.target && event.target.id === 'dadiRetry') {
                submitMessage();
            }
        });
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            submitMessage(chip.getAttribute('data-message'), true);
        });
    });

    /* ---------------- onboarding (first visit) ---------------- */

    var onboarding = document.getElementById('dadiOnboarding');

    if (onboarding) {
        var onboardForm = document.getElementById('dadiOnboardForm');
        var onboardBack = document.getElementById('dadiOnboardBack');
        var onboardNext = document.getElementById('dadiOnboardNext');
        var onboardStart = document.getElementById('dadiOnboardStart');
        var onboardError = document.getElementById('dadiOnboardError');
        var onboardOtherWrap = document.getElementById('dadiOnboardOther');
        var onboardLanguageName = document.getElementById('dadiLanguageName');
        var onboardSteps = Array.prototype.slice.call(onboarding.querySelectorAll('.dadi-onboard-step'));
        var onboardDots = Array.prototype.slice.call(onboarding.querySelectorAll('.dadi-onboard-dot'));
        var onboardRadios = Array.prototype.slice.call(onboarding.querySelectorAll('.dadi-onboard-radio'));
        var onboardingEndpoint = app.getAttribute('data-onboarding-endpoint') || '';

        var onboardStepIndex = 0;

        function onboardShowError(text) {
            if (!onboardError) {
                return;
            }
            onboardError.textContent = text;
            onboardError.hidden = false;
        }

        function onboardClearError() {
            if (onboardError) {
                onboardError.hidden = true;
                onboardError.textContent = '';
            }
        }

        function onboardShowStep(index) {
            if (index < 0 || index >= onboardSteps.length) {
                return;
            }

            onboardStepIndex = index;

            onboardSteps.forEach(function (step, i) {
                step.hidden = i !== onboardStepIndex;
            });

            onboardDots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === onboardStepIndex);
                if (i === onboardStepIndex) {
                    dot.setAttribute('aria-current', 'step');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });

            if (onboardBack) {
                onboardBack.hidden = onboardStepIndex === 0;
            }
            if (onboardNext) {
                onboardNext.hidden = onboardStepIndex === onboardSteps.length - 1;
            }
            if (onboardStart) {
                onboardStart.hidden = onboardStepIndex !== onboardSteps.length - 1;
            }
        }

        function onboardStepValid() {
            if (onboardStepIndex !== 0) {
                return true;
            }

            var selected = onboardRadios.some(function (radio) {
                return radio.checked;
            });

            if (!selected) {
                onboardShowError('Beta, pehle ek bhaasha chun lein — Dadi usi mein baat karengi.');
                return false;
            }

            var isOther = onboardRadios.some(function (radio) {
                return radio.checked && radio.value === 'other';
            });

            if (isOther && onboardLanguageName && onboardLanguageName.value.trim() === '') {
                onboardShowError('Beta, bhaasha ka naam likhein — jaise Marathi, Bengali ya Gujarati.');
                return false;
            }

            return true;
        }

        onboardRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                onboardClearError();
                if (onboardOtherWrap) {
                    onboardOtherWrap.hidden = !(radio.checked && radio.value === 'other');
                }
                if (radio.checked && radio.value !== 'other' && onboardLanguageName) {
                    onboardLanguageName.value = '';
                }
            });
        });

        if (onboardBack) {
            onboardBack.addEventListener('click', function () {
                onboardClearError();
                onboardShowStep(onboardStepIndex - 1);
            });
        }

        if (onboardNext) {
            onboardNext.addEventListener('click', function () {
                if (!onboardStepValid()) {
                    return;
                }
                onboardClearError();
                onboardShowStep(onboardStepIndex + 1);
            });
        }

        if (onboardForm) {
            onboardForm.addEventListener('submit', function (event) {
                event.preventDefault();

                if (!onboardStepValid()) {
                    return;
                }

                onboardClearError();
                onboardStart.disabled = true;

                var data = new FormData(onboardForm);

                fetch(onboardingEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: data,
                }).then(function (response) {
                    if (response.ok) {
                        // Laravel persisted the preference; the reloaded page
                        // reveals the conversation with the language-aware
                        // welcome. Nothing is appended client-side, so no
                        // duplicate state can ever be persisted.
                        window.location.reload();
                        return null;
                    }
                    return response.json().then(function (body) {
                        return { status: response.status, body: (body || {}) };
                    }).catch(function () {
                        return { status: response.status, body: {} };
                    });
                }).then(function (result) {
                    if (result === null) {
                        return;
                    }

                    var errors = result.body.errors || {};
                    var nameErrors = errors.language_name || [];
                    var languageErrors = errors.language || [];

                    if (nameErrors.length) {
                        onboardShowError('Beta, bhaasha ka naam likhein — jaise Marathi, Bengali ya Gujarati.');
                    } else if (languageErrors.length) {
                        onboardShowError('Beta, ek bhaasha chun lein.');
                    } else {
                        onboardShowError('Beta, thodi si gadbad hui — phir se koshish karein.');
                    }
                }).catch(function () {
                    onboardShowError('Beta, thodi si gadbad hui — phir se koshish karein.');
                }).finally(function () {
                    onboardStart.disabled = false;
                });
            });
        }

        onboardShowStep(0);
    }

    /* ---------------- card commerce ---------------- */

    function cardRef(card) {
        return card.getAttribute('data-dadi-ref') || '';
    }

    function cardAddUrl(card) {
        return card.getAttribute('data-add-url') || '';
    }

    function cardVariantId(card) {
        var v = card.getAttribute('data-variant-id');
        return (v !== null && v !== '') ? String(v) : '';
    }

    function statusEl(card) {
        var el = card.querySelector('[data-dadi-status]');
        return el || null;
    }

    function setCardStatus(card, kind, text) {
        var el = statusEl(card);
        if (!el) {
            return;
        }
        el.textContent = text;
        el.classList.toggle('is-success', kind === 'success');
        el.classList.toggle('is-fail', kind === 'fail');
        el.hidden = false;
    }

    function clearCardStatus(card) {
        var el = statusEl(card);
        if (el) {
            el.hidden = true;
            el.className = 'dadi-card-status';
        }
    }

    function updateCartBadge(count) {
        document.querySelectorAll('.js-cart-count').forEach(function (badge) {
            badge.textContent = String(count);
        });
    }

    function fireClickBeacon(card) {
        var ref = cardRef(card);
        if (!ref || !clickEndpoint) {
            return;
        }
        var payload = 'reference=' + encodeURIComponent(ref);
        try {
            fetch(clickEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: payload,
                keepalive: true,
            }).catch(function () { /* best-effort */ });
        } catch (e) { /* best-effort */ }
    }

    function performCommerce(card, buyNow, button) {
        var url = cardAddUrl(card);
        if (!url || button.disabled) {
            return;
        }

        button.disabled = true;
        button.classList.add('is-busy');

        var body = new URLSearchParams();
        body.set('quantity', '1');

        var variantId = cardVariantId(card);
        if (variantId) {
            body.set('variant_id', variantId);
        }

        var ref = cardRef(card);
        if (ref) {
            body.set('dadi_reference', ref);
            var convId = app.getAttribute('data-conversation-id');
            if (convId) {
                body.set('conversation_id', convId);
            }
        }

        if (buyNow) {
            body.set('buy_now', '1');
        }

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, status: response.status, data: (data || {}) };
            }).catch(function () {
                return { ok: false, status: response.status, data: {} };
            });
        }).then(function (result) {
            button.disabled = false;
            button.classList.remove('is-busy');

            if (result.ok && result.data.success) {
                var data = result.data;

                if (typeof data.cartCount !== 'undefined' && data.cartCount !== null) {
                    updateCartBadge(data.cartCount);
                }

                if (data.analytics && window.dataLayer) {
                    window.dataLayer.push(data.analytics);
                }

                if (buyNow && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                setCardStatus(card, 'success', 'Cart mein rakh diya \u2713');
                return;
            }

            setCardStatus(card, 'fail', 'Beta, yeh waala abhi available nahi hai.');
        }).catch(function () {
            button.disabled = false;
            button.classList.remove('is-busy');
            setCardStatus(card, 'fail', 'Beta, yeh waala abhi available nahi hai.');
        });
    }

    if (stage) {
        stage.addEventListener('click', function (event) {
            var target = event.target;

            var link = target.closest ? target.closest('a[data-dadi-click]') : null;
            if (link) {
                var linkCard = link.closest('.dadi-card');
                if (linkCard) {
                    fireClickBeacon(linkCard);
                }
                return;
            }

            var addBtn = target.closest ? target.closest('.js-dadi-add') : null;
            if (addBtn) {
                event.preventDefault();
                var addCard = addBtn.closest('.dadi-card');
                if (addCard) {
                    clearCardStatus(addCard);
                    performCommerce(addCard, false, addBtn);
                }
                return;
            }

            var buyBtn = target.closest ? target.closest('.js-dadi-buy-now') : null;
            if (buyBtn) {
                event.preventDefault();
                var buyCard = buyBtn.closest('.dadi-card');
                if (buyCard) {
                    clearCardStatus(buyCard);
                    performCommerce(buyCard, true, buyBtn);
                }
            }
        });
    }

    updateCounter();
})();