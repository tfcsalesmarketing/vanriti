/**
 * vr-whatsapp-otp.js  v2
 * Shared WhatsApp / SMS OTP client for the storefront auth pages.
 *
 * Exposes a single global:
 *
 *   window.vrWhatsAppOtp = {
 *       send({ phoneEl, btn, purpose, onSent, onError [,url] })
 *       verify({ phoneEl, codeEl, btn, purpose, onVerified, onError [,url] })
 *       cooldown({ seconds, btn, label })   // 1s ticker + re-enable
 *       setEnabled(bool)                    // gate the "Send code" button
 *   }
 *
 * The blade injects routes once via:
 *   window.vrOtpConfig = { sendUrl: '...', verifyUrl: '...' };
 *
 * Both login and register blades call these helpers; implementation lives here
 * so the two-step OTP UX shares one code path.
 *
 * Backend contract (OtpController):
 *   send   → { result: '1', message: '...' }  on success
 *   verify → { result: '1', message: '...' }  on success; includes redirect on login
 */
(function (window, document) {
    'use strict';

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setBusy(btn, busy, text) {
        if (!btn) return;
        var original = btn.getAttribute('data-vr-original-label');
        if (!original) {
            original = btn.textContent.trim();
            btn.setAttribute('data-vr-original-label', original);
        }
        btn.disabled = busy;
        btn.textContent = busy ? (text || original) : original;
    }

    function post(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().then(function (data) {
                if (!r.ok) {
                    // Surface the server-side message even on 4xx/5xx
                    throw { status: r.status, data: data };
                }
                return data;
            });
        });
    }

    function digitsOnly(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function resolveUrl(opts, key) {
        // Allow explicit url per call or fall back to the global config object
        var config = window.vrOtpConfig || {};
        return opts[key] || (key === 'sendUrl' ? config.sendUrl : config.verifyUrl) || '';
    }

    /* ── Public API ───────────────────────────────────────────────────────── */

    window.vrWhatsAppOtp = {

        /**
         * Send OTP.
         * opts: { phoneEl, btn, purpose, onSent, onError [, sendUrl] }
         */
        send: function (opts) {
            if (!opts || !opts.phoneEl || !opts.btn) {
                console.error('vrWhatsAppOtp.send: phoneEl and btn are required.');
                return;
            }

            var url = resolveUrl(opts, 'sendUrl');
            if (!url) {
                console.error('vrWhatsAppOtp.send: no sendUrl found. Set window.vrOtpConfig.sendUrl.');
                if (opts.onError) opts.onError('Configuration error. Please refresh the page.');
                return;
            }

            var phone = digitsOnly(opts.phoneEl.value);
            if (phone.length < 10) {
                if (opts.onError) opts.onError('Enter a valid 10-digit mobile number.');
                return;
            }

            setBusy(opts.btn, true, 'Sending…');

            post(url, {
                phone: phone,
                purpose: opts.purpose || 'login'
            }).then(function (data) {
                setBusy(opts.btn, false);

                // Backend returns { result: '1' } on success
                if (String(data.result) === '1') {
                    // Auto-start the 120s resend cooldown
                    window.vrWhatsAppOtp.cooldown({
                        seconds: 120,
                        btn: opts.btn,
                        label: opts.btn.getAttribute('data-vr-original-label') || 'Send OTP'
                    });
                    if (opts.onSent) opts.onSent(data);
                } else {
                    if (opts.onError) opts.onError(data.message || 'Could not send the code. Try again.');
                }
            }).catch(function (err) {
                setBusy(opts.btn, false);
                var msg = (err && err.data && err.data.message)
                    ? err.data.message
                    : 'Network error. Please try again.';
                if (opts.onError) opts.onError(msg);
            });
        },

        /**
         * Verify OTP.
         * opts: { phoneEl, codeEl, btn, purpose, onVerified, onError [, verifyUrl] }
         */
        verify: function (opts) {
            if (!opts || !opts.phoneEl || !opts.codeEl || !opts.btn) {
                console.error('vrWhatsAppOtp.verify: phoneEl, codeEl and btn are required.');
                return;
            }

            var url = resolveUrl(opts, 'verifyUrl');
            if (!url) {
                console.error('vrWhatsAppOtp.verify: no verifyUrl found. Set window.vrOtpConfig.verifyUrl.');
                if (opts.onError) opts.onError('Configuration error. Please refresh the page.');
                return;
            }

            var code = String(opts.codeEl.value || '').trim();
            if (!/^\d{6}$/.test(code)) {
                if (opts.onError) opts.onError('Enter the 6-digit code.');
                return;
            }

            setBusy(opts.btn, true, 'Verifying…');

            post(url, {
                phone: digitsOnly(opts.phoneEl.value),
                code: code,
                purpose: opts.purpose || 'login'
            }).then(function (data) {
                setBusy(opts.btn, false);

                // Backend returns { result: '1' } on success
                if (String(data.result) === '1') {
                    if (opts.onVerified) opts.onVerified(data);
                } else {
                    if (opts.onError) opts.onError(data.message || 'That code is incorrect. Try again.');
                }
            }).catch(function (err) {
                setBusy(opts.btn, false);
                var msg = (err && err.data && err.data.message)
                    ? err.data.message
                    : 'Network error. Please try again.';
                if (opts.onError) opts.onError(msg);
            });
        },

        /**
         * Countdown timer on the send button.
         * opts: { seconds, btn, label }
         */
        cooldown: function (opts) {
            if (!opts || !opts.btn) return;
            var remaining = parseInt(opts.seconds, 10) || 120;
            var label = opts.label || opts.btn.getAttribute('data-vr-original-label') || 'Send OTP';
            opts.btn.disabled = true;
            (function tick() {
                if (remaining > 0) {
                    opts.btn.textContent = 'Resend in ' + remaining + 's';
                    remaining -= 1;
                    setTimeout(tick, 1000);
                } else {
                    opts.btn.disabled = false;
                    opts.btn.textContent = label;
                }
            })();
        },

        setEnabled: function (enabled) {
            var phone = document.getElementById('vrOtpPhone') || document.getElementById('vrRegOtpPhone');
            var send  = document.getElementById('vrOtpSend')  || document.getElementById('vrRegOtpSend');
            if (phone) phone.disabled = !enabled;
            if (send)  send.disabled  = !enabled;
        }
    };

})(window, document);
