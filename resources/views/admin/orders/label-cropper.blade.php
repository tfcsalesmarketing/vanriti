@extends('admin.layouts.app')

@section('title', 'Shipping Label Cropper (Thermal 4x6)')

@push('styles')
<style>
    .label-cropper-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .dropzone-container {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        padding: 2.2rem 1.5rem;
        text-align: center;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
    }
    .dropzone-container:hover, .dropzone-container.dragover {
        border-color: #263D25;
        background: #f0fdf4;
    }
    .preset-pill {
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.65rem 0.85rem;
        background: #ffffff;
        text-align: left;
        user-select: none;
    }
    .preset-pill:hover {
        border-color: #263D25;
        background: #f8fafc;
    }
    .preset-pill.active {
        border-color: #263D25;
        background: #f0fdf4;
        box-shadow: 0 0 0 1.5px #263D25;
    }
    .preview-box-wrapper {
        background: #0f172a;
        border-radius: 10px;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 480px;
        position: relative;
        overflow: hidden;
    }
    .thermal-paper-mockup {
        background: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5), 0 8px 10px -6px rgba(0,0,0,0.5);
        border-radius: 4px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .preview-canvas {
        max-width: 100%;
        height: auto;
        display: block;
    }
    .crop-mask-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        touch-action: none;
    }
    .form-range::-webkit-slider-thumb {
        background: #263D25;
    }
    .form-range::-moz-range-thumb {
        background: #263D25;
    }
    .btn-brand {
        background-color: #263D25;
        color: #ffffff;
        border: none;
    }
    .btn-brand:hover {
        background-color: #1b2d1a;
        color: #ffffff;
    }
    .stepper-badge {
        width: 24px;
        height: 24px;
        background: #263D25;
        color: #fff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .visually-hidden-file-input {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 py-2">
    {{-- Header & Breadcrumbs --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-dark">Shipping Label Cropper</h4>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-printer me-1"></i>Thermal 4×6″ Ready
                </span>
            </div>
            <div class="text-muted small mt-1">
                Crop &amp; convert Meesho, Flipkart, and Amazon A4 shipping labels into clean 4″ × 6″ thermal stickers without invoices or blank margins.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-receipt"></i><span>All Orders</span>
            </a>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-truck"></i><span>Shipments</span>
            </a>
        </div>
    </div>

    {{-- Accessible File Input (Strictly DOM-present) --}}
    <input type="file" id="pdfFileInput" accept="application/pdf" multiple class="visually-hidden-file-input">

    {{-- Step 1: Upload Dropzone Card --}}
    <div class="label-cropper-card p-4 mb-3" id="dropzoneCard">
        <div class="dropzone-container" id="dropzone">
            <div class="mb-2">
                <i class="bi bi-cloud-arrow-up display-5 text-success"></i>
            </div>
            <h5 class="fw-bold mb-1 text-dark">Drop your Meesho, Flipkart, or Amazon PDF here</h5>
            <p class="text-muted small mb-3">Supports single or multi-page bulk PDF labels downloaded from seller portals (e.g. 10 to 100+ labels at once)</p>
            <label for="pdfFileInput" class="btn btn-sm btn-brand px-3 py-1.5 fw-semibold" id="btnBrowseFile" style="cursor: pointer;">
                <i class="bi bi-folder2-open me-1"></i>Browse PDF Files
            </label>
            <div class="text-muted text-xs mt-2" style="font-size: 0.78rem;">
                <i class="bi bi-shield-check text-success me-1"></i>100% Private &amp; Secure — processed locally in your browser. Customer details never leave your computer.
            </div>
        </div>
    </div>

    {{-- Compact File Status Bar (Shows when file is loaded) --}}
    <div id="fileInfoBar" class="d-none mb-3 p-3 bg-white label-cropper-card d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-light p-2 border">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark text-truncate" id="fileNameDisplay" style="max-width: 340px;">sample.pdf</div>
                <div class="text-muted small" id="fileMetaDisplay">0 pages • 0 KB</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnAutoTrimTop">
                <i class="bi bi-magic me-1"></i>Auto-Trim White Space
            </button>
            <label for="pdfFileInput" class="btn btn-sm btn-outline-secondary" style="cursor: pointer;">
                <i class="bi bi-arrow-repeat me-1"></i>Upload Different PDF
            </label>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearFile">
                <i class="bi bi-trash3 me-1"></i>Clear
            </button>
        </div>
    </div>

    {{-- Step 2 & 3: Workspace Area (Hidden until file selected) --}}
    <div id="workspaceSection" class="d-none">
        <div class="row g-3">
            {{-- Left Column: Marketplace Presets & Thermal Settings --}}
            <div class="col-lg-5 col-xl-4">
                <div class="label-cropper-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="stepper-badge">1</span>
                            <span>Marketplace Preset</span>
                        </div>
                        <span class="badge bg-light text-muted border">Quick Crop</span>
                    </div>
                    <p class="text-muted small mb-2">Select your platform preset for instant tight cropping:</p>

                    <div class="vstack gap-2" id="presetsContainer">
                        @foreach ($presets as $key => $preset)
                        <div class="preset-pill {{ $key === 'flipkart_tight' ? 'active' : '' }}" data-preset="{{ $key }}" id="presetPill_{{ $key }}">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark small">{{ $preset['name'] }}</span>
                                <span class="badge {{ $preset['badge_class'] }} border" style="font-size: 0.65rem;">{{ $preset['badge'] }}</span>
                            </div>
                            <div class="text-muted text-xs" style="font-size: 0.74rem;">{{ $preset['description'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Thermal Printer & Paper Settings --}}
                <div class="label-cropper-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="stepper-badge">2</span>
                            <span>Thermal Printer Settings</span>
                        </div>
                        <span class="badge bg-light text-muted border">Paper Spec</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1" for="paperSizeSelect">Target Paper / Roll Size</label>
                        <select class="form-select form-select-sm" id="paperSizeSelect">
                            @foreach ($paperSizes as $pKey => $paper)
                            <option value="{{ $pKey }}" {{ $pKey === '4x6' ? 'selected' : '' }}>
                                {{ $paper['name'] }}
                            </option>
                            @endforeach
                            <option value="custom">Custom Dimensions (mm)...</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3 d-none" id="customPaperInputs">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-0" for="customWidthMm">Width (mm)</label>
                            <input type="number" class="form-control form-control-sm" id="customWidthMm" value="100" min="40" max="300">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-0" for="customHeightMm">Height (mm)</label>
                            <input type="number" class="form-control form-control-sm" id="customHeightMm" value="150" min="40" max="400">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1" for="rotationSelect">Rotation</label>
                            <select class="form-select form-select-sm" id="rotationSelect">
                                <option value="0" selected>0° (Upright Portrait)</option>
                                <option value="90">90° Clockwise</option>
                                <option value="180">180° Inverted</option>
                                <option value="270">270° (90° CCW)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold mb-1" for="safeMarginInput">Safe Margin (Border)</label>
                            <select class="form-select form-select-sm" id="safeMarginInput">
                                <option value="0" selected>0 mm (No White Space)</option>
                                <option value="1">1 mm (Minimal)</option>
                                <option value="2">2 mm (Standard)</option>
                                <option value="4">4 mm (Safe Padding)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1" for="scaleModeSelect">Scale &amp; Fit Format</label>
                        <select class="form-select form-select-sm" id="scaleModeSelect">
                            <option value="fill" selected>Fit Entire 4×6″ Standard Thermal Format (Zero Blank Space)</option>
                            <option value="fit">Fit Proportionally</option>
                        </select>
                        <div class="form-text text-muted text-xs" style="font-size: 0.72rem;">
                            "Fit Entire 4×6″" automatically scales all shipping labels to 100% of the 4×6″ thermal sticker area with zero white space.
                        </div>
                    </div>
                </div>

                {{-- Fine-Tune Crop Sliders --}}
                <div class="label-cropper-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="stepper-badge">3</span>
                            <span>Crop Margins (Zero Blank)</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold" id="btnAutoTrimSide">
                            <i class="bi bi-magic me-1"></i>Auto-Trim
                        </button>
                    </div>

                    <div class="collapse show" id="cropSlidersCollapse">
                        <div class="vstack gap-2 pt-1">
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Cut Top:</span>
                                    <span class="fw-bold text-dark" id="cropTopVal">3%</span>
                                </div>
                                <input type="range" class="form-range" id="cropTopSlider" min="0" max="90" value="3">
                            </div>

                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted text-danger fw-semibold">Cut Bottom (Tax Invoice):</span>
                                    <span class="fw-bold text-danger" id="cropBottomVal">55%</span>
                                </div>
                                <input type="range" class="form-range" id="cropBottomSlider" min="0" max="90" value="55">
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Cut Left:</span>
                                        <span class="fw-bold text-dark" id="cropLeftVal">31%</span>
                                    </div>
                                    <input type="range" class="form-range" id="cropLeftSlider" min="0" max="80" value="31">
                                </div>
                                <div class="col-6">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Cut Right:</span>
                                        <span class="fw-bold text-dark" id="cropRightVal">31%</span>
                                    </div>
                                    <input type="range" class="form-range" id="cropRightSlider" min="0" max="80" value="31">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Dual Live Preview & Batch Actions --}}
            <div class="col-lg-7 col-xl-8">
                {{-- Actions Bar --}}
                <div class="label-cropper-card p-3 mb-3 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success px-2 py-1 fs-6">
                                <i class="bi bi-check2-circle me-1"></i><span id="labelCountBadge">1 Label Ready</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnAutoTrimAction">
                                <i class="bi bi-magic me-1"></i>Auto-Trim White Space
                            </button>
                            <div class="vr mx-1"></div>
                            {{-- Multi-page Navigation --}}
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btnPrevPage">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="btn btn-outline-secondary disabled fw-semibold text-dark" id="pageIndicator">Page 1 / 1</span>
                                <button type="button" class="btn btn-outline-secondary" id="btnNextPage">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPrintBtn">
                                <i class="bi bi-printer me-1"></i>Print Directly
                            </button>
                            <button type="button" class="btn btn-brand px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btnDownloadBtn">
                                <i class="bi bi-download"></i><span>Download 4×6″ Thermal PDF</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Dual Previews --}}
                <div class="row g-3">
                    {{-- Preview A: Source PDF Sheet with Interactive Draggable Crop Box --}}
                    <div class="col-md-6">
                        <div class="label-cropper-card p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold small text-dark"><i class="bi bi-file-earmark-text text-primary me-1"></i>Original Marketplace PDF</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                    <i class="bi bi-arrows-move me-1"></i>Draggable Crop Handles
                                </span>
                            </div>
                            <div class="preview-box-wrapper" style="background: #1e293b;">
                                <div style="position: relative; display: inline-block;">
                                    <canvas id="sourceCanvas" class="preview-canvas rounded shadow-sm"></canvas>
                                    <canvas id="sourceOverlayCanvas" class="crop-mask-overlay"></canvas>
                                </div>
                            </div>
                            <div class="text-muted text-center text-xs mt-2" style="font-size: 0.72rem;">
                                💡 <strong>Tip:</strong> Click &amp; drag the green border lines or corner circles directly on the PDF above to eliminate any white space!
                            </div>
                        </div>
                    </div>

                    {{-- Preview B: Mockup of Physical 4" x 6" Thermal Sticker --}}
                    <div class="col-md-6">
                        <div class="label-cropper-card p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold small text-dark"><i class="bi bi-upc-scan text-success me-1"></i>4″ × 6″ Thermal Sticker Preview</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;" id="thermalSizeBadge">100 × 150 mm</span>
                            </div>
                            <div class="preview-box-wrapper" style="background: #0f172a;">
                                <div class="thermal-paper-mockup" id="thermalPaperMockup">
                                    <canvas id="thermalCanvas" class="preview-canvas"></canvas>
                                </div>
                            </div>
                            <div class="text-muted text-center text-xs mt-2" style="font-size: 0.72rem;">
                                Live preview: Notice how the label fills the entire thermal sticker with <strong>zero blank margin</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Print Container --}}
<iframe id="printIframe" class="d-none" style="display:none; width:0; height:0; border:0;"></iframe>
@endsection

@push('scripts')
{{-- Vendored local PDF-Lib and PDF.js (Same-origin, zero CORS/CDN/CSP issues) --}}
<script nonce="{{ $cspNonce }}" src="{{ asset('vendor/pdf-lib/pdf-lib.min.js') }}"></script>
<script nonce="{{ $cspNonce }}" src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    // Prevent accidental window drag-and-drop navigation
    window.addEventListener('dragover', function (e) { e.preventDefault(); }, false);
    window.addEventListener('drop', function (e) { e.preventDefault(); }, false);

    // Initialize local PDF.js worker
    if (window.pdfjsLib) {
        try {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
        } catch (e) {
            console.warn('PDF.js worker initialization:', e);
        }
    }

    const presetsConfig = @json($presets);
    const paperSizesConfig = @json($paperSizes);

    // State
    let activePreset = 'flipkart_tight';
    let currentPdfFile = null;
    let currentPdfBytes = null;
    let pdfJsDoc = null;
    let pdfLibDoc = null;
    let totalPages = 1;
    let currentPageIndex = 0; // 0-indexed
    let cachedMasterCanvas = null;
    let cachedMasterPageIndex = -1;
    let activeRenderTask = null;

    // Draggable crop overlay state
    let dragMode = null; // 'top', 'bottom', 'left', 'right', 'nw', 'ne', 'sw', 'se', 'move'
    let dragStartMouse = { x: 0, y: 0 };
    let dragStartCrop = { top: 0, bottom: 0, left: 0, right: 0 };

    // DOM Elements
    const dropzoneCard = document.getElementById('dropzoneCard');
    const dropzone = document.getElementById('dropzone');
    const pdfFileInput = document.getElementById('pdfFileInput');
    const fileInfoBar = document.getElementById('fileInfoBar');
    const workspaceSection = document.getElementById('workspaceSection');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileMetaDisplay = document.getElementById('fileMetaDisplay');
    const btnClearFile = document.getElementById('btnClearFile');

    // Controls
    const paperSizeSelect = document.getElementById('paperSizeSelect');
    const customPaperInputs = document.getElementById('customPaperInputs');
    const customWidthMm = document.getElementById('customWidthMm');
    const customHeightMm = document.getElementById('customHeightMm');

    const rotationSelect = document.getElementById('rotationSelect');
    const safeMarginInput = document.getElementById('safeMarginInput');
    const scaleModeSelect = document.getElementById('scaleModeSelect');

    const cropTopSlider = document.getElementById('cropTopSlider');
    const cropBottomSlider = document.getElementById('cropBottomSlider');
    const cropLeftSlider = document.getElementById('cropLeftSlider');
    const cropRightSlider = document.getElementById('cropRightSlider');

    const cropTopVal = document.getElementById('cropTopVal');
    const cropBottomVal = document.getElementById('cropBottomVal');
    const cropLeftVal = document.getElementById('cropLeftVal');
    const cropRightVal = document.getElementById('cropRightVal');

    const sourceCanvas = document.getElementById('sourceCanvas');
    const sourceOverlayCanvas = document.getElementById('sourceOverlayCanvas');
    const thermalCanvas = document.getElementById('thermalCanvas');
    const pageIndicator = document.getElementById('pageIndicator');
    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');
    const labelCountBadge = document.getElementById('labelCountBadge');
    const thermalSizeBadge = document.getElementById('thermalSizeBadge');
    const btnDownloadBtn = document.getElementById('btnDownloadBtn');
    const btnPrintBtn = document.getElementById('btnPrintBtn');

    const btnAutoTrimTop = document.getElementById('btnAutoTrimTop');
    const btnAutoTrimSide = document.getElementById('btnAutoTrimSide');
    const btnAutoTrimAction = document.getElementById('btnAutoTrimAction');

    // 1. FILE PICKER & DROPZONE
    dropzone.addEventListener('click', function (e) {
        if (e.target.id !== 'btnBrowseFile' && !e.target.closest('#btnBrowseFile')) {
            pdfFileInput.click();
        }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', function (e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            handleFilesSelection(Array.from(files));
        }
    });

    pdfFileInput.addEventListener('change', function (e) {
        if (e.target.files && e.target.files.length > 0) {
            handleFilesSelection(Array.from(e.target.files));
        }
    });

    btnClearFile.addEventListener('click', resetWorkspace);

    // 2. PRESET SELECTION
    document.querySelectorAll('.preset-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            const key = pill.getAttribute('data-preset');
            applyPreset(key);
        });
    });

    // 3. DROPDOWNS & SLIDERS
    paperSizeSelect.addEventListener('change', function () {
        onPaperSizeChange(true);
    });

    rotationSelect.addEventListener('change', function () {
        updateThermalMockupOnly();
    });

    safeMarginInput.addEventListener('change', function () {
        updateThermalMockupOnly();
    });

    scaleModeSelect.addEventListener('change', function () {
        updateThermalMockupOnly();
    });

    customWidthMm.addEventListener('input', function () {
        updateThermalMockupOnly();
    });

    customHeightMm.addEventListener('input', function () {
        updateThermalMockupOnly();
    });

    // Crop sliders
    [cropTopSlider, cropBottomSlider, cropLeftSlider, cropRightSlider].forEach(function (slider) {
        slider.addEventListener('input', onCropSliderChange);
    });

    // Navigation and Action buttons
    btnPrevPage.addEventListener('click', function () { changePage(-1); });
    btnNextPage.addEventListener('click', function () { changePage(1); });
    btnDownloadBtn.addEventListener('click', downloadCroppedPdf);
    btnPrintBtn.addEventListener('click', printThermalDirectly);

    // Auto-Trim Buttons
    [btnAutoTrimTop, btnAutoTrimSide, btnAutoTrimAction].forEach(function (btn) {
        if (btn) btn.addEventListener('click', autoTrimLabel);
    });

    // 4. INTERACTIVE CANVAS DRAGGING
    function getCropRectPx(w, h) {
        const cropTopPct = parseFloat(cropTopSlider.value) / 100;
        const cropBottomPct = parseFloat(cropBottomSlider.value) / 100;
        const cropLeftPct = parseFloat(cropLeftSlider.value) / 100;
        const cropRightPct = parseFloat(cropRightSlider.value) / 100;

        const left = w * cropLeftPct;
        const top = h * cropTopPct;
        const right = w * (1 - cropRightPct);
        const bottom = h * (1 - cropBottomPct);
        return { left, top, right, bottom, width: Math.max(0, right - left), height: Math.max(0, bottom - top) };
    }

    function getDragHandleAtPoint(x, y, rect) {
        const tol = 14; // pixel hit target tolerance
        const { left, top, right, bottom } = rect;

        // Corners
        if (Math.abs(x - left) <= tol && Math.abs(y - top) <= tol) return 'nw';
        if (Math.abs(x - right) <= tol && Math.abs(y - top) <= tol) return 'ne';
        if (Math.abs(x - left) <= tol && Math.abs(y - bottom) <= tol) return 'sw';
        if (Math.abs(x - right) <= tol && Math.abs(y - bottom) <= tol) return 'se';

        // Edges
        if (Math.abs(y - top) <= tol && x >= left - tol && x <= right + tol) return 'top';
        if (Math.abs(y - bottom) <= tol && x >= left - tol && x <= right + tol) return 'bottom';
        if (Math.abs(x - left) <= tol && y >= top - tol && y <= bottom + tol) return 'left';
        if (Math.abs(x - right) <= tol && y >= top - tol && y <= bottom + tol) return 'right';

        // Inside box
        if (x > left && x < right && y > top && y < bottom) return 'move';

        return null;
    }

    function getCursorForMode(mode) {
        switch (mode) {
            case 'top':
            case 'bottom': return 'ns-resize';
            case 'left':
            case 'right': return 'ew-resize';
            case 'nw':
            case 'se': return 'nwse-resize';
            case 'ne':
            case 'sw': return 'nesw-resize';
            case 'move': return 'move';
            default: return 'crosshair';
        }
    }

    sourceOverlayCanvas.addEventListener('pointerdown', function (e) {
        const rect = sourceOverlayCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const cropRect = getCropRectPx(sourceOverlayCanvas.width, sourceOverlayCanvas.height);
        const handle = getDragHandleAtPoint(x, y, cropRect);

        if (handle) {
            dragMode = handle;
            dragStartMouse = { x: e.clientX, y: e.clientY };
            dragStartCrop = {
                top: parseFloat(cropTopSlider.value),
                bottom: parseFloat(cropBottomSlider.value),
                left: parseFloat(cropLeftSlider.value),
                right: parseFloat(cropRightSlider.value)
            };
            sourceOverlayCanvas.setPointerCapture(e.pointerId);
        }
    });

    sourceOverlayCanvas.addEventListener('pointermove', function (e) {
        const rect = sourceOverlayCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        if (!dragMode) {
            const cropRect = getCropRectPx(sourceOverlayCanvas.width, sourceOverlayCanvas.height);
            const handle = getDragHandleAtPoint(x, y, cropRect);
            sourceOverlayCanvas.style.cursor = getCursorForMode(handle);
            return;
        }

        const dx = e.clientX - dragStartMouse.x;
        const dy = e.clientY - dragStartMouse.y;
        const w = sourceOverlayCanvas.width;
        const h = sourceOverlayCanvas.height;

        const dxPct = (dx / w) * 100;
        const dyPct = (dy / h) * 100;

        let newTop = dragStartCrop.top;
        let newBottom = dragStartCrop.bottom;
        let newLeft = dragStartCrop.left;
        let newRight = dragStartCrop.right;

        if (dragMode === 'top' || dragMode === 'nw' || dragMode === 'ne') {
            newTop = Math.max(0, Math.min(85, dragStartCrop.top + dyPct));
        }
        if (dragMode === 'bottom' || dragMode === 'sw' || dragMode === 'se') {
            newBottom = Math.max(0, Math.min(85, dragStartCrop.bottom - dyPct));
        }
        if (dragMode === 'left' || dragMode === 'nw' || dragMode === 'sw') {
            newLeft = Math.max(0, Math.min(85, dragStartCrop.left + dxPct));
        }
        if (dragMode === 'right' || dragMode === 'ne' || dragMode === 'se') {
            newRight = Math.max(0, Math.min(85, dragStartCrop.right - dxPct));
        }
        if (dragMode === 'move') {
            const boxWPct = 100 - dragStartCrop.left - dragStartCrop.right;
            const boxHPct = 100 - dragStartCrop.top - dragStartCrop.bottom;

            newLeft = Math.max(0, Math.min(100 - boxWPct, dragStartCrop.left + dxPct));
            newRight = Math.max(0, 100 - newLeft - boxWPct);

            newTop = Math.max(0, Math.min(100 - boxHPct, dragStartCrop.top + dyPct));
            newBottom = Math.max(0, 100 - newTop - boxHPct);
        }

        cropTopSlider.value = Math.round(newTop);
        cropBottomSlider.value = Math.round(newBottom);
        cropLeftSlider.value = Math.round(newLeft);
        cropRightSlider.value = Math.round(newRight);

        updateSliderDisplayValues();
        document.querySelectorAll('.preset-pill').forEach(p => p.classList.remove('active'));
        const customPill = document.getElementById('presetPill_custom');
        if (customPill) customPill.classList.add('active');

        updateOverlaysAndThermal();
    });

    sourceOverlayCanvas.addEventListener('pointerup', function (e) {
        if (dragMode) {
            dragMode = null;
            try { sourceOverlayCanvas.releasePointerCapture(e.pointerId); } catch (e) {}
        }
    });

    // 5. AUTO-TRIM ALGORITHM: Detects the solid black borders of the shipping label
    function autoTrimLabel() {
        if (!cachedMasterCanvas) return;

        const ctx = cachedMasterCanvas.getContext('2d');
        const w = cachedMasterCanvas.width;
        const h = cachedMasterCanvas.height;

        // Scan the top 47% of the page (where shipping labels live, ignoring bottom invoice)
        const scanH = Math.floor(h * 0.47);
        const imgData = ctx.getImageData(0, 0, w, scanH);
        const data = imgData.data;

        let minY = scanH, maxY = 0;

        // Check darkness
        function isDark(r, g, b, a) {
            if (a < 80) return false;
            const luminance = (r * 299 + g * 587 + b * 114) / 1000;
            return luminance < 170;
        }

        // 1. Detect vertical bounds
        for (let y = 0; y < scanH; y++) {
            let darkCount = 0;
            for (let x = 0; x < w; x++) {
                const idx = (y * w + x) * 4;
                if (isDark(data[idx], data[idx + 1], data[idx + 2], data[idx + 3])) {
                    darkCount++;
                }
            }
            if (darkCount > 25) {
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
            }
        }

        // 2. Detect horizontal bounds within the vertical range
        let minX = w, maxX = 0;
        if (minY < maxY) {
            for (let x = 0; x < w; x++) {
                let darkCount = 0;
                for (let y = minY; y <= maxY; y++) {
                    const idx = (y * w + x) * 4;
                    if (isDark(data[idx], data[idx + 1], data[idx + 2], data[idx + 3])) {
                        darkCount++;
                    }
                }
                if (darkCount > 15) {
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                }
            }
        }

        if (minX < maxX && minY < maxY && (maxX - minX) > 80 && (maxY - minY) > 80) {
            // Tight crop: add 1-2px safety margin so border strokes aren't cut
            minX = Math.max(0, minX - 2);
            maxX = Math.min(w, maxX + 2);
            minY = Math.max(0, minY - 2);
            maxY = Math.min(h, maxY + 2);

            const topPct = Math.max(0, Math.round((minY / h) * 100));
            const bottomPct = Math.max(0, Math.round(((h - maxY) / h) * 100));
            const leftPct = Math.max(0, Math.round((minX / w) * 100));
            const rightPct = Math.max(0, Math.round(((w - maxX) / w) * 100));

            cropTopSlider.value = topPct;
            cropBottomSlider.value = bottomPct;
            cropLeftSlider.value = leftPct;
            cropRightSlider.value = rightPct;
            safeMarginInput.value = '0'; // 0mm edge-to-edge for zero blank space!

            // Auto-detect orientation: if width > height (like Meesho), rotate 90°
            const labelW = maxX - minX;
            const labelH = maxY - minY;
            if (labelW > labelH * 1.15) {
                rotationSelect.value = '90';
            } else {
                rotationSelect.value = '0';
            }

            updateSliderDisplayValues();
            document.querySelectorAll('.preset-pill').forEach(p => p.classList.remove('active'));
            const customPill = document.getElementById('presetPill_custom');
            if (customPill) customPill.classList.add('active');

            updateOverlaysAndThermal();
        } else {
            // Fallback to Flipkart tight crop preset
            applyPreset('flipkart_tight');
        }
    }

    function resetWorkspace() {
        currentPdfFile = null;
        currentPdfBytes = null;
        pdfJsDoc = null;
        pdfLibDoc = null;
        totalPages = 1;
        currentPageIndex = 0;
        cachedMasterCanvas = null;
        cachedMasterPageIndex = -1;
        pdfFileInput.value = '';
        dropzoneCard.classList.remove('d-none');
        fileInfoBar.classList.add('d-none');
        workspaceSection.classList.add('d-none');
    }

    async function handleFilesSelection(files) {
        if (!files || files.length === 0) return;

        if (typeof PDFLib === 'undefined' || !PDFLib.PDFDocument) {
            alert('PDF processing library is loading. Please wait 2 seconds and try again.');
            return;
        }

        if (typeof pdfjsLib === 'undefined') {
            alert('PDF preview library is loading. Please wait 2 seconds and try again.');
            return;
        }

        const pdfFiles = files.filter(f => f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf'));
        if (pdfFiles.length === 0) {
            alert('Please select valid PDF file(s).');
            return;
        }

        fileNameDisplay.textContent = pdfFiles.length === 1 ? pdfFiles[0].name : `${pdfFiles.length} PDF files selected (Batch)`;
        fileMetaDisplay.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span>Reading PDF pages...';
        dropzoneCard.classList.add('d-none');
        fileInfoBar.classList.remove('d-none');

        try {
            const { PDFDocument } = PDFLib;
            let mergedLibDoc;

            if (pdfFiles.length === 1) {
                currentPdfFile = pdfFiles[0];
                const arrayBuffer = await pdfFiles[0].arrayBuffer();
                currentPdfBytes = new Uint8Array(arrayBuffer);
                pdfLibDoc = await PDFDocument.load(currentPdfBytes.slice());
            } else {
                currentPdfFile = { name: `batch_${pdfFiles.length}_orders.pdf`, size: pdfFiles.reduce((acc, f) => acc + f.size, 0) };
                mergedLibDoc = await PDFDocument.create();

                for (const f of pdfFiles) {
                    const buf = await f.arrayBuffer();
                    const subDoc = await PDFDocument.load(buf);
                    const pages = await mergedLibDoc.copyPages(subDoc, subDoc.getPageIndices());
                    pages.forEach(p => mergedLibDoc.addPage(p));
                }

                currentPdfBytes = await mergedLibDoc.save();
                pdfLibDoc = await PDFDocument.load(currentPdfBytes.slice());
            }

            // Load with PDF.js for rendering preview canvases
            pdfJsDoc = await pdfjsLib.getDocument({ data: currentPdfBytes.slice() }).promise;
            totalPages = pdfJsDoc.numPages;

            const totalSizeKb = (currentPdfBytes.byteLength / 1024).toFixed(1);
            fileMetaDisplay.textContent = `${totalPages} label page(s) • ${totalSizeKb} KB`;
            labelCountBadge.textContent = `${totalPages} Label(s) Ready`;
            currentPageIndex = 0;
            cachedMasterCanvas = null;
            cachedMasterPageIndex = -1;

            workspaceSection.classList.remove('d-none');
            updatePageIndicator();

            // Set default tight preset (Flipkart / Meesho)
            const lowerName = (pdfFiles[0].name || '').toLowerCase();
            if (lowerName.includes('meesho')) {
                applyPreset('meesho_90');
            } else if (lowerName.includes('amazon') || lowerName.includes('easy-ship')) {
                applyPreset('amazon_quad');
            } else {
                applyPreset('flipkart_tight');
            }

            await renderCurrentPage();

            // Run auto-trim on load to eliminate all white space automatically
            setTimeout(autoTrimLabel, 80);
        } catch (err) {
            console.error('Failed to parse PDF:', err);
            alert('Error loading PDF: ' + err.message);
            resetWorkspace();
        }
    }

    function applyPreset(key) {
        activePreset = key;
        const preset = presetsConfig[key];
        if (!preset) return;

        // Update active class on preset pills
        document.querySelectorAll('.preset-pill').forEach(function (pill) {
            pill.classList.toggle('active', pill.getAttribute('data-preset') === key);
        });

        // Set inputs based on preset
        cropTopSlider.value = preset.crop_top;
        cropBottomSlider.value = preset.crop_bottom;
        cropLeftSlider.value = preset.crop_left;
        cropRightSlider.value = preset.crop_right;
        rotationSelect.value = String(preset.rotation);
        paperSizeSelect.value = preset.paper;
        safeMarginInput.value = String(preset.margin);

        updateSliderDisplayValues();
        onPaperSizeChange(false);
        updateOverlaysAndThermal();
    }

    function onCropSliderChange() {
        updateSliderDisplayValues();
        document.querySelectorAll('.preset-pill').forEach(function (pill) {
            pill.classList.remove('active');
        });
        const customPill = document.getElementById('presetPill_custom');
        if (customPill) customPill.classList.add('active');

        updateOverlaysAndThermal();
    }

    function updateSliderDisplayValues() {
        cropTopVal.textContent = cropTopSlider.value + '%';
        cropBottomVal.textContent = cropBottomSlider.value + '%';
        cropLeftVal.textContent = cropLeftSlider.value + '%';
        cropRightVal.textContent = cropRightSlider.value + '%';
    }

    function onPaperSizeChange(shouldUpdatePreview) {
        const val = paperSizeSelect.value;
        if (val === 'custom') {
            customPaperInputs.classList.remove('d-none');
            thermalSizeBadge.textContent = 'Custom mm';
        } else {
            customPaperInputs.classList.add('d-none');
            const paper = paperSizesConfig[val];
            if (paper) {
                thermalSizeBadge.textContent = paper.width_mm + ' × ' + paper.height_mm + ' mm';
            }
        }
        if (shouldUpdatePreview) {
            updateThermalMockupOnly();
        }
    }

    function changePage(delta) {
        const newPage = currentPageIndex + delta;
        if (newPage >= 0 && newPage < totalPages) {
            currentPageIndex = newPage;
            cachedMasterCanvas = null;
            cachedMasterPageIndex = -1;
            updatePageIndicator();
            renderCurrentPage();
        }
    }

    function updatePageIndicator() {
        pageIndicator.textContent = 'Page ' + (currentPageIndex + 1) + ' / ' + totalPages;
        btnPrevPage.disabled = currentPageIndex <= 0;
        btnNextPage.disabled = currentPageIndex >= totalPages - 1;
    }

    // High performance rendering: Renders PDF page once to an offscreen master canvas
    async function renderCurrentPage() {
        if (!pdfJsDoc) return;

        if (activeRenderTask) {
            try { activeRenderTask.cancel(); } catch (e) {}
            activeRenderTask = null;
        }

        try {
            const page = await pdfJsDoc.getPage(currentPageIndex + 1);

            if (!cachedMasterCanvas || cachedMasterPageIndex !== currentPageIndex) {
                const renderScale = 2.0;
                const fullViewport = page.getViewport({ scale: renderScale });

                const offCanvas = document.createElement('canvas');
                offCanvas.width = fullViewport.width;
                offCanvas.height = fullViewport.height;
                const offCtx = offCanvas.getContext('2d');

                activeRenderTask = page.render({
                    canvasContext: offCtx,
                    viewport: fullViewport
                });

                await activeRenderTask.promise;
                activeRenderTask = null;

                cachedMasterCanvas = offCanvas;
                cachedMasterPageIndex = currentPageIndex;
            }

            // Draw to visible source canvas (scaled down to fit UI)
            const maxDim = 460;
            const dispScale = Math.min(maxDim / cachedMasterCanvas.width, maxDim / cachedMasterCanvas.height);
            const dispW = Math.round(cachedMasterCanvas.width * dispScale);
            const dispH = Math.round(cachedMasterCanvas.height * dispScale);

            sourceCanvas.width = dispW;
            sourceCanvas.height = dispH;
            const sCtx = sourceCanvas.getContext('2d');
            sCtx.drawImage(cachedMasterCanvas, 0, 0, dispW, dispH);

            updateOverlaysAndThermal();
        } catch (err) {
            if (err.name !== 'RenderingCancelledException') {
                console.error('Render error:', err);
            }
        }
    }

    // Instant update of overlays and thermal mockup from the cached master canvas (0ms latency)
    function updateOverlaysAndThermal() {
        if (!cachedMasterCanvas) return;
        renderSourceCropOverlay(sourceCanvas.width, sourceCanvas.height);
        renderThermalMockupFromMaster();
    }

    function updateThermalMockupOnly() {
        if (!cachedMasterCanvas) return;
        renderThermalMockupFromMaster();
    }

    function renderSourceCropOverlay(w, h) {
        sourceOverlayCanvas.width = w;
        sourceOverlayCanvas.height = h;
        const ctx = sourceOverlayCanvas.getContext('2d');
        ctx.clearRect(0, 0, w, h);

        const rect = getCropRectPx(w, h);
        const { left, top, right, bottom, width: keptW, height: keptH } = rect;

        // Dark red semi-transparent overlay for discarded parts
        ctx.fillStyle = 'rgba(239, 68, 68, 0.42)';

        // Top discarded strip
        if (top > 0) ctx.fillRect(0, 0, w, top);
        // Bottom discarded strip (tax invoice)
        if (h - bottom > 0) ctx.fillRect(0, bottom, w, h - bottom);
        // Left discarded strip
        if (left > 0) ctx.fillRect(0, top, left, keptH);
        // Right discarded strip
        if (w - right > 0) ctx.fillRect(right, top, w - right, keptH);

        // Dashed green highlight box around kept shipping label
        ctx.strokeStyle = '#22c55e';
        ctx.lineWidth = 2.5;
        ctx.setLineDash([6, 3]);
        ctx.strokeRect(left, top, keptW, keptH);
        ctx.setLineDash([]); // reset

        // Draw 8 interactive drag handles
        ctx.fillStyle = '#22c55e';
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;

        const handleRadius = 5;
        const midX = left + keptW / 2;
        const midY = top + keptH / 2;

        const handles = [
            [left, top], [right, top], [left, bottom], [right, bottom], // 4 corners
            [midX, top], [midX, bottom], [left, midY], [right, midY]    // 4 midpoints
        ];

        handles.forEach(([hx, hy]) => {
            ctx.beginPath();
            ctx.arc(hx, hy, handleRadius, 0, 2 * Math.PI);
            ctx.fill();
            ctx.stroke();
        });

        // Badge: Kept Label
        ctx.fillStyle = '#15803d';
        ctx.fillRect(left + 4, top + 4, 115, 18);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 10px sans-serif';
        ctx.fillText('✂️ KEPT LABEL', left + 8, top + 17);

        // Badge: Discarded Invoice
        if (h - bottom > 26) {
            ctx.fillStyle = '#b91c1c';
            ctx.fillRect(8, bottom + 6, 145, 18);
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 10px sans-serif';
            ctx.fillText('🗑️ DISCARDED INVOICE', 12, bottom + 19);
        }
    }

    function renderThermalMockupFromMaster() {
        if (!cachedMasterCanvas) return;

        const cropTopPct = parseFloat(cropTopSlider.value) / 100;
        const cropBottomPct = parseFloat(cropBottomSlider.value) / 100;
        const cropLeftPct = parseFloat(cropLeftSlider.value) / 100;
        const cropRightPct = parseFloat(cropRightSlider.value) / 100;

        const rotationAngle = parseInt(rotationSelect.value, 10) || 0;
        const safeMarginMm = parseFloat(safeMarginInput.value) || 0;
        const scaleMode = scaleModeSelect.value;

        let targetWidthMm = 100;
        let targetHeightMm = 150;
        const paperVal = paperSizeSelect.value;
        if (paperVal === 'custom') {
            targetWidthMm = parseFloat(customWidthMm.value) || 100;
            targetHeightMm = parseFloat(customHeightMm.value) || 150;
        } else if (paperSizesConfig[paperVal]) {
            targetWidthMm = paperSizesConfig[paperVal].width_mm;
            targetHeightMm = paperSizesConfig[paperVal].height_mm;
        }

        const dpmm = 2.4;
        const mockupW = Math.round(targetWidthMm * dpmm);
        const mockupH = Math.round(targetHeightMm * dpmm);

        thermalCanvas.width = mockupW;
        thermalCanvas.height = mockupH;
        const tCtx = thermalCanvas.getContext('2d');

        // Crisp white thermal paper background
        tCtx.fillStyle = '#ffffff';
        tCtx.fillRect(0, 0, mockupW, mockupH);

        const marginPx = Math.round(safeMarginMm * dpmm);

        // Crop pixel coordinates on cachedMasterCanvas
        const sX = Math.round(cachedMasterCanvas.width * cropLeftPct);
        const sY = Math.round(cachedMasterCanvas.height * cropTopPct);
        const sW = Math.max(1, Math.round(cachedMasterCanvas.width * (1 - cropLeftPct - cropRightPct)));
        const sH = Math.max(1, Math.round(cachedMasterCanvas.height * (1 - cropTopPct - cropBottomPct)));

        const availW = Math.max(10, mockupW - (marginPx * 2));
        const availH = Math.max(10, mockupH - (marginPx * 2));

        const isRotated90or270 = (rotationAngle === 90 || rotationAngle === 270);
        const contentW = isRotated90or270 ? sH : sW;
        const contentH = isRotated90or270 ? sW : sH;

        tCtx.save();
        tCtx.translate(mockupW / 2, mockupH / 2);
        tCtx.rotate((rotationAngle * Math.PI) / 180);

        if (scaleMode === 'fill') {
            // Fill 4x6 exactly (Zero Blank Space)
            const dW = isRotated90or270 ? availH : availW;
            const dH = isRotated90or270 ? availW : availH;
            tCtx.drawImage(cachedMasterCanvas, sX, sY, sW, sH, -dW / 2, -dH / 2, dW, dH);
        } else {
            const scale = Math.min(availW / contentW, availH / contentH);
            const drawnW = sW * scale;
            const drawnH = sH * scale;
            tCtx.drawImage(cachedMasterCanvas, sX, sY, sW, sH, -drawnW / 2, -drawnH / 2, drawnW, drawnH);
        }
        tCtx.restore();

        // Subtle guide line if safe margin is configured
        if (marginPx > 0) {
            tCtx.strokeStyle = 'rgba(34, 197, 94, 0.4)';
            tCtx.lineWidth = 1;
            tCtx.setLineDash([4, 4]);
            tCtx.strokeRect(marginPx, marginPx, mockupW - (marginPx * 2), mockupH - (marginPx * 2));
        }
    }

    // Generate output thermal PDF with vector fidelity via PDF-Lib
    async function buildThermalPdfBlob() {
        if (!pdfLibDoc) throw new Error('No PDF loaded');

        const { PDFDocument, degrees } = PDFLib;
        const outDoc = await PDFDocument.create();

        const mmToPt = 72 / 25.4;
        let targetWidthPt = 100 * mmToPt;
        let targetHeightPt = 150 * mmToPt;

        const paperVal = paperSizeSelect.value;
        if (paperVal === 'custom') {
            const cW = parseFloat(customWidthMm.value) || 100;
            const cH = parseFloat(customHeightMm.value) || 150;
            targetWidthPt = cW * mmToPt;
            targetHeightPt = cH * mmToPt;
        } else if (paperSizesConfig[paperVal]) {
            targetWidthPt = paperSizesConfig[paperVal].width_pt;
            targetHeightPt = paperSizesConfig[paperVal].height_pt;
        }

        const safeMarginMm = parseFloat(safeMarginInput.value) || 0;
        const safeMarginPt = safeMarginMm * mmToPt;
        const rotationAngle = parseInt(rotationSelect.value, 10) || 0;
        const scaleMode = scaleModeSelect.value;

        const cropTopPct = parseFloat(cropTopSlider.value) / 100;
        const cropBottomPct = parseFloat(cropBottomSlider.value) / 100;
        const cropLeftPct = parseFloat(cropLeftSlider.value) / 100;
        const cropRightPct = parseFloat(cropRightSlider.value) / 100;

        const pageCount = pdfLibDoc.getPageCount();

        for (let i = 0; i < pageCount; i++) {
            const srcPage = pdfLibDoc.getPage(i);
            const { width: srcW, height: srcH } = srcPage.getSize();

            const cropX = srcW * cropLeftPct;
            const cropY = srcH * cropBottomPct;
            const cropWidth = srcW * (1 - cropLeftPct - cropRightPct);
            const cropHeight = srcH * (1 - cropTopPct - cropBottomPct);

            const embeddedPage = await outDoc.embedPage(srcPage, {
                left: cropX,
                bottom: cropY,
                right: cropX + cropWidth,
                top: cropY + cropHeight
            });

            const targetPage = outDoc.addPage([targetWidthPt, targetHeightPt]);

            const availW = Math.max(5, targetWidthPt - (safeMarginPt * 2));
            const availH = Math.max(5, targetHeightPt - (safeMarginPt * 2));

            const isRotated90or270 = (rotationAngle === 90 || rotationAngle === 270);
            const contentW = isRotated90or270 ? cropHeight : cropWidth;
            const contentH = isRotated90or270 ? cropWidth : cropHeight;

            let drawX = 0;
            let drawY = 0;
            let effScaleX = 1;
            let effScaleY = 1;
            let pdfRotationDegrees = 0;

            if (scaleMode === 'fill') {
                // Exact 4x6" standard thermal format fill (Zero Blank Space)
                if (rotationAngle === 0) {
                    pdfRotationDegrees = 0;
                    effScaleX = availW / cropWidth;
                    effScaleY = availH / cropHeight;
                    drawX = safeMarginPt;
                    drawY = safeMarginPt;
                } else if (rotationAngle === 90) {
                    pdfRotationDegrees = 270;
                    effScaleX = availH / cropWidth;
                    effScaleY = availW / cropHeight;
                    drawX = safeMarginPt;
                    drawY = safeMarginPt + availH;
                } else if (rotationAngle === 180) {
                    pdfRotationDegrees = 180;
                    effScaleX = availW / cropWidth;
                    effScaleY = availH / cropHeight;
                    drawX = safeMarginPt + availW;
                    drawY = safeMarginPt + availH;
                } else if (rotationAngle === 270) {
                    pdfRotationDegrees = 90;
                    effScaleX = availH / cropWidth;
                    effScaleY = availW / cropHeight;
                    drawX = safeMarginPt + availW;
                    drawY = safeMarginPt;
                }
            } else {
                // Proportional fit
                const uniformScale = Math.min(availW / contentW, availH / contentH);
                effScaleX = uniformScale;
                effScaleY = uniformScale;
                const drawnW = cropWidth * uniformScale;
                const drawnH = cropHeight * uniformScale;

                if (rotationAngle === 0) {
                    pdfRotationDegrees = 0;
                    drawX = (targetWidthPt - drawnW) / 2;
                    drawY = (targetHeightPt - drawnH) / 2;
                } else if (rotationAngle === 90) {
                    pdfRotationDegrees = 270;
                    drawX = (targetWidthPt - drawnH) / 2;
                    drawY = (targetHeightPt + drawnW) / 2;
                } else if (rotationAngle === 180) {
                    pdfRotationDegrees = 180;
                    drawX = (targetWidthPt + drawnW) / 2;
                    drawY = (targetHeightPt + drawnH) / 2;
                } else if (rotationAngle === 270) {
                    pdfRotationDegrees = 90;
                    drawX = (targetWidthPt + drawnH) / 2;
                    drawY = (targetHeightPt - drawnW) / 2;
                }
            }

            targetPage.drawPage(embeddedPage, {
                x: drawX,
                y: drawY,
                xScale: effScaleX,
                yScale: effScaleY,
                rotate: degrees(pdfRotationDegrees)
            });
        }

        const pdfBytes = await outDoc.save();
        return new Blob([pdfBytes], { type: 'application/pdf' });
    }

    async function downloadCroppedPdf() {
        const origText = btnDownloadBtn.innerHTML;
        btnDownloadBtn.disabled = true;
        btnDownloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Processing...';

        try {
            const blob = await buildThermalPdfBlob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const baseName = currentPdfFile && currentPdfFile.name ? currentPdfFile.name.replace(/\.pdf$/i, '') : 'shipping_labels';
            a.href = url;
            a.download = baseName + '_thermal_4x6.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
        } catch (err) {
            console.error('Download error:', err);
            alert('Failed to generate thermal PDF: ' + err.message);
        } finally {
            btnDownloadBtn.disabled = false;
            btnDownloadBtn.innerHTML = origText;
        }
    }

    async function printThermalDirectly() {
        const origText = btnPrintBtn.innerHTML;
        btnPrintBtn.disabled = true;
        btnPrintBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Preparing...';

        try {
            const blob = await buildThermalPdfBlob();
            const blobUrl = URL.createObjectURL(blob);

            const iframe = document.getElementById('printIframe');
            iframe.src = blobUrl;
            iframe.onload = function () {
                setTimeout(function () {
                    try {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    } catch (e) {
                        console.error('Print iframe error:', e);
                        window.open(blobUrl, '_blank');
                    }
                    btnPrintBtn.disabled = false;
                    btnPrintBtn.innerHTML = origText;
                }, 400);
            };
        } catch (err) {
            console.error('Print error:', err);
            alert('Failed to launch print dialog: ' + err.message);
            btnPrintBtn.disabled = false;
            btnPrintBtn.innerHTML = origText;
        }
    }
});
</script>
@endpush
