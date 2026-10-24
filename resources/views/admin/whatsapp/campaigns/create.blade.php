@extends('admin.layouts.app')

@section('title', 'Create WhatsApp Campaign')

@push('styles')
<style>
.cohort-card {
    background-color: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.cohort-card:hover {
    border-color: #25D366 !important;
    background-color: #f7faf8;
}
.cohort-card.active {
    border-color: #25D366 !important;
    background-color: #e8f5ec;
}
.wa-bubble-outbound {
    background-color: #dcf8c6;
    border-radius: 12px;
    border-bottom-right-radius: 3px;
    color: #0f381e;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-0" style="max-width: 1100px;">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="btn btn-light-sm btn-icon rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Create Broadcast Campaign</h4>
                <p class="text-muted small mb-0">Target specific customer cohorts with approved Meta WhatsApp templates.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.whatsapp.campaigns.store') }}" id="campaignForm">
        @csrf
        <div class="row g-4">
            {{-- Left: Configuration Form --}}
            <div class="col-12 col-lg-7">
                {{-- Step 1: Campaign Details --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">1</span>
                        <span>Campaign Details</span>
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Campaign Name</label>
                        <input type="text" name="name" class="form-control rounded-3" value="{{ old('name') }}" placeholder="e.g. Festive Special Offer 2026" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-dark">Internal Notes (Optional)</label>
                        <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Goals, target ROI or campaign context...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Step 2: Audience Cohort Selection --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">2</span>
                        <span>Select Target Audience Cohort</span>
                    </h6>

                    <div class="row g-2">
                        {{-- All Customers --}}
                        <div class="col-6">
                            <label class="border rounded-3 p-3 d-block cursor-pointer transition-all cohort-card active" for="cohort_all">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <input type="radio" name="target_segment" id="cohort_all" value="all" checked class="form-check-input mt-0">
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill small">{{ number_format($estimates['all']) }} users</span>
                                </div>
                                <div class="fw-bold text-dark small">All Registered Customers</div>
                                <div class="text-muted" style="font-size: 0.72rem;">All accounts with verified phone numbers</div>
                            </label>
                        </div>

                        {{-- Abandoned Carts --}}
                        <div class="col-6">
                            <label class="border rounded-3 p-3 d-block cursor-pointer transition-all cohort-card" for="cohort_cart">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <input type="radio" name="target_segment" id="cohort_cart" value="abandoned_cart" class="form-check-input mt-0">
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill small">{{ number_format($estimates['abandoned_cart']) }} carts</span>
                                </div>
                                <div class="fw-bold text-dark small">Abandoned Carts (7d)</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Uncompleted carts in past week</div>
                            </label>
                        </div>

                        {{-- Buyers / VIPs --}}
                        <div class="col-6">
                            <label class="border rounded-3 p-3 d-block cursor-pointer transition-all cohort-card" for="cohort_buyers">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <input type="radio" name="target_segment" id="cohort_buyers" value="buyers" class="form-check-input mt-0">
                                    <span class="badge bg-success-subtle text-success rounded-pill small">{{ number_format($estimates['buyers']) }} buyers</span>
                                </div>
                                <div class="fw-bold text-dark small">Recent Buyers & VIPs</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Customers with completed orders</div>
                            </label>
                        </div>

                        {{-- Inactive Customers --}}
                        <div class="col-6">
                            <label class="border rounded-3 p-3 d-block cursor-pointer transition-all cohort-card" for="cohort_inactive">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <input type="radio" name="target_segment" id="cohort_inactive" value="inactive" class="form-check-input mt-0">
                                    <span class="badge bg-info-subtle text-info rounded-pill small">{{ number_format($estimates['inactive']) }} users</span>
                                </div>
                                <div class="fw-bold text-dark small">Inactive Customers (30d+)</div>
                                <div class="text-muted" style="font-size: 0.72rem;">No orders placed in past 30 days</div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Meta Template Configuration --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">3</span>
                        <span>Meta Template & Dynamic Variables</span>
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Meta Template</label>
                        <select name="template_name" id="campTmplSelect" class="form-select rounded-3" required>
                            @if (!empty($approvedTemplates) && $approvedTemplates->isNotEmpty())
                                <optgroup label="Approved Meta Templates (Live)">
                                    @foreach ($approvedTemplates as $tpl)
                                        <option value="{{ $tpl->name }}">
                                            {{ $tpl->name }} ({{ strtoupper($tpl->language) }} - {{ ucfirst($tpl->category) }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="Preset Standard Templates">
                                <option value="promotional_offer">🎉 Promotional Discount (3 variables)</option>
                                <option value="abandoned_cart_recovery">🛒 Abandoned Cart Recovery (2 variables)</option>
                                <option value="vanriti_welcome">🌿 Welcome Offer (1 variable)</option>
                                <option value="order_confirmation">📦 Order Confirmation (3 variables)</option>
                                <option value="custom">⚙️ Custom Approved Template Name</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="customTmplGroup">
                        <label class="form-label fw-bold small text-dark">Custom Meta Template Name</label>
                        <input type="text" id="customTmplName" class="form-control rounded-3" placeholder="e.g. spring_festive_offer">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Language Code</label>
                        <input type="text" name="template_lang" class="form-control rounded-3" value="en" required>
                    </div>

                    <div id="campDynamicFields">
                        <!-- Dynamically filled parameter inputs -->
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-dark">Header Image URL (Optional)</label>
                        <input type="url" name="header_image_url" id="campHeaderImg" class="form-control rounded-3" placeholder="https://vanriti.com/images/festive-banner.jpg">
                        <small class="text-muted" style="font-size: 0.72rem;">Required only if the selected Meta template has an image header.</small>
                    </div>
                </div>

                {{-- Step 4: Dispatch Settings --}}
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white p-1" style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">4</span>
                        <span>Schedule & Launch</span>
                    </h6>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="dispatch_now" id="dispatchNow" value="1" checked>
                        <label class="form-check-label fw-semibold small text-dark" for="dispatchNow">
                            Launch immediately upon saving
                        </label>
                        <div class="text-muted" style="font-size: 0.72rem;">Broadcast will execute with automated rate-limiting and conversation recording.</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm" id="btnSubmitCampaign">
                            <i class="bi bi-send-check me-1"></i>Save & Launch Campaign
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right: Live Preview & Test Send Drawer --}}
            <div class="col-12 col-lg-5">
                <div class="sticky-top" style="top: 80px;">
                    {{-- Live WhatsApp Phone Bubble Preview --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="text-uppercase fw-bold text-muted small" style="font-size: 0.72rem;">Live WhatsApp Preview</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Meta Verified</span>
                        </div>

                        {{-- Phone Shell Simulated Card --}}
                        <div class="p-3 rounded-4 border shadow-xs" style="background-color: #efeae2; background-image: radial-gradient(#d5cebe 1px, transparent 1px); background-size: 16px 16px; min-height: 280px;">
                            {{-- Header Media (if any) --}}
                            <div id="previewHeaderImgWrapper" class="mb-2 rounded-3 overflow-hidden d-none" style="max-height: 140px;">
                                <img src="" alt="header" class="w-100 h-100 object-fit-cover">
                            </div>

                            {{-- Outbound Bubble --}}
                            <div class="wa-bubble-outbound p-3 shadow-xs">
                                <div class="fw-bold small text-success mb-1" id="previewTitle">VANRITI</div>
                                <div class="small text-dark text-break" id="previewBodyText" style="white-space: pre-wrap; font-size: 0.85rem; line-height: 1.45;">Loading template preview...</div>
                                <div class="d-flex align-items-center justify-content-end gap-1 mt-2 text-muted" style="font-size: 0.65rem;">
                                    <span>Now</span>
                                    <i class="bi bi-check2 text-muted"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Send Instant Test Message --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-phone text-primary"></i>
                            <span>Send Live Test to Mobile</span>
                        </h6>
                        <p class="text-muted small mb-3">Verify how this exact template with your customized parameters renders on your WhatsApp phone before broadcasting.</p>

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white border-end-0 text-muted">+91</span>
                            <input type="text" id="testMobile" class="form-control border-start-0 ps-0" placeholder="e.g. 9876543210" maxlength="15">
                            <button type="button" class="btn btn-outline-success px-3 fw-semibold" id="btnSendTest">
                                <i class="bi bi-send-fill me-1"></i>Send Test
                            </button>
                        </div>
                        <div id="testSendFeedback" class="small mt-1"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    // Cohort radio highlights
    document.querySelectorAll('.cohort-card input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.cohort-card').forEach(c => c.classList.remove('active'));
            if (this.checked) {
                this.closest('.cohort-card').classList.add('active');
            }
        });
    });

    const tmplSelect = document.getElementById('campTmplSelect');
    const customTmplGroup = document.getElementById('customTmplGroup');
    const customTmplName = document.getElementById('customTmplName');
    const dynamicFields = document.getElementById('campDynamicFields');
    const headerImgInput = document.getElementById('campHeaderImg');
    const previewHeaderWrapper = document.getElementById('previewHeaderImgWrapper');
    const previewHeaderImgTag = previewHeaderWrapper ? previewHeaderWrapper.querySelector('img') : null;
    const previewTitle = document.getElementById('previewTitle');
    const previewBodyText = document.getElementById('previewBodyText');
    const btnSendTest = document.getElementById('btnSendTest');
    const testMobile = document.getElementById('testMobile');
    const testFeedback = document.getElementById('testSendFeedback');
    const testUrl = '{{ route('admin.whatsapp.campaigns.test-send') }}';
    const csrfToken = '{{ csrf_token() }}';

    const templateSchemas = {
        promotional_offer: {
            title: "Special VIP Offer 🎉",
            text: "Hello {name}, celebrate with us! Enjoy {1} on your next order using coupon code: {2} at checkout.",
            fields: [
                { name: "parameters[]", label: "Offer Title (Param 1)", default: "Flat 15% OFF" },
                { name: "parameters[]", label: "Coupon Code (Param 2)", default: "FESTIVE15" },
            ]
        },
        abandoned_cart_recovery: {
            title: "Items left in your cart! 🌿",
            text: "Hello {name}, you left items in your VANRITI cart! Complete your order now with 10% OFF at {1}",
            fields: [
                { name: "parameters[]", label: "Checkout Link (Param 1)", default: "{{ url('/cart') }}" },
            ]
        },
        vanriti_welcome: {
            title: "Welcome to VANRITI 🌿",
            text: "Hello {name}, welcome to VANRITI! Discover the pure essence of Ayurveda and natural holistic skincare with special offers on your first purchase.",
            fields: []
        },
        order_confirmation: {
            title: "Order Confirmed 🌿",
            text: "Hello {name}, thank you for your order! Your order #{1} is confirmed and will be delivered by {2}.",
            fields: [
                { name: "parameters[]", label: "Order Number (Param 1)", default: "VAN-1001" },
                { name: "parameters[]", label: "Delivery Date (Param 2)", default: "in 3-5 business days" },
            ]
        },
        custom: {
            title: "Custom Meta Template",
            text: "Custom approved template body with variables: {1}, {2}",
            fields: [
                { name: "parameters[]", label: "Parameter 1", default: "" },
                { name: "parameters[]", label: "Parameter 2", default: "" },
            ]
        }
    };

    const approvedMetaTemplates = @json($approvedTemplates ?? []);
    if (Array.isArray(approvedMetaTemplates)) {
        approvedMetaTemplates.forEach(tpl => {
            const tokenRegex = new RegExp('\\' + '{' + '\\' + '{' + '\\s*\\d+\\s*' + '\\' + '}' + '\\' + '}', 'g');
            const matches = tpl.body_text ? (tpl.body_text.match(tokenRegex) || []) : [];
            const uniqueTokens = [...new Set(matches)];
            const fields = uniqueTokens.map((t, idx) => ({
                name: "parameters[]",
                label: `Parameter ${idx + 1}`,
                default: idx === 0 ? "Customer" : ""
            }));

            templateSchemas[tpl.name] = {
                title: tpl.header_text || (tpl.name.replace(/_/g, ' ').toUpperCase()),
                text: tpl.body_text || '',
                fields: fields,
                language: tpl.language || 'en'
            };
        });
    }

    function renderFields() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.promotional_offer;

        if (schema.language) {
            const langInput = document.querySelector('input[name="template_lang"]');
            if (langInput) langInput.value = schema.language;
        }

        if (key === 'custom') {
            if (customTmplGroup) customTmplGroup.classList.remove('d-none');
            if (customTmplName) customTmplName.name = "template_name";
            tmplSelect.name = "";
        } else {
            if (customTmplGroup) customTmplGroup.classList.add('d-none');
            tmplSelect.name = "template_name";
        }

        if (dynamicFields) {
            dynamicFields.innerHTML = schema.fields.map((f, idx) => `
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">${f.label}</label>
                    <input type="text" name="${f.name}" class="form-control rounded-3 param-input" value="${f.default}" required>
                </div>
            `).join('');

            dynamicFields.querySelectorAll('.param-input').forEach(inp => {
                inp.addEventListener('input', updatePreview);
            });
        }

        updatePreview();
    }

    function updatePreview() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.promotional_offer;

        if (previewTitle) previewTitle.textContent = schema.title;

        let body = schema.text.replace('{name}', 'Customer');
        const inputs = dynamicFields ? dynamicFields.querySelectorAll('.param-input') : [];
        inputs.forEach((inp, idx) => {
            const token1 = '{' + (idx + 1) + '}';
            const token2 = '{{' + (idx + 1) + '}}';
            const val = inp.value || token1;
            body = body.split(token1).join(val).split(token2).join(val);
        });

        if (previewBodyText) previewBodyText.textContent = body;

        if (headerImgInput && previewHeaderWrapper && previewHeaderImgTag) {
            const url = headerImgInput.value.trim();
            if (url) {
                previewHeaderImgTag.src = url;
                previewHeaderWrapper.classList.remove('d-none');
            } else {
                previewHeaderWrapper.classList.add('d-none');
            }
        }
    }

    if (tmplSelect) {
        tmplSelect.addEventListener('change', renderFields);
    }
    if (headerImgInput) {
        headerImgInput.addEventListener('input', updatePreview);
    }
    renderFields();

    // Test send handling
    if (btnSendTest && testMobile && testFeedback) {
        btnSendTest.addEventListener('click', async function () {
            const phone = testMobile.value.trim();
            if (phone.length < 10) {
                testFeedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Please enter a valid 10-digit mobile number.</span>';
                return;
            }

            const templateName = tmplSelect.value === 'custom' ? (customTmplName ? customTmplName.value.trim() : '') : tmplSelect.value;
            const params = [];
            if (dynamicFields) {
                dynamicFields.querySelectorAll('.param-input').forEach(inp => params.push(inp.value.trim()));
            }
            const headerUrl = headerImgInput ? (headerImgInput.value.trim() || null) : null;
            const langCode = document.querySelector('input[name="template_lang"]')?.value || 'en';

            btnSendTest.disabled = true;
            btnSendTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';
            testFeedback.innerHTML = '<span class="text-muted">Sending test template to +91 ' + phone + '...</span>';

            try {
                const res = await fetch(testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        test_phone: phone,
                        template_name: templateName,
                        template_lang: langCode,
                        parameters: params,
                        header_image_url: headerUrl,
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    testFeedback.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
                } else {
                    testFeedback.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>' + (data.message || 'Test send failed') + '</span>';
                }
            } catch (e) {
                testFeedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Network error while sending test message.</span>';
            } finally {
                btnSendTest.disabled = false;
                btnSendTest.innerHTML = '<i class="bi bi-send-fill me-1"></i>Send Test';
            }
        });
    }
});
</script>
@endpush
