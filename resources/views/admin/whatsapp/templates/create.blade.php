@extends('admin.layouts.app')

@section('title', 'Create Meta Template - Vanriti Admin')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp CRM</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.templates.index') }}" class="text-decoration-none">Templates</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: rgba(37, 211, 102, 0.15); color: #25D366;">
                    <i class="bi bi-file-earmark-plus fs-5"></i>
                </span>
                Create Meta Template
            </h1>
            <p class="text-muted small mb-0">Build and submit a new message template directly to Meta for review and approval.</p>
        </div>
        <div>
            <a href="{{ route('admin.whatsapp.templates.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Templates
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-5 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Template Builder Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold mb-0">Template Details & Content</h5>
                </div>
                <div class="card-body p-4 pt-2">
                    <form action="{{ route('admin.whatsapp.templates.store') }}" method="POST" id="templateForm">
                        @csrf

                        <!-- 1. Name & Language -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="name" class="form-label fw-bold small text-dark mb-1">
                                    Template Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="form-control rounded-3 font-monospace @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g. spring_wellness_offer"
                                       pattern="^[a-z0-9_]+$"
                                       required>
                                <div class="form-text small text-muted">Lowercase letters, numbers, and underscores only (no spaces).</div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-5">
                                <label for="language" class="form-label fw-bold small text-dark mb-1">
                                    Language <span class="text-danger">*</span>
                                </label>
                                <select name="language" id="language" class="form-select rounded-3">
                                    <option value="en" {{ old('language', 'en') === 'en' ? 'selected' : '' }}>English (en)</option>
                                    <option value="hi" {{ old('language') === 'hi' ? 'selected' : '' }}>Hindi (hi)</option>
                                    <option value="en_US" {{ old('language') === 'en_US' ? 'selected' : '' }}>English (US)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. Category -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-dark mb-1 d-block">
                                Category <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="category" id="cat_marketing" value="MARKETING" {{ old('category', 'MARKETING') === 'MARKETING' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-dark w-100 rounded-3 text-start p-3" for="cat_marketing">
                                        <div class="fw-bold mb-1"><i class="bi bi-megaphone me-1 text-primary"></i> Marketing</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Promotions, seasonal offers, product launches, reminders.</div>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="category" id="cat_utility" value="UTILITY" {{ old('category') === 'UTILITY' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-dark w-100 rounded-3 text-start p-3" for="cat_utility">
                                        <div class="fw-bold mb-1"><i class="bi bi-receipt me-1 text-success"></i> Utility</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Order confirmations, shipping updates, account alerts.</div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <!-- 3. Header -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-dark mb-1 d-block">Header (Optional)</label>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <input type="radio" class="btn-check" name="header_type" id="hdr_none" value="NONE" {{ old('header_type', 'NONE') === 'NONE' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="hdr_none">None</label>

                                <input type="radio" class="btn-check" name="header_type" id="hdr_text" value="TEXT" {{ old('header_type') === 'TEXT' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="hdr_text"><i class="bi bi-type me-1"></i> Text</label>

                                <input type="radio" class="btn-check" name="header_type" id="hdr_image" value="IMAGE" {{ old('header_type') === 'IMAGE' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="hdr_image"><i class="bi bi-image me-1"></i> Media (Image)</label>

                                <input type="radio" class="btn-check" name="header_type" id="hdr_doc" value="DOCUMENT" {{ old('header_type') === 'DOCUMENT' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="hdr_doc"><i class="bi bi-file-earmark-pdf me-1"></i> Document</label>
                            </div>

                            <div id="headerTextInputWrapper" class="d-none mt-2">
                                <input type="text" 
                                       name="header_text" 
                                       id="header_text" 
                                       maxlength="60" 
                                       class="form-control rounded-3" 
                                       value="{{ old('header_text') }}" 
                                       placeholder="e.g. Exclusive Ayurvedic Care Offer 🌿">
                                <div class="form-text small text-muted">Header headline (max 60 characters).</div>
                            </div>
                        </div>

                        <!-- 4. Body Text -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="body_text" class="form-label fw-bold small text-dark mb-0">
                                    Body Message <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-0.5" id="btnAddParam" style="font-size: 0.75rem;">
                                        <i class="bi bi-plus-lg me-0.5"></i> Insert Parameter
                                    </button>
                                    <span class="text-muted small" id="bodyCounter">0 / 1024</span>
                                </div>
                            </div>
                            <textarea name="body_text" 
                                      id="body_text" 
                                      rows="5" 
                                      maxlength="1024" 
                                      class="form-control rounded-3 @error('body_text') is-invalid @enderror" 
                                      placeholder="Hello {{ '{' . '{1}' . '}' }}, welcome to VANRITI! Enjoy {{ '{' . '{2}' . '}' }} off on your first wellness order using code {{ '{' . '{3}' . '}' }}." 
                                      required>{{ old('body_text') }}</textarea>
                            <div class="form-text small text-muted">
                                Use numbered parameters like <code>{{ '{' . '{1}' . '}' }}</code>, <code>{{ '{' . '{2}' . '}' }}</code> for customer names, coupon codes, and order totals.
                            </div>
                            @error('body_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 5. Footer -->
                        <div class="mb-4">
                            <label for="footer_text" class="form-label fw-bold small text-dark mb-1">
                                Footer Text (Optional)
                            </label>
                            <input type="text" 
                                   name="footer_text" 
                                   id="footer_text" 
                                   maxlength="60" 
                                   class="form-control rounded-3" 
                                   value="{{ old('footer_text', 'Reply STOP to opt out') }}" 
                                   placeholder="e.g. Reply STOP to opt out">
                            <div class="form-text small text-muted">Small disclaimer or brand tagline shown at the bottom (max 60 chars).</div>
                        </div>

                        <!-- 6. Buttons -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-dark mb-1 d-block">Interactive Buttons (Optional)</label>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <input type="radio" class="btn-check" name="button_type" id="btn_none" value="NONE" {{ old('button_type', 'NONE') === 'NONE' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="btn_none">None</label>

                                <input type="radio" class="btn-check" name="button_type" id="btn_qr" value="QUICK_REPLY" {{ old('button_type') === 'QUICK_REPLY' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="btn_qr"><i class="bi bi-chat-dots me-1"></i> Quick Reply</label>

                                <input type="radio" class="btn-check" name="button_type" id="btn_url" value="URL" {{ old('button_type') === 'URL' ? 'checked' : '' }}>
                                <label class="btn btn-sm btn-outline-secondary rounded-pill px-3" for="btn_url"><i class="bi bi-box-arrow-up-right me-1"></i> Visit Website</label>
                            </div>

                            <div id="buttonFieldsWrapper" class="d-none mt-2 p-3 bg-light rounded-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-dark">Button Label</label>
                                        <input type="text" name="button_text" id="button_text" maxlength="25" class="form-control form-control-sm rounded-3" placeholder="e.g. Shop Now" value="{{ old('button_text') }}">
                                    </div>
                                    <div class="col-md-6" id="buttonUrlWrapper">
                                        <label class="form-label small fw-semibold text-dark">Destination URL</label>
                                        <input type="url" name="button_url" id="button_url" class="form-control form-control-sm rounded-3" placeholder="https://vanriti.com/shop" value="{{ old('button_url') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                            <a href="{{ route('admin.whatsapp.templates.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold d-flex align-items-center gap-2" style="background-color: #25D366; border-color: #25D366;">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Submit Template to Meta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Live Phone Mockup Preview -->
        <div class="col-lg-5">
            <div class="position-sticky" style="top: 2rem;">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4" style="background-color: #0b141a;">
                    <!-- Phone App Header -->
                    <div class="p-3 d-flex align-items-center gap-2.5 text-white" style="background-color: #1f2c34;">
                        <i class="bi bi-arrow-left fs-5"></i>
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold fs-6" style="width: 36px; height: 36px;">
                            V
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold small d-flex align-items-center gap-1">
                                VANRITI <i class="bi bi-patch-check-fill text-success" style="font-size: 0.75rem;"></i>
                            </div>
                            <div class="text-white-50" style="font-size: 0.7rem;">Official Business Account</div>
                        </div>
                    </div>

                    <!-- Phone Chat Wallpaper Screen -->
                    <div class="p-4" style="min-height: 380px; background-color: #0b141a; background-image: radial-gradient(#1f2c34 1px, transparent 1px); background-size: 16px 16px;">
                        
                        <!-- Simulated Message Bubble -->
                        <div class="bg-white rounded-3 shadow-sm p-3 text-dark position-relative" style="max-width: 90%; font-size: 0.85rem; line-height: 1.45;">
                            
                            <!-- Header Preview -->
                            <div id="prevHeaderMedia" class="rounded bg-secondary-subtle text-muted text-center py-4 mb-2 d-none">
                                <i class="bi bi-image fs-3 d-block mb-1 text-secondary"></i>
                                <span class="small font-monospace">[Header Image Preview]</span>
                            </div>

                            <div id="prevHeaderText" class="fw-bold text-dark mb-1.5 fs-6 d-none"></div>

                            <!-- Body Text Preview -->
                            <div id="prevBodyText" class="text-secondary mb-2" style="white-space: pre-line;">
                                Hello {{ '{' . '{1}' . '}' }}, your message template text will appear here as you type...
                            </div>

                            <!-- Footer Preview -->
                            <div id="prevFooterText" class="text-muted mt-2 pt-1 border-top" style="font-size: 0.72rem;">
                                Reply STOP to opt out
                            </div>

                            <div class="text-end text-muted mt-1" style="font-size: 0.65rem;">
                                12:00 PM <i class="bi bi-check2 text-muted"></i>
                            </div>

                            <!-- Button Preview -->
                            <div id="prevButtonWrapper" class="mt-2 pt-2 border-top d-none">
                                <div class="btn btn-sm btn-white border w-100 text-primary fw-medium text-center py-1.5 rounded" style="font-size: 0.78rem;">
                                    <i class="bi bi-box-arrow-up-right me-1" id="prevButtonIcon"></i> <span id="prevButtonLabel">Action</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Meta Approval Guidelines Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-light">
                    <div class="card-body p-4 small text-muted">
                        <h6 class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-2">
                            <i class="bi bi-shield-check text-success"></i> Fast Approval Guidelines
                        </h6>
                        <ul class="mb-0 ps-3">
                            <li class="mb-1"><strong>Parameters:</strong> Variables must have surrounding context (e.g. <code>Hello {{ '{' . '{1}' . '}' }}</code> instead of just <code>{{ '{' . '{1}' . '}' }}</code>).</li>
                            <li class="mb-1"><strong>Spelling:</strong> Meta rejects templates containing misspellings, excessive punctuation (<code>!!!</code>), or all-caps shouting.</li>
                            <li class="mb-1"><strong>Links:</strong> Use full domain URLs (<code>https://vanriti.com/shop</code>) rather than link shorteners.</li>
                            <li><strong>Review Time:</strong> Most templates are reviewed automatically within 1 to 15 minutes.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bodyInput = document.getElementById('body_text');
    const headerTextInput = document.getElementById('header_text');
    const footerInput = document.getElementById('footer_text');
    const btnTextInput = document.getElementById('button_text');
    const btnAddParam = document.getElementById('btnAddParam');
    const bodyCounter = document.getElementById('bodyCounter');

    const prevHeaderText = document.getElementById('prevHeaderText');
    const prevHeaderMedia = document.getElementById('prevHeaderMedia');
    const prevBodyText = document.getElementById('prevBodyText');
    const prevFooterText = document.getElementById('prevFooterText');
    const prevButtonWrapper = document.getElementById('prevButtonWrapper');
    const prevButtonLabel = document.getElementById('prevButtonLabel');
    const prevButtonIcon = document.getElementById('prevButtonIcon');

    const headerTextWrapper = document.getElementById('headerTextInputWrapper');
    const buttonFieldsWrapper = document.getElementById('buttonFieldsWrapper');
    const buttonUrlWrapper = document.getElementById('buttonUrlWrapper');

    function updatePreview() {
        // Body
        let body = bodyInput.value.trim() || "Hello {{1}}, your template text will appear here as you type...";
        prevBodyText.innerHTML = escapeHtml(body).replace(/\{\{(\d+)\}\}/g, '<span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace py-0.5 px-1.5">{$1}</span>');
        bodyCounter.textContent = bodyInput.value.length + ' / 1024';

        // Header
        const headerType = document.querySelector('input[name="header_type"]:checked')?.value || 'NONE';
        if (headerType === 'TEXT') {
            headerTextWrapper.classList.remove('d-none');
            prevHeaderText.textContent = headerTextInput.value.trim() || 'Headline';
            prevHeaderText.classList.remove('d-none');
            prevHeaderMedia.classList.add('d-none');
        } else if (headerType === 'IMAGE' || headerType === 'DOCUMENT') {
            headerTextWrapper.classList.add('d-none');
            prevHeaderText.classList.add('d-none');
            prevHeaderMedia.classList.remove('d-none');
        } else {
            headerTextWrapper.classList.add('d-none');
            prevHeaderText.classList.add('d-none');
            prevHeaderMedia.classList.add('d-none');
        }

        // Footer
        const footer = footerInput.value.trim();
        if (footer) {
            prevFooterText.textContent = footer;
            prevFooterText.classList.remove('d-none');
        } else {
            prevFooterText.classList.add('d-none');
        }

        // Button
        const btnType = document.querySelector('input[name="button_type"]:checked')?.value || 'NONE';
        if (btnType !== 'NONE') {
            buttonFieldsWrapper.classList.remove('d-none');
            if (btnType === 'URL') {
                buttonUrlWrapper.classList.remove('d-none');
                prevButtonIcon.className = 'bi bi-box-arrow-up-right me-1';
            } else {
                buttonUrlWrapper.classList.add('d-none');
                prevButtonIcon.className = 'bi bi-chat-dots me-1';
            }
            prevButtonLabel.textContent = btnTextInput.value.trim() || 'Action';
            prevButtonWrapper.classList.remove('d-none');
        } else {
            buttonFieldsWrapper.classList.add('d-none');
            prevButtonWrapper.classList.add('d-none');
        }
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Add variable helper
    if (btnAddParam) {
        btnAddParam.addEventListener('click', function () {
            const matches = bodyInput.value.match(/\{\{(\d+)\}\}/g) || [];
            const nextIdx = matches.length + 1;
            const token = '{{' + nextIdx + '}}';

            const start = bodyInput.selectionStart;
            const end = bodyInput.selectionEnd;
            const text = bodyInput.value;

            bodyInput.value = text.substring(0, start) + token + text.substring(end);
            bodyInput.selectionStart = bodyInput.selectionEnd = start + token.length;
            bodyInput.focus();
            updatePreview();
        });
    }

    bodyInput.addEventListener('input', updatePreview);
    headerTextInput.addEventListener('input', updatePreview);
    footerInput.addEventListener('input', updatePreview);
    btnTextInput.addEventListener('input', updatePreview);

    document.querySelectorAll('input[name="header_type"]').forEach(r => r.addEventListener('change', updatePreview));
    document.querySelectorAll('input[name="button_type"]').forEach(r => r.addEventListener('change', updatePreview));

    updatePreview();
});
</script>
@endsection
