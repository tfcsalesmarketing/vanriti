/**
 * public/js/vr-login-modal.js
 * ---------------------------------------------------------------------------
 * Guest-add gate driver — controls the login modal for unauthenticated users.
 * ---------------------------------------------------------------------------
 */
(function () {
    'use strict';

    var modalEl = null;
    var bsModal = null;

    var Login = {
        pendingForm: null,
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
    function modalReady() { ensureModal(); return !!(modalEl && bsModal); }

    function open() {
        ensureModal();
        if (!modalReady()) return;
        activatePanel('password');
        clearAll();
        bsModal.show();
    }

    function close() {
        if (bsModal) bsModal.hide();
    }

    function replay() {
        close();
        var form = Login.pendingForm;
        if (!form) return;
        Login.pendingForm = null;

        var guestMeta = first('meta[name="vr-is-guest"]');
        if (guestMeta) guestMeta.setAttribute('content', '0');

        if (typeof form.submit === 'function') {
            try {
                var submitBtn = form.querySelector('.js-add-cart, button[type="submit"], input[type="submit"]');
                if (submitBtn) submitBtn.disabled = false;
                
                // If it's an AJAX form handler in storefront.js, dispatching submit event replays it
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
            toggles[j].classList.toggle('btn-vr', isMatch);
            toggles[j].classList.toggle('btn-vr-soft-outline', !isMatch);
        }
    }

    function showPasswordPanel() { activatePanel('password'); }
    function showOtpPanel() { activatePanel('otp'); }

    function clearAll() {
        var inputs = all('#vrLoginModal input:not([type="checkbox"]):not([type="hidden"])');
        for (var i = 0; i < inputs.length; i++) { inputs[i].value = ''; }
        var errs = all('#vrLoginModal [data-vr-login-error], #vrLoginModal [data-vr-login-otp-error]');
        for (var j = 0; j < errs.length; j++) {
            errs[j].classList.add('d-none');
            errs[j].classList.remove('show');
            var span = errs[j].querySelector('span');
            if (span) span.textContent = ''; else errs[j].textContent = '';
        }
        var verifyBox = first('.vr-login-otp-verify-wrap') || first('.vr-login-otp-verify');
        if (verifyBox) {
            verifyBox.classList.add('d-none');
            verifyBox.classList.remove('show');
        }
        var sendBtn = first('[data-vr-login-otp-send]');
        if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<i class="ri-send-plane-line"></i> Send WhatsApp Code'; }
    }

    function flashError(sel, message) {
        var el = first(sel);
        if (!el) return;
        var span = el.querySelector('span');
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

        if (!phone || !phone.value.trim()) {
            flashError(errSel, 'Enter your mobile number first.');
            return;
        }

        var sendBtn = first('[data-vr-login-otp-send]');
        if (sendBtn) { sendBtn.disabled = true; sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...'; }

        fetch(Login.otpSendUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ purpose: 'login', phone: phone.value.trim() })
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (sendBtn) sendBtn.disabled = false;
              if (data && (data.result === '1' || data.success)) {
                  if (sendBtn) sendBtn.innerHTML = '<i class="ri-refresh-line"></i> Resend Code';
                  var box = first('.vr-login-otp-verify-wrap') || first('.vr-login-otp-verify');
                  if (box) {
                      box.classList.remove('d-none');
                      box.classList.add('show');
                  }
                  if (window.vrToast) window.vrToast('OTP sent to your WhatsApp.');
              } else {
                  if (sendBtn) sendBtn.innerHTML = '<i class="ri-send-plane-line"></i> Send WhatsApp Code';
                  flashError(errSel, (data && data.message) || 'Could not send the code.');
              }
          }).catch(function () {
              if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<i class="ri-send-plane-line"></i> Send WhatsApp Code'; }
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

        if (!phone || !phone.value.trim() || !code || !code.value.trim()) {
            flashError(errSel, 'Enter your mobile number and 6-digit code.');
            return;
        }

        var verifyBtn = first('[data-vr-login-otp-verify]');
        if (verifyBtn) { verifyBtn.disabled = true; verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...'; }

        fetch(Login.otpVerifyUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ purpose: 'login', phone: phone.value.trim(), code: code.value.trim() })
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (verifyBtn) verifyBtn.disabled = false;
              if (data && (data.result === '1' || data.success)) {
                  refreshToken(data.csrf_token);
                  replay();
              } else {
                  if (verifyBtn) verifyBtn.innerHTML = '<span>Verify &amp; Sign In</span> <i class="ri-arrow-right-line"></i>';
                  flashError(errSel, (data && data.message) || 'The code entered is incorrect.');
              }
          }).catch(function () {
              if (verifyBtn) { verifyBtn.disabled = false; verifyBtn.innerHTML = '<span>Verify &amp; Sign In</span> <i class="ri-arrow-right-line"></i>'; }
              flashError(errSel, 'Verification failed. Please try again.');
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
            flashError(errSel, 'Enter your email/mobile and password.');
            return;
        }

        var submitBtn = first('[data-vr-login-submit]');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Signing in...'; }

        var payload = {
            login: login.value.trim(),
            password: pass.value,
            remember: byId('vrLoginModalRemember') && byId('vrLoginModalRemember').checked ? 1 : 0
        };

        fetch(Login.passwordUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (data) {
              if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<span>Sign In</span> <i class="ri-arrow-right-line"></i>'; }
              if (data && (data.result === '1' || data.success)) {
                  refreshToken(data.csrf_token);
                  replay();
              } else {
                  flashError(errSel, (data && data.message) || 'These credentials do not match our records.');
              }
          }).catch(function () {
              if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<span>Sign In</span> <i class="ri-arrow-right-line"></i>'; }
              flashError(errSel, 'Login failed. Please try again.');
          });
    }

    function wire() {
        var modes = all('[data-vr-login-mode]');
        for (var i = 0; i < modes.length; i++) {
            modes[i].addEventListener('click', function () {
                var name = this.getAttribute('data-vr-login-mode');
                if (name === 'otp') { showOtpPanel(); } else { showPasswordPanel(); }
            });
        }

        var form = byId('vrLoginModalForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitPassword();
            });
        }

        var send = first('[data-vr-login-otp-send]');
        if (send) send.addEventListener('click', function () { sendOtp(); });

        var verify = first('[data-vr-login-otp-verify]');
        if (verify) verify.addEventListener('click', function () { verifyOtp(); });

        var code = byId('vrLoginModalOtpCode');
        if (code) {
            code.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyOtp();
                }
            });
        }

        var reg = first('[data-vr-login-register]');
        if (reg) {
            reg.addEventListener('click', function () {
                close();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wire);
    } else {
        wire();
    }
})();
