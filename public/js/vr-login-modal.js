/**
 * public/js/vr-login-modal.js
 * ---------------------------------------------------------------------------
 * Vanriti Luxury Login & Fast Checkout Modal Controller
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
        passwordUrl: window.vrLoginModal && window.vrLoginModal.passwordUrl,
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

        activatePanel(options.panel || 'otp');
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
            // If neither redirect nor pendingForm was set, reload to reflect authenticated state
            window.location.reload();
            return;
        }
        Login.pendingForm = null;

        var guestMeta = first('meta[name="vr-is-guest"]');
        if (guestMeta) guestMeta.setAttribute('content', '0');

        if (typeof form.submit === 'function') {
            try {
                var submitBtn = form.querySelector('.js-add-cart, button[type="submit"], input[type="submit"]');
                if (submitBtn) submitBtn.disabled = false;

                // Dispatch submit event so AJAX listeners in storefront.js pick it up
                var evt = new Event('submit', { cancelable: true, bubbles: true });
                var prevented = !form.dispatchEvent(evt);
                if (!prevented) {
                    form.submit();
                }
            } catch (e) {
                window.location.reload();
            }
        }
    }

    function refreshToken(csrf) {
        if (csrf) {
            var meta = first('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', csrf);
            var hiddenToken = first('input[name="_token"]');
            if (hiddenToken) hiddenToken.value = csrf;
            if (Login.pendingForm) {
                var t = Login.pendingForm.querySelector('input[name="_token"]');
                if (t) t.value = csrf;
            }
        }
        var guestMeta = first('meta[name="vr-is-guest"]');
        if (guestMeta) guestMeta.setAttribute('content', '0');
    }

    function activatePanel(name) {
        var panels = all('[data-vr-login-panel]');
        for (var i = 0; i < panels.length; i++) {
            panels[i].classList.toggle('d-none', panels[i].getAttribute('data-vr-login-panel') !== name);
        }
        var toggles = all('[data-vr-login-mode]');
        for (var j = 0; j < toggles.length; j++) {
            var isMatch = toggles[j].getAttribute('data-vr-login-mode') === name;
            toggles[j].classList.toggle('active', isMatch);
            toggles[j].setAttribute('aria-selected', isMatch ? 'true' : 'false');
        }
    }

    function showPasswordPanel() { activatePanel('password'); }
    function showOtpPanel() { activatePanel('otp'); }

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
        var errs = all('#vrLoginModal [data-vr-login-error], #vrLoginModal [data-vr-login-otp-error]');
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
                  // Update confirmed phone display
                  var phoneDisplay = byId('vrConfirmedPhoneDisplay');
                  if (phoneDisplay) {
                      phoneDisplay.textContent = '+91 ' + rawPhone;
                  }

                  // Switch to code verification step
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
                  flashError(errSel, (data && data.message) || 'Could not send the code. Please try again.');
              }
          }).catch(function () {
              if (sendBtn) {
                  sendBtn.disabled = false;
                  sendBtn.innerHTML = '<span class="vr-btn-label"><i class="ri-send-plane-2-fill"></i><span>Send WhatsApp Code</span></span>';
              }
              if (resendBtn) resendBtn.disabled = false;
              flashError(errSel, 'Network error. Please try again.');
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
                  flashError(errSel, (data && data.message) || 'The code entered is incorrect or expired.');
                  if (code) {
                      code.focus();
                      code.select();
                  }
              }
          }).catch(function () {
              if (verifyBtn) {
                  verifyBtn.disabled = false;
                  verifyBtn.innerHTML = '<span class="vr-btn-label"><span>Verify &amp; Continue</span><i class="ri-arrow-right-line"></i></span>';
              }
              flashError(errSel, 'Verification failed. Please check connection and try again.');
          });
    }

    // ---- Password submit ----
    function submitPassword() {
        var login = byId('vrLoginModalLogin');
        var pass = byId('vrLoginModalPassword');
        var errSel = '[data-vr-login-error]';
        var errEl = first(errSel);
        if (errEl) { errEl.classList.add('d-none'); errEl.classList.remove('show'); }

        if (!login || !login.value.trim() || !pass || !pass.value) {
            flashError(errSel, 'Please enter your email/mobile and password.');
            return;
        }

        var submitBtn = first('[data-vr-login-submit]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="vr-btn-label"><span class="spinner-border spinner-border-sm me-1"></span><span>Signing in...</span></span>';
        }

        var payload = {
            login: login.value.trim(),
            password: pass.value,
            remember: byId('vrLoginModalRemember') && byId('vrLoginModalRemember').checked ? 1 : 0
        };

        fetch(Login.passwordUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (submitBtn) {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = '<span class="vr-btn-label"><span>Sign In</span><i class="ri-arrow-right-line"></i></span>';
              }
              if (data && (data.result === '1' || data.success)) {
                  refreshToken(data.csrf_token);
                  replay();
              } else {
                  flashError(errSel, (data && data.message) || 'These credentials do not match our records.');
              }
          }).catch(function () {
              if (submitBtn) {
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = '<span class="vr-btn-label"><span>Sign In</span><i class="ri-arrow-right-line"></i></span>';
              }
              flashError(errSel, 'Login failed. Please check connection and try again.');
          });
    }

    function wire() {
        // Tab switching
        var modes = all('[data-vr-login-mode]');
        for (var i = 0; i < modes.length; i++) {
            modes[i].addEventListener('click', function () {
                var name = this.getAttribute('data-vr-login-mode');
                if (name === 'otp') { showOtpPanel(); } else { showPasswordPanel(); }
            });
        }

        // Form submit (password)
        var form = byId('vrLoginModalForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitPassword();
            });
        }

        // OTP actions
        var send = first('[data-vr-login-otp-send]');
        if (send) send.addEventListener('click', function () { sendOtp(); });

        var verify = first('[data-vr-login-otp-verify]');
        if (verify) verify.addEventListener('click', function () { verifyOtp(); });

        // Change number action
        var changePhone = byId('vrChangePhoneBtn');
        if (changePhone) {
            changePhone.addEventListener('click', function () {
                setOtpStep('phone');
                var phone = byId('vrLoginModalOtpPhone');
                if (phone) {
                    phone.focus();
                    phone.select();
                }
            });
        }

        // Resend action
        var resend = byId('vrResendOtpActionBtn');
        if (resend) {
            resend.addEventListener('click', function () {
                sendOtp();
            });
        }

        // Phone input enter key & numeric filter
        var phone = byId('vrLoginModalOtpPhone');
        if (phone) {
            phone.addEventListener('input', function () {
                var clean = phone.value.replace(/\D/g, '');
                if (clean !== phone.value) phone.value = clean;
            });
            phone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendOtp();
                }
            });
        }

        // Code input enter key & auto verify on 6 digits
        var code = byId('vrLoginModalOtpCode');
        if (code) {
            code.addEventListener('input', function () {
                var clean = code.value.replace(/\D/g, '');
                if (clean !== code.value) code.value = clean;
                if (clean.length === 6) {
                    verifyOtp();
                }
            });
            code.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyOtp();
                }
            });
        }

        // Register link in modal closes modal
        var reg = first('[data-vr-login-register]');
        if (reg) {
            reg.addEventListener('click', function () {
                close();
            });
        }

        // Eye toggle for password
        var eye = first('[data-vr-login-eye]');
        if (eye) {
            eye.addEventListener('click', function () {
                var input = byId('vrLoginModalPassword');
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                var icon = eye.querySelector('i');
                if (icon) icon.className = show ? 'ri-eye-off-line' : 'ri-eye-line';
                eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }

        // Global opener triggers: any link/button with data-vr-login-modal-open
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-vr-login-modal-open]');
            if (trigger) {
                // If not middle click or ctrl/cmd click
                if (!e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) {
                    e.preventDefault();
                    open({
                        title: trigger.getAttribute('data-title') || 'Welcome to Vanriti',
                        subtitle: trigger.getAttribute('data-subtitle') || 'Quick sign-in to continue shopping'
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
