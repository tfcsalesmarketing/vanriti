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

        if (input.type === 'file') {
            var hasFile = !!(input.files && input.files.length);
            if (input.required && !hasFile) {
                var wrapper = input.closest('.mb-3, .col-md-6, .col-md-4, .col-md-8, .col-md-12, .col-12, .col-auto, .col-lg-6');
                var labelText = input.dataset.requiredMsg || '';
                var isFullMsg = !!input.dataset.requiredMsg;
                if (!labelText) {
                    labelText = name.replace(/\[\]/, '');
                    if (wrapper) {
                        var label = wrapper.querySelector('.form-label');
                        if (label) labelText = label.textContent;
                    }
                }
                labelText = labelText.replace('*', '').trim();
                addFieldError(input, isFullMsg ? labelText : labelText + ' is required');
                return false;
            }
            removeFieldError(input);
            input.classList.add('is-valid');
            return true;
        }

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

    // ── Custom Confirm Modal ──
    function vrConfirm(message) {
        return new Promise(function (resolve) {
            var overlay = document.createElement('div');
            overlay.className = 'vr-confirm-overlay';
            overlay.innerHTML =
                '<div class="vr-confirm-dialog">'
                + '<div class="vr-confirm-header">'
                +   '<div class="vr-confirm-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>'
                +   '<h6 class="vr-confirm-title">Are you sure?</h6>'
                + '</div>'
                + '<div class="vr-confirm-body"></div>'
                + '<div class="vr-confirm-actions">'
                +   '<button type="button" class="btn btn-sm btn-light vr-confirm-cancel">Cancel</button>'
                +   '<button type="button" class="btn btn-sm btn-danger vr-confirm-ok">Yes, proceed</button>'
                + '</div>'
                + '</div>';

            overlay.querySelector('.vr-confirm-body').textContent = message;
            document.body.appendChild(overlay);
            requestAnimationFrame(function () { overlay.classList.add('active'); });

            function close(result) {
                document.removeEventListener('keydown', keyHandler);
                overlay.classList.remove('active');
                setTimeout(function () { overlay.remove(); }, 200);
                resolve(result);
            }

            function keyHandler(e) {
                if (e.key === 'Escape') close(false);
                if (e.key === 'Enter') close(true);
            }

            overlay.querySelector('.vr-confirm-ok').addEventListener('click', function () { close(true); });
            overlay.querySelector('.vr-confirm-cancel').addEventListener('click', function () { close(false); });
            overlay.addEventListener('click', function (e) { if (e.target === overlay) close(false); });
            document.addEventListener('keydown', keyHandler);
            overlay.querySelector('.vr-confirm-ok').focus();
        });
    }

    // ── Single Submit Handler (validation + page loader) ──
    function lockFormSubmit(form) {
        form.dataset.preventDouble = '1';
        showPageLoader();
        form.querySelectorAll('[type="submit"]').forEach(function (btn) {
            if (btn.disabled) return;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
        });
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;

        if (form.dataset.preventDouble !== undefined) return;

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
            var target = firstInvalid;
            if (firstInvalid.type === 'file') {
                var dw = firstInvalid.closest('.file-dropzone');
                if (dw) target = dw;
            }
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { firstInvalid.focus({ preventScroll: true }); } catch (err) {}
            showToast('Please fill in all required fields correctly.', 'error');
            return;
        }

        // Custom confirm dialog
        if (form.dataset.confirm) {
            e.preventDefault();
            e.stopImmediatePropagation();
            vrConfirm(form.dataset.confirm).then(function (ok) {
                if (!ok) return;
                if (form.method && form.method.toUpperCase() === 'GET') { form.submit(); return; }
                lockFormSubmit(form);
                form.submit();
            });
            return;
        }

        // Skip loader for GET forms
        if (form.method && form.method.toUpperCase() === 'GET') return;

        // Show page loader + disable buttons
        lockFormSubmit(form);
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

            var isMultiple = !!input.multiple;
            var isDropzone = input.hasAttribute('data-dropzone');

            var zoneBox = null;
            if (isDropzone) {
                wrapper.classList.add('file-dropzone');
                zoneBox = document.createElement('label');
                zoneBox.className = 'file-dropzone-box';
                zoneBox.innerHTML = '<i class="bi bi-cloud-arrow-up"></i>'
                    + '<span class="file-dropzone-title">Click to browse or drag &amp; drop images</span>'
                    + '<small class="file-dropzone-hint">JPG, PNG, WEBP, GIF &middot; up to 5 MB each</small>';
                wrapper.insertBefore(zoneBox, input);
                zoneBox.appendChild(input);

                ['dragenter', 'dragover'].forEach(function (evt) {
                    wrapper.addEventListener(evt, function (e) {
                        e.preventDefault();
                        wrapper.classList.add('drag-over');
                    });
                });
                ['dragleave', 'drop'].forEach(function (evt) {
                    wrapper.addEventListener(evt, function (e) {
                        e.preventDefault();
                        wrapper.classList.remove('drag-over');
                    });
                });
                wrapper.addEventListener('drop', function (e) {
                    e.preventDefault();
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                        input.files = e.dataTransfer.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }

            var preview = document.createElement('div');
            preview.className = 'file-upload-preview';
            preview.innerHTML = '<img src="" alt="Preview"><span class="preview-remove" title="Remove">&times;</span>';
            wrapper.appendChild(preview);

            var multiPreview = document.createElement('div');
            if (isMultiple) multiPreview.className = 'file-upload-thumbs';
            wrapper.appendChild(multiPreview);

            var fileName = document.createElement('div');
            fileName.className = 'file-upload-name';
            wrapper.appendChild(fileName);

            var previewImg = preview.querySelector('img');
            var removeBtn = preview.querySelector('.preview-remove');

            function safeFileName(name) {
                var div = document.createElement('div');
                div.textContent = name;
                return div.textContent;
            }

            var nameMap = new Map();

            function makeThumbCard(file, idx) {
                var isImage = !!(file.type && file.type.startsWith('image/'));

                var thumb = document.createElement('div');
                thumb.className = 'file-upload-thumb';

                if (isImage) {
                    thumb.innerHTML = '<div class="thumb-media">'
                        + '<img alt=""><button type="button" class="preview-remove" title="Remove file">&times;</button>'
                        + '<span class="thumb-name"></span></div>'
                        + '<input type="text" name="secondary_names[]" class="form-control form-control-sm thumb-name-input" maxlength="150" placeholder="Secondary name (optional)">';
                } else {
                    thumb.innerHTML = '<div class="thumb-media thumb-media-file">'
                        + '<i class="bi bi-file-earmark-image"></i>'
                        + '<button type="button" class="preview-remove" title="Remove file">&times;</button>'
                        + '<span class="thumb-name"></span></div>'
                        + '<input type="text" name="secondary_names[]" class="form-control form-control-sm thumb-name-input" maxlength="150" placeholder="Secondary name (optional)">';
                }

                thumb.querySelector('.thumb-name').textContent = safeFileName(file.name);

                var nameInput = thumb.querySelector('.thumb-name-input');
                if (nameMap.has(file)) {
                    nameInput.value = nameMap.get(file);
                }
                nameInput.addEventListener('input', function () {
                    nameMap.set(file, nameInput.value);
                });

                if (isImage) {
                    var reader = new FileReader();
                    reader.onload = function (ev) {
                        thumb.querySelector('img').src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                thumb.querySelector('.preview-remove').addEventListener('click', function () {
                    removeAtIndex(idx);
                });

                return thumb;
            }

            function renderThumbs() {
                multiPreview.innerHTML = '';
                var files = Array.prototype.slice.call(input.files || []);

                files.forEach(function (file, idx) {
                    multiPreview.appendChild(makeThumbCard(file, idx));
                });

                multiPreview.style.display = files.length ? 'flex' : 'none';
                fileName.textContent = files.length
                    ? files.length + ' file(s) selected - add a secondary name to each below'
                    : '';
                fileName.title = files.map(function (f) { return safeFileName(f.name); }).join(', ');
                fileName.classList.toggle('active', files.length > 0);

                if (zoneBox) {
                    zoneBox.style.display = files.length ? 'none' : '';
                    wrapper.classList.toggle('has-files', files.length > 0);
                }
            }

            function removeAtIndex(idx) {
                var files = Array.prototype.slice.call(input.files || []);
                files.splice(idx, 1);

                if (typeof DataTransfer !== 'undefined') {
                    var dt = new DataTransfer();
                    files.forEach(function (f) { dt.items.add(f); });
                    input.files = dt.files;
                } else {
                    input.value = '';
                }

                renderThumbs();
            }

            input.addEventListener('change', function () {
                if (!input.files || !input.files.length) {
                    preview.style.display = 'none';
                    multiPreview.style.display = 'none';
                    fileName.classList.remove('active');
                    if (zoneBox) zoneBox.style.display = '';
                    wrapper.classList.remove('has-files');
                    return;
                }

                if (isMultiple) {
                    preview.style.display = 'none';
                    renderThumbs();
                    if (zoneBox) zoneBox.style.display = 'none';
                    wrapper.classList.add('has-files');
                    return;
                }

                var file = input.files[0];
                fileName.textContent = file.name;
                fileName.title = '';
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
                if (zoneBox) zoneBox.style.display = '';
                wrapper.classList.remove('has-files');
            });
        });
    }

    initFileUploaders();
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(initFileUploaders).observe(document.body, { childList: true, subtree: true });
    }
})();
