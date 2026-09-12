@php
    $selectedSections = $profile ? ($profile->sections ?? []) : (old('sections', []));
    $selectedConcerns = $profile ? ($profile->concerns ?? []) : (old('concerns', []));
    $positioningValue = $profile ? ($profile->positioning ?? '') : old('positioning');
    $benefitsValue = $profile ? implode("\n", $profile->approved_benefits ?? []) : old('approved_benefits');
    $usageValue = $profile ? implode("\n", $profile->approved_usage_context ?? []) : old('approved_usage_context');
    $precautionsValue = $profile ? implode("\n", $profile->approved_precautions ?? []) : old('approved_precautions');
    $suitabilityValue = $profile ? implode("\n", $profile->suitability_notes ?? []) : old('suitability_notes');

    $concernsBySection = [];
    foreach ($concerns as $concern) {
        $concernsBySection[$concern->section()->value][] = $concern;
    }
@endphp

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-2">Sections</h6>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($sections as $section)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="sections[]" value="{{ $section->value }}"
                           id="section_{{ $section->value }}"
                           {{ in_array($section->value, $selectedSections, true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="section_{{ $section->value }}">{{ $section->label() }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-2">Suitable Concerns</h6>
        <p class="text-muted small mb-3">Controlled vocabulary — only approved suitability codes are stored. A concern must be tagged before this profile can ever match it.</p>
        @foreach ($concernsBySection as $sectionCode => $sectionConcerns)
            <div class="mb-3">
                <div class="small fw-semibold text-uppercase text-muted mb-2">{{ ucfirst($sectionCode) }}</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach ($sectionConcerns as $concern)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="concerns[]" value="{{ $concern->value }}"
                                   id="concern_{{ $concern->value }}"
                                   {{ in_array($concern->value, $selectedConcerns, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="concern_{{ $concern->value }}">{{ $concern->label() }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold" for="positioning">Product Positioning</label>
        <p class="text-muted small">How Dadi should naturally describe the product. Not a medical claim.</p>
        <textarea class="form-control" id="positioning" name="positioning" rows="3">{{ $positioningValue }}</textarea>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold" for="approved_benefits">Approved Benefits <span class="text-muted fw-normal">(one per line)</span></label>
        <p class="text-muted small">Only human-approved benefit statements. Dadi never invents benefits.</p>
        <textarea class="form-control" id="approved_benefits" name="approved_benefits" rows="4">{{ $benefitsValue }}</textarea>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold" for="approved_usage_context">Approved Usage Context <span class="text-muted fw-normal">(one per line)</span></label>
        <textarea class="form-control" id="approved_usage_context" name="approved_usage_context" rows="3">{{ $usageValue }}</textarea>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold" for="approved_precautions">Approved Precautions <span class="text-muted fw-normal">(one per line)</span></label>
        <textarea class="form-control" id="approved_precautions" name="approved_precautions" rows="3">{{ $precautionsValue }}</textarea>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label fw-semibold" for="suitability_notes">Suitability Notes <span class="text-muted fw-normal">(one per line)</span></label>
        <p class="text-muted small">Human-authored notes about situations where the product may be considered.</p>
        <textarea class="form-control" id="suitability_notes" name="suitability_notes" rows="3">{{ $suitabilityValue }}</textarea>
    </div>
</div>

<div class="alert alert-info small mb-3">
    SKU, price, MRP, stock, inventory, discount, coupon, cart, checkout and order are never stored here. Those values
    always come live from the product catalogue.
</div>