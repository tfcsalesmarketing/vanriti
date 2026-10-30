@extends('admin.layouts.app')

@section('title', 'Create TFC WhatsApp Broadcast Campaign')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.campaigns.index') }}" class="text-decoration-none">Campaigns</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </nav>
    <h3 class="fw-bold mb-0 text-dark">
        <i class="bi bi-broadcast text-success me-2"></i>Create TFC WhatsApp Broadcast
    </h3>
    <p class="text-muted small">Configure your direct-selling audience segment, select an approved Meta template, and dispatch broadcast messages.</p>
</div>

<form method="POST" action="{{ route('admin.tfc.whatsapp.campaigns.store') }}" id="campaignForm">
    @csrf
    <div class="row g-4">
        {{-- ============================================================== --}}
        {{-- LEFT COLUMN: Campaign Settings & Audience                      --}}
        {{-- ============================================================== --}}
        <div class="col-12 col-lg-7">
            {{-- Step 1: Campaign Details --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3"><span class="badge bg-dark rounded-circle me-2">1</span>Campaign Details</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Campaign Title <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Q4 Direct Seller Product Launch &amp; Orientation" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Internal Campaign Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional internal notes for tracking campaign objectives...">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Step 2: Target Audience --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3"><span class="badge bg-dark rounded-circle me-2">2</span>Target Audience Segment</h5>
                
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="card p-3 border rounded-3 h-100 cursor-pointer audience-card">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <input type="radio" name="target_segment" value="tfc_inquiries_all" class="form-check-input" checked>
                                <strong class="small text-dark">All Seller Inquiries</strong>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Est. {{ number_format($estimates['tfc_inquiries_all'] ?? 0) }} contacts</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="card p-3 border rounded-3 h-100 cursor-pointer audience-card">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <input type="radio" name="target_segment" value="tfc_inquiries_approved" class="form-check-input">
                                <strong class="small text-dark">Approved Sellers</strong>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Est. {{ number_format($estimates['tfc_inquiries_approved'] ?? 0) }} active distributors</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="card p-3 border rounded-3 h-100 cursor-pointer audience-card">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <input type="radio" name="target_segment" value="tfc_inquiries_pending" class="form-check-input">
                                <strong class="small text-dark">Pending Inquiries</strong>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Est. {{ number_format($estimates['tfc_inquiries_pending'] ?? 0) }} follow-ups</span>
                        </label>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="card p-3 border rounded-3 h-100 cursor-pointer audience-card">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <input type="radio" name="target_segment" value="registered_customers" class="form-check-input">
                                <strong class="small text-dark">Registered Customers</strong>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Est. {{ number_format($estimates['registered_customers'] ?? 0) }} verified users</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="card p-3 border rounded-3 cursor-pointer audience-card">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <input type="radio" name="target_segment" value="custom_numbers" class="form-check-input" id="radioCustomNumbers">
                                <strong class="small text-dark">Custom Phone Number List</strong>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Enter phone numbers manually or paste from spreadsheet</span>
                        </label>
                    </div>
                </div>

                {{-- Custom Numbers Textarea --}}
                <div id="customNumbersContainer" class="mt-2" style="display: none;">
                    <label class="form-label small fw-semibold text-dark">Paste Phone Numbers (one per line or comma-separated)</label>
                    <textarea name="custom_numbers" class="form-control" rows="3" placeholder="919876543210&#10;919812345678"></textarea>
                    <div class="form-text small">Include 91 country code without + or spaces.</div>
                </div>
            </div>

            {{-- Step 3: Meta Template Selection & Dynamic Variables --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3"><span class="badge bg-dark rounded-circle me-2">3</span>Meta Approved Template</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Select Template <span class="text-danger">*</span></label>
                    <select name="template_name" id="campaignTemplateSelector" class="form-select" required>
                        <option value="">-- Choose Approved Template --</option>
                        @foreach ($approvedTemplates as $tmpl)
                            <option value="{{ $tmpl->name }}"
                                    data-lang="{{ $tmpl->language }}"
                                    data-body="{{ $tmpl->body_text }}"
                                    data-header="{{ $tmpl->header_text }}"
                                    data-headertype="{{ $tmpl->header_type }}"
                                    data-vars="{{ $tmpl->variable_count }}">
                                {{ $tmpl->name }} ({{ $tmpl->language }} - {{ $tmpl->category }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="template_lang" id="templateLangInput" value="en">

                {{-- Header Image (If template supports image header) --}}
                <div class="mb-3" id="headerImageContainer" style="display: none;">
                    <label class="form-label fw-semibold text-dark small">Header Banner Image HTTPS URL</label>
                    <input type="url" name="header_image_url" id="headerImageUrl" class="form-control" placeholder="https://tfcsalesmarketing.com/images/banner.jpg">
                    <div class="form-text small">Must be a direct, publicly accessible HTTPS image URL.</div>
                </div>

                {{-- Dynamic Parameters inputs --}}
                <div id="campaignVariablesContainer" style="display: none;">
                    <label class="form-label fw-semibold text-dark small mb-2">Dynamic Template Variables</label>
                    <div class="alert alert-info py-2 px-3 small mb-2">
                        <i class="bi bi-info-circle me-1"></i> You can use <code>{name}</code> to automatically insert each recipient's name!
                    </div>
                    <div id="campaignVariablesList" class="row g-2"></div>
                </div>
            </div>

            {{-- Step 4: Dispatch or Schedule --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold text-dark mb-3"><span class="badge bg-dark rounded-circle me-2">4</span>Launch Settings</h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small">Schedule for Future (Optional)</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                    <div class="form-text small">Leave blank to dispatch immediately upon saving.</div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 pt-2">
                    <a href="{{ route('admin.tfc.whatsapp.campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success px-4" id="btnLaunchCampaign">
                        <i class="bi bi-send-fill me-1"></i> Launch Broadcast Campaign
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT COLUMN: Live Mobile Phone Mockup Preview                 --}}
        {{-- ============================================================== --}}
        <div class="col-12 col-lg-5">
            <div class="position-sticky" style="top: 20px;">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-phone me-1"></i>Live WhatsApp Message Preview</h6>

                    {{-- Phone Shell --}}
                    <div class="mx-auto rounded-5 shadow-lg p-3 text-start position-relative"
                         style="width: 320px; min-height: 520px; background: #0A1D37; border: 10px solid #1E293B;">
                        
                        {{-- Top Speaker Notch --}}
                        <div class="rounded-pill mx-auto mb-3" style="width: 70px; height: 5px; background: #334155;"></div>

                        {{-- Screen Header --}}
                        <div class="p-2 rounded-3 d-flex align-items-center gap-2 mb-3" style="background: #132D54; color: #FFFFFF;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                 style="width: 32px; height: 32px; background: #D49B24; font-size: 0.75rem;">
                                TFC
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold small text-truncate">TFC Sales &amp; Marketing</div>
                                <div class="text-white-50" style="font-size: 0.65rem;">Official Business Account &bull; Verified</div>
                            </div>
                        </div>

                        {{-- WhatsApp Message Bubble Mockup --}}
                        <div class="rounded-3 p-3 shadow-sm bg-white text-dark mb-3" style="font-size: 0.82rem; border-left: 4px solid #D49B24;">
                            {{-- Image header preview --}}
                            <div id="mockupImageWrap" class="mb-2 rounded overflow-hidden" style="display: none;">
                                <img src="" id="mockupImg" class="img-fluid rounded w-100" style="max-height: 140px; object-fit: cover;">
                            </div>
                            
                            {{-- Header text preview --}}
                            <div id="mockupHeader" class="fw-bold mb-1 text-dark" style="display: none;"></div>

                            {{-- Body text preview --}}
                            <div id="mockupBody" class="text-secondary" style="white-space: pre-wrap;">Select a template on the left to preview message copy...</div>

                            <div class="text-end text-muted mt-2" style="font-size: 0.65rem;">
                                <span>12:00 PM</span> <i class="bi bi-check2 text-muted"></i>
                            </div>
                        </div>

                        {{-- Direct Selling Statutory Trust Badge Mockup --}}
                        <div class="p-2 rounded bg-white bg-opacity-10 text-white-50 text-center" style="font-size: 0.65rem;">
                            <i class="bi bi-shield-check text-warning me-1"></i>Consumer Protection Direct Selling Compliant
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radioCustom = document.getElementById('radioCustomNumbers');
    const customContainer = document.getElementById('customNumbersContainer');
    const templateSelector = document.getElementById('campaignTemplateSelector');
    const templateLangInput = document.getElementById('templateLangInput');
    const headerImgContainer = document.getElementById('headerImageContainer');
    const headerImgUrl = document.getElementById('headerImageUrl');
    const varsContainer = document.getElementById('campaignVariablesContainer');
    const varsList = document.getElementById('campaignVariablesList');

    // Mockup elements
    const mockupImgWrap = document.getElementById('mockupImageWrap');
    const mockupImg = document.getElementById('mockupImg');
    const mockupHeader = document.getElementById('mockupHeader');
    const mockupBody = document.getElementById('mockupBody');

    // Toggle custom numbers
    document.querySelectorAll('input[name="target_segment"]').forEach(radio => {
        radio.addEventListener('change', function () {
            customContainer.style.display = (this.value === 'custom_numbers') ? 'block' : 'none';
        });
    });

    // Template selection and mockup update
    templateSelector.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) {
            mockupBody.textContent = 'Select a template on the left to preview message copy...';
            mockupHeader.style.display = 'none';
            mockupImgWrap.style.display = 'none';
            headerImgContainer.style.display = 'none';
            varsContainer.style.display = 'none';
            return;
        }

        templateLangInput.value = opt.dataset.lang || 'en';

        // Header Type Image check
        if (opt.dataset.headertype === 'IMAGE') {
            headerImgContainer.style.display = 'block';
        } else {
            headerImgContainer.style.display = 'none';
            mockupImgWrap.style.display = 'none';
        }

        // Header Text check
        if (opt.dataset.header) {
            mockupHeader.textContent = opt.dataset.header;
            mockupHeader.style.display = 'block';
        } else {
            mockupHeader.style.display = 'none';
        }

        let rawBody = opt.dataset.body || '';
        mockupBody.textContent = rawBody;

        // Variables generation
        const varCount = parseInt(opt.dataset.vars || '0', 10);
        varsList.innerHTML = '';

        if (varCount > 0) {
            varsContainer.style.display = 'block';
            for (let i = 1; i <= varCount; i++) {
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6';
                col.innerHTML = `
                    <label class="form-label small text-muted mb-0">Variable {{` + i + `}}</label>
                    <input type="text" name="parameters[]" class="form-control form-control-sm var-input" data-var-index="` + i + `" placeholder="Value or {name}" required>
                `;
                varsList.appendChild(col);
            }

            // Realtime update in mockup on variable typing
            varsList.querySelectorAll('.var-input').forEach(inp => {
                inp.addEventListener('input', updateMockupBody);
            });
        } else {
            varsContainer.style.display = 'none';
        }
    });

    // Realtime image preview
    if (headerImgUrl) {
        headerImgUrl.addEventListener('input', function () {
            if (this.value.trim().startsWith('http')) {
                mockupImg.src = this.value.trim();
                mockupImgWrap.style.display = 'block';
            } else {
                mockupImgWrap.style.display = 'none';
            }
        });
    }

    function updateMockupBody() {
        const opt = templateSelector.options[templateSelector.selectedIndex];
        if (!opt) return;

        let body = opt.dataset.body || '';
        const inputs = varsList.querySelectorAll('.var-input');
        inputs.forEach(inp => {
            const idx = inp.dataset.varIndex;
            const val = inp.value.trim() || ('{{' + idx + '}}');
            body = body.split('{{' + idx + '}}').join(val);
        });

        mockupBody.textContent = body;
    }
});
</script>
@endpush
