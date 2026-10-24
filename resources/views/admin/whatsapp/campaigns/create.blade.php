@extends('admin.layouts.app')

@section('title', 'Create WhatsApp Campaign')

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
                        <input type="text" name="name" class="form-control rounded-3" value="{{ old('name') }}" placeholder="e.g. Diwali Mega Sale 2026" required>
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
                            <option value="promotional_offer">🎉 Promotional Discount (3 variables)</option>
                            <option value="abandoned_cart_recovery">🛒 Abandoned Cart Recovery (2 variables)</option>
                            <option value="vanriti_welcome">🌿 Welcome Offer (1 variable)</option>
                            <option value="order_confirmation">📦 Order Confirmation (3 variables)</option>
                            <option value="custom">⚙️ Custom Approved Template Name</option>
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
                        <div class="text-muted" style="font-size: 0.72rem;">Broadcast will begin sending through background queue with Meta rate-limiting.</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
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
                            <span class="fw-bold text-dark small">WhatsApp Message Preview</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-0.5" style="font-size: 0.68rem;">Verified Format</span>
                        </div>

                        <div class="p-3 rounded-4 border shadow-xs d-flex flex-column" style="min-height: 280px; background-color: #f7f9f8; background-image: radial-gradient(#e0e7e4 1px, transparent 1px); background-size: 15px 15px;">
                            <div id="previewHeaderImgWrapper" class="rounded-3 overflow-hidden mb-2 d-none" style="max-height: 140px;">
                                <img src="" class="w-100 h-100 object-fit-cover" alt="header">
                            </div>

                            <div class="wa-bubble wa-bubble-outbound p-3 w-100 shadow-sm" style="max-width: 100%;">
                                <div class="fw-bold small text-success mb-1" id="previewTitle">VANRITI</div>
                                <div class="small text-dark" id="previewBodyText">Loading preview...</div>
                                <div class="text-end text-muted mt-2" style="font-size: 0.65rem;">Now • <i class="bi bi-check2 text-muted"></i></div>
                            </div>

                            <div class="mt-auto pt-3 text-center text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-shield-check text-success me-1"></i>Approved Meta Cloud Template
                            </div>
                        </div>
                    </div>

                    {{-- Test Send to Phone Card --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-phone text-success fs-5"></i>
                            <span class="fw-bold text-dark small">Send Test to My Phone</span>
                        </div>
                        <p class="text-muted small mb-3">Verify how this broadcast looks on your mobile WhatsApp before launching to customers.</p>

                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light text-muted small">+91</span>
                            <input type="tel" id="testMobile" class="form-control" placeholder="10-digit mobile number" maxlength="10">
                            <button type="button" class="btn btn-outline-success fw-semibold" id="btnSendTest">
                                Send Test
                            </button>
                        </div>
                        <div id="testSendFeedback" class="small mt-1"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.cohort-card {
    background-color: #ffffff;
    cursor: pointer;
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

<script>
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
    const previewHeaderImgTag = previewHeaderWrapper.querySelector('img');
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
                { name: "parameters[]", label: "Offer Title {{1}}", default: "Flat 15% OFF" },
                { name: "parameters[]", label: "Coupon Code {{2}}", default: "FESTIVE15" },
            ]
        },
        abandoned_cart_recovery: {
            title: "Items left in your cart! 🌿",
            text: "Hello {name}, you left items in your VANRITI cart! Complete your order now with 10% OFF at {1}",
            fields: [
                { name: "parameters[]", label: "Checkout Link {{1}}", default: "{{ url('/cart') }}" },
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
                { name: "parameters[]", label: "Order Number {{1}}", default: "VAN-TEST" },
                { name: "parameters[]", label: "Delivery Date {{2}}", default: "in 3-5 business days" },
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

    function renderFields() {
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.promotional_offer;

        if (key === 'custom') {
            customTmplGroup.classList.remove('d-none');
            customTmplName.name = "template_name";
            tmplSelect.name = "";
        } else {
            customTmplGroup.classList.add('d-none');
            tmplSelect.name = "template_name";
        }

        dynamicFields.innerHTML = schema.fields.map((f, idx) => `
            <div class="mb-3">
                <label class="form-label fw-bold small text-dark">${f.label}</label>
                <input type="text" name="${f.name}" class="form-control rounded-3 param-input" value="${f.default}" required>
            </div>
        `).join('');

        dynamicFields.querySelectorAll('.param-input').forEach(inp => {
            inp.addEventListener('input', updatePreview);
        });

        updatePreview();
    }

    function updatePreview() {
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.promotional_offer;

        previewTitle.textContent = schema.title;

        let body = schema.text.replace('{name}', 'Customer');
        const inputs = dynamicFields.querySelectorAll('.param-input');
        inputs.forEach((inp, idx) => {
            const token = '{' + (idx + 1) + '}';
            const val = inp.value || token;
            body = body.split(token).join(val);
        });

        previewBodyText.textContent = body;

        const url = headerImgInput.value.trim();
        if (url) {
            previewHeaderImgTag.src = url;
            previewHeaderWrapper.classList.remove('d-none');
        } else {
            previewHeaderWrapper.classList.add('d-none');
        }
    }

    tmplSelect.addEventListener('change', renderFields);
    headerImgInput.addEventListener('input', updatePreview);
    renderFields();

    // Test send handling
    btnSendTest.addEventListener('click', async function () {
        const phone = testMobile.value.trim();
        if (phone.length < 10) {
            testFeedback.innerHTML = '<span class="text-danger">Please enter a valid 10-digit mobile number.</span>';
            return;
        }

        const templateName = tmplSelect.value === 'custom' ? customTmplName.value.trim() : tmplSelect.value;
        const params = [];
        dynamicFields.querySelectorAll('.param-input').forEach(inp => params.push(inp.value.trim()));
        const headerUrl = headerImgInput.value.trim() || null;

        btnSendTest.disabled = true;
        btnSendTest.textContent = 'Sending...';
        testFeedback.innerHTML = '<span class="text-muted">Sending test template to ' + phone + '...</span>';

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
                    parameters: params,
                    header_image_url: headerUrl,
                }),
            });
            const data = await res.json();

            if (data.success) {
                testFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</span>';
            } else {
                testFeedback.innerHTML = '<span class="text-danger">' + (data.message || 'Test send failed') + '</span>';
            }
        } catch (e) {
            testFeedback.innerHTML = '<span class="text-danger">Network error while sending test message.</span>';
        } finally {
            btnSendTest.disabled = false;
            btnSendTest.textContent = 'Send Test';
        }
    });
});
</script>
@endsection
