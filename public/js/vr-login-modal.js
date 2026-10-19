/**
 * public/js/vr-login-modal.js
 * ---------------------------------------------------------------------------
 * Vanriti Luxury Login Modal Controller
 * WhatsApp OTP fast login only. Password login & registration live on the
 * full auth pages (login / register).
 * ---------------------------------------------------------------------------
 */
(function () {
    'use strict';

    var modalEl = null;
    var bsModal = null;

    var Login = {
        pendingForm: null,
        pendingRedirect: null,
        currentPurpose: 'fast_checkout',
        open: open,
        close: close,
        replay: replay,
        registerUrl: window.vrLoginModal && window.vrLoginModal.registerUrl,
        otpSendUrl: (window.vrOtpConfig && window.vrOtpConfig.sendUrl) || '',
        otpVerifyUrl: (window.vrOtpConfig && window.vrOtpConfig.verifyUrl) || ''
    };

    window.vrLoginModal = Login;

    function byId(id) { return document.getElementById(id); }
    function first(sel) { return document.querySelector(sel); }
    function all(sel) { return document.querySelectorAll(sel); }

    function csrfToken() {
        var meta = first('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function ensureModal() {
        if (modalEl && bsModal) return;
        modalEl = byId('vrLoginModal');
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: 'static',
                keyboard: true
            });
        }
    }

    function modalReady() {
        ensureModal();
        return !!(modalEl && bsModal);
    }

    function open(options) {
        ensureModal();
        if (!modalReady()) return;
        options = options || {};
        Login.pendingRedirect = options.redirect || null;
        Login.currentPurpose = options.purpose || 'fast_checkout';

        var titleEl = byId('vrLoginModalTitle');
        if (titleEl && options.title) {
            titleEl.textContent = options.title;
        }

        var subtitleEl = byId('vrLoginModalSubtitle');
        if (subtitleEl && options.subtitle) {
            subtitleEl.textContent = options.subtitle;
        }

        clearAll();
        bsModal.show();
    }

    function close() {
        if (bsModal) bsModal.hide();
    }

    function replay(redirectUrl) {
        close();
        var target = redirectUrl || Login.pendingRedirect;
        if (target) {
            Login.pendingRedirect = null;
            Login.pendingForm = null;
            window.location.href = target;
            return;
        }

        var form = Login.pendingForm;
        if (!form) {
            window.location.reload();
            return;
        }
        Login.pendingForm = null;

        var guestMeta = first('meta[name="vr-is-guest"]');
        if (guestMeta) guestMeta.setAttribute('content', '0');

        try {
            var submitBtn = form.querySelector('.js-add-to-cart-btn, button[type="submit"], button');
            if (submitBtn) submitBtn.disabled = false;
            var evt = new Event('submit', { bubbles: true, cancelable: true });
            var prevented = !form.dispatchEvent(evt);
            if (!prevented) form.submit();
        } catch (e) {
            window.location.reload();
        }
    }

    function refreshToken(csrf) {
        if (!csrf) return;
        var meta = first('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', csrf);
        var hiddenToken = first('input[name="_token"]');
        if (hiddenToken) hiddenToken.value = csrf;
        if (Login.pendingForm) {
            var t = Login.pendingForm.querySelector('input[name="_token"]');
            if (t) t.value = csrf;
        }
    }

    function setOtpStep(step) {
        var phoneStep = first('[data-vr-otp-step="phone"]');
        var codeStep = first('[data-vr-otp-step="code"]');
        if (step === 'code') {
            if (phoneStep) phoneStep.classList.add('d-none');
            if (codeStep) {
                codeStep.classList.remove('d-none');
                codeStep.classList.add('show');
            }
        } else {
            if (phoneStep) phoneStep.classList.remove('d-none');
            if (codeStep) {
                codeStep.classList.add('d-none');
                codeStep.classList.remove('show');
            }
        }
    }

    function clearAll() {
        var inputs = all('#vrLoginModal input:not([type="checkbox"]):not([type="hidden"])');
        for (var i = 0; i < inputs.length; i++) { inputs[i].value = ''; }
        var errs = all('#vrLoginModal [data-vr-login-otp-error]');
        for (var j = 0; j < errs.length; j++) {
            errs[j].classList.add('d-none');
            errs[j].classList.remove('show');
            var span = errs[j].querySelector('.vr-alert-msg') || errs[j].querySelector('span');
            if (span) span.textContent = ''; else errs[j].textContent = '';
        }
        setOtpStep('phone');
        var sendBtn = first('[data-vr-login-otp-send]');
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<span class="vr-btn-label"><i class="ri-send-plane-2-fill"></i><span>Send WhatsApp Code</span></span>';
        }
        var verifyBtn = first('[data-vr-login-otp-verify]');
        if (verifyBtn) {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<span class="vr-btn-label"><span>Verify &amp; Continue</span><i class="ri-arrow-right-line"></i></span>';
        }
    }

    function flashError(sel, message) {
        var el = first(sel);
        if (!el) return;
        var span = el.querySelector('.vr-alert-msg') || el.querySelector('span');
        if (span) { span.textContent = message; } else { el.textContent = message; }
        el.classList.remove('d-none');
        el.classList.add('show');
    }

    // ---- WhatsApp OTP: send code ----
    function sendOtp() {
        var phone = byId('vrLoginModalOtpPhone');
        var errSel = '[data-vr-login-otp-error]';
        var errEl = first(errSel);
        if (errEl) { errEl.classList.add('d-none'); errEl.classList.remove('show'); }

        var rawPhone = phone ? phone.value.trim().replace(/\D/g, '') : '';
        if (!rawPhone || rawPhone.length < 10) {
            flashError(errSel, 'Please enter a valid 10-digit mobile number.');
            if (phone) phone.focus();
            return;
        }

        var sendBtn = first('[data-vr-login-otp-send]');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="vr-btn-label"><span class="spinner-border spinner-border-sm me-1"></span><span>Sending Code...</span></span>';
        }

        var resendBtn = byId('vrResendOtpActionBtn');
        if (resendBtn) resendBtn.disabled = true;

        var purpose = Login.currentPurpose || 'fast_checkout';

        fetch(Login.otpSendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ purpose: purpose, phone: rawPhone })
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (sendBtn) sendBtn.disabled = false;
              if (resendBtn) resendBtn.disabled = false;
              if (data && (data.result === '1' || data.success)) {
                  var phoneDisplay = byId('vrConfirmedPhoneDisplay');
                  if (phoneDisplay) {
                      phoneDisplay.textContent = '+91 ' + rawPhone;
                  }

                  setOtpStep('code');

                  var codeInput = byId('vrLoginModalOtpCode');
                  if (codeInput) {
                      codeInput.value = '';
                      setTimeout(function () { codeInput.focus(); }, 150);
                  }
                  if (window.vrToast) window.vrToast('Code sent to your WhatsApp.');
              } else {
                  if (sendBtn) {
                      sendBtn.innerHTML = '<span class="vr-btn-label"><i class="ri-send-plane-2-fill"></i><span>Send WhatsApp Code</span></span>';
                  }
                  flashError('[data-vr-login-otp-error]', (data && data.message) || 'Could not send the code. Please try again.');
              }
          }).catch(function () {
              if (sendBtn) {
                  sendBtn.disabled = false;
                  sendBtn.innerHTML = '<span class="vr-btn-label"><i class="ri-send-plane-2-fill"></i><span>Send WhatsApp Code</span></span>';
              }
              if (resendBtn) resendBtn.disabled = false;
              flashError('[data-vr-login-otp-error]', 'Network error. Please try again.');
          });
    }

    // ---- WhatsApp OTP: verify code ----
    function verifyOtp() {
        var phone = byId('vrLoginModalOtpPhone');
        var code = byId('vrLoginModalOtpCode');
        var errSel = '[data-vr-login-otp-error]';
        var errEl = first(errSel);
        if (errEl) { errEl.classList.add('d-none'); errEl.classList.remove('show'); }

        var rawPhone = phone ? phone.value.trim().replace(/\D/g, '') : '';
        var rawCode = code ? code.value.trim().replace(/\D/g, '') : '';

        if (!rawPhone || rawPhone.length < 10) {
            flashError(errSel, 'Enter your valid 10-digit mobile number.');
            setOtpStep('phone');
            return;
        }

        if (!rawCode || rawCode.length !== 6) {
            flashError(errSel, 'Please enter the complete 6-digit code received on WhatsApp.');
            if (code) code.focus();
            return;
        }

        var verifyBtn = first('[data-vr-login-otp-verify]');
        if (verifyBtn) {
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="vr-btn-label"><span class="spinner-border spinner-border-sm me-1"></span><span>Verifying...</span></span>';
        }

        var purpose = Login.currentPurpose || 'fast_checkout';

        fetch(Login.otpVerifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ purpose: purpose, phone: rawPhone, code: rawCode })
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (verifyBtn) verifyBtn.disabled = false;
              if (data && (data.result === '1' || data.success)) {
                  refreshToken(data.csrf_token);
                  replay(data.redirect || Login.pendingRedirect);
              } else {
                  if (verifyBtn) {
                      verifyBtn.innerHTML = '<span class="vr-btn-label"><span>Verify &amp; Continue</span><i class="ri-arrow-right-line"></i></span>';
                  }
                  flashError('[data-vr-login-otp-error]', (data && data.message) || 'Invalid or expired code. Please try again.');
              }
          }).catch(function () {
              if (verifyBtn) {
                  verifyBtn.disabled = false;
                  verifyBtn.innerHTML = '<span class="vr-btn-label"><span>Verify &amp; Continue</span><i class="ri-arrow-right-line"></i></span>';
              }
              flashError('[data-vr-login-otp-error]', 'Network error. Please try again.');
          });
    }

    function wire() {
        // OTP actions
        var send = first('[data-vr-login-otp-send]');
        if (send) send.addEventListener('click', function () { sendOtp(); });

        var verify = first('[data-vr-login-otp-verify]');
        if (verify) verify.addEventListener('click', function () { verifyOtp(); });

        var changePhone = byId('vrChangePhoneBtn');
        if (changePhone) {
            changePhone.addEventListener('click', function () {
                setOtpStep('phone');
                var phone = byId('vrLoginModalOtpPhone');
                if (phone) phone.focus();
            });
        }

        var resend = byId('vrResendOtpActionBtn');
        if (resend) resend.addEventListener('click', function () { sendOtp(); });

        var phone = byId('vrLoginModalOtpPhone');
        if (phone) {
            phone.addEventListener('input', function () {
                var clean = phone.value.replace(/\D/g, '');
                if (clean !== phone.value) phone.value = clean;
            });
            phone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); sendOtp(); }
            });
        }

        var code = byId('vrLoginModalOtpCode');
        if (code) {
            code.addEventListener('input', function () {
                var clean = code.value.replace(/\D/g, '');
                if (clean !== code.value) code.value = clean;
                if (clean.length === 6) verifyOtp();
            });
            code.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); verifyOtp(); }
            });
        }

        // Register link in modal closes modal before navigating
        var reg = first('[data-vr-login-register]');
        if (reg) {
            reg.addEventListener('click', function () { close(); });
        }

        // Global opener triggers: any link/button with data-vr-login-modal-open
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-vr-login-modal-open]');
            if (trigger) {
                if (!e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) {
                    e.preventDefault();
                    open({
                        title: trigger.getAttribute('data-title') || undefined,
                        subtitle: trigger.getAttribute('data-subtitle') || undefined,
                        purpose: trigger.getAttribute('data-vr-login-purpose') || undefined
                    });
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wire);
    } else {
        wire();
    }
})();
