(function () {
    'use strict';

    document.querySelectorAll('form').forEach(function (f) { if (!f.closest('.dropdown-menu')) f.setAttribute('novalidate', ''); });

    // ── Sidebar ──
    var sidebar = document.getElementById('adminSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle = document.getElementById('sidebarToggle');

    if (sidebar && backdrop) {
        var showSidebar = function () { sidebar.classList.add('show'); backdrop.classList.add('show'); };
        var hideSidebar = function () { sidebar.classList.remove('show'); backdrop.classList.remove('show'); };
        if (toggle) toggle.addEventListener('click', function () { sidebar.classList.contains('show') ? hideSidebar() : showSidebar(); });
        backdrop.addEventListener('click', hideSidebar);
    }

    // ── Toast auto-dismiss ──
    document.querySelectorAll('.vr-toast.show').forEach(function (toast) {
        setTimeout(function () {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(function () { toast.remove(); }, 350);
        }, 4000);
    });

    document.querySelectorAll('.vr-toast-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var toast = btn.closest('.vr-toast');
            toast.style.transition = 'opacity 0.25s, transform 0.25s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(function () { toast.remove(); }, 280);
        });
    });

    // ── Page Loader ──
    function createPageLoader() {
        if (document.getElementById('vrPageLoader')) return;
        var el = document.createElement('div');
        el.id = 'vrPageLoader';
        el.className = 'vr-page-loader';
        el.innerHTML = '<div class="vr-page-loader-inner"><div class="spinner-border" role="status"></div><p>Saving...</p></div>';
        document.body.appendChild(el);
    }

    function showPageLoader() {
        createPageLoader();
        document.getElementById('vrPageLoader').classList.add('active');
    }

    // ── Toast helper ──
    function createToastContainer() {
        var c = document.createElement('div');
        c.className = 'vr-toast-container';
        c.id = 'vrToasts';
        document.body.appendChild(c);
        return c;
    }

    function showToast(message, type) {
        var container = document.querySelector('.vr-toast-container') || createToastContainer();
        var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
        var toast = document.createElement('div');
        toast.className = 'vr-toast vr-toast-' + type + ' show';
        toast.innerHTML = '<div class="vr-toast-icon"><i class="bi ' + icon + '"></i></div><div class="vr-toast-body">' + message + '</div><button type="button" class="vr-toast-close">&times;</button>';
        container.appendChild(toast);
        toast.querySelector('.vr-toast-close').addEventListener('click', function () {
            toast.style.transition = 'opacity 0.25s, transform 0.25s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            setTimeout(function () { toast.remove(); }, 280);
        });
        setTimeout(function () {
            if (toast.parentNode) {
                toast.style.transition = 'opacity 0.3s, transform 0.3s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(40px)';
                setTimeout(function () { toast.remove(); }, 350);
            }
        }, 4000);
    }

    // ── Live Field Validation ──
    function addFieldError(input, message) {
        removeFieldError(input);
        input.classList.add('is-invalid');
        var errorEl = document.createElement('div');
        errorEl.className = 'vr-field-error show';
        errorEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>' + message;
        input.parentNode.insertBefore(errorEl, input.nextSibling);
    }

    function removeFieldError(input) {
        input.classList.remove('is-invalid');
        var next = input.nextElementSibling;
        if (next && next.classList.contains('vr-field-error')) {
            next.remove();
        }
    }

    function validateField(input) {
        var val = input.value.trim();
        var name = input.getAttribute('name');
        if (!name) return true;

        if (input.required && !val) {
            var wrapper = input.closest('.mb-3, .col-md-6, .col-md-4, .col-md-8, .col-md-12, .col-auto, .col-lg-6');
            var labelText = name;
            if (wrapper) {
                var label = wrapper.querySelector('.form-label');
                if (label) labelText = label.textContent;
            }
            labelText = labelText.replace('*', '').trim();
            addFieldError(input, labelText + ' is required');
            return false;
        }

        if (input.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            addFieldError(input, 'Please enter a valid email');
            return false;
        }

        if (input.type === 'url' && val && !/^https?:\/\/.+/.test(val)) {
            addFieldError(input, 'Enter a valid URL (http:// or https://)');
            return false;
        }

        removeFieldError(input);
        input.classList.add('is-valid');
        return true;
    }

    document.addEventListener('blur', function (e) {
        if (e.target.matches('input[required], select[required], input[type="email"], input[type="url"]')) {
            validateField(e.target);
        }
    }, true);

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('is-invalid')) {
            validateField(e.target);
        }
    }, true);

    // ── Single Submit Handler (validation + page loader) ──
    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (form.dataset.preventDouble !== undefined) return;

        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }

        // Live validation
        var firstInvalid = null;
        form.querySelectorAll('input[required], select[required], input[type="email"], input[type="url"]').forEach(function (input) {
            if (!validateField(input) && !firstInvalid) {
                firstInvalid = input;
            }
        });
        if (firstInvalid) {
            e.preventDefault();
            e.stopImmediatePropagation();
            firstInvalid.focus();
            showToast('Please fill in all required fields correctly.', 'error');
            return;
        }

        // Skip loader for GET forms
        if (form.method && form.method.toUpperCase() === 'GET') return;

        // Show page loader + disable buttons
        form.dataset.preventDouble = '1';
        showPageLoader();

        form.querySelectorAll('[type="submit"]').forEach(function (btn) {
            if (btn.disabled) return;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        });
    }, true);

    // ── Page restore (back button) ──
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('.vr-page-loader.active').forEach(function (el) { el.classList.remove('active'); });
        document.querySelectorAll('[data-prevent-double]').forEach(function (f) { f.removeAttribute('data-prevent-double'); });
        document.querySelectorAll('[data-original-html]').forEach(function (btn) { btn.innerHTML = btn.dataset.originalHtml; btn.disabled = false; });
    });

    // ── File Upload Preview ──
    function initFileUploaders() {
        document.querySelectorAll('input[type="file"]').forEach(function (input) {
            if (input.dataset.uploaderInit) return;
            input.dataset.uploaderInit = '1';

            var wrapper = document.createElement('div');
            wrapper.className = 'file-upload-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var preview = document.createElement('div');
            preview.className = 'file-upload-preview';
            preview.innerHTML = '<img src="" alt="Preview"><span class="preview-remove" title="Remove">&times;</span>';
            wrapper.appendChild(preview);

            var fileName = document.createElement('div');
            fileName.className = 'file-upload-name';
            wrapper.appendChild(fileName);

            var previewImg = preview.querySelector('img');
            var removeBtn = preview.querySelector('.preview-remove');

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) {
                    preview.style.display = 'none';
                    fileName.classList.remove('active');
                    return;
                }
                fileName.textContent = file.name;
                fileName.classList.add('active');
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        previewImg.src = ev.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });

            removeBtn.addEventListener('click', function () {
                input.value = '';
                preview.style.display = 'none';
                fileName.classList.remove('active');
            });
        });
    }

    initFileUploaders();
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(initFileUploaders).observe(document.body, { childList: true, subtree: true });
    }
})();
