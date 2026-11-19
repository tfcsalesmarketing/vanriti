@php
    $aPlus = old('a_plus_content', $product->a_plus_content ?? []);
    $modules = $aPlus['modules'] ?? [];
    $hasAPlus = old('has_a_plus', $product->has_a_plus ?? false);

    $apHero = collect($modules)->firstWhere('type', 'hero_banner') ?? [];
    $apFeatures = collect($modules)->firstWhere('type', 'features_grid') ?? [];
    $apStory = collect($modules)->firstWhere('type', 'split_story') ?? [];
    $apHerbs = collect($modules)->firstWhere('type', 'ingredients_spotlight') ?? [];
    $apRitual = collect($modules)->firstWhere('type', 'how_to_use_ritual') ?? [];
    $apStats = collect($modules)->firstWhere('type', 'clinical_stats') ?? [];
@endphp

<div class="card mb-3 border-success border-opacity-25" id="vrAdminAPlusCard">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center bg-light">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-stars text-success fs-5"></i>
            <span>Amazon A+ Content Builder (Enhanced Brand Content)</span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" name="has_a_plus" value="1" class="form-check-input" id="hasAPlusSwitch" {{ $hasAPlus ? 'checked' : '' }}>
                <label class="form-check-label small fw-semibold" for="hasAPlusSwitch">Enable Custom A+</label>
            </div>
        </div>
    </div>

    <div class="card-body">
        <p class="text-muted small mb-3">
            Transform this product page into a luxury visual storefront using modular Amazon A+ storytelling blocks. 
            When <strong>Enabled</strong>, the custom modules configured below will display. When disabled, the storefront automatically renders the elegant smart fallback.
        </p>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-sm btn-outline-success" id="btnAutoDraftAPlus">
                <i class="bi bi-magic me-1"></i> Auto-Draft Template from Product Details
            </button>
        </div>

        <div class="accordion" id="vrAPlusModulesAccordion">
            <!-- MODULE 1: Cinematic Brand Hero Banner -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModHero">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 1</span>
                        <strong>Cinematic Brand Hero Banner</strong>
                    </button>
                </h2>
                <div id="collapseModHero" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Eyebrow Tag</label>
                                <input type="text" name="a_plus_modules[hero_banner][eyebrow]" class="form-control form-control-sm" value="{{ $apHero['eyebrow'] ?? 'Ayurvedic Formulations' }}" placeholder="e.g. Ancient Radiance Formula">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Hero Headline</label>
                                <input type="text" name="a_plus_modules[hero_banner][title]" class="form-control form-control-sm" value="{{ $apHero['title'] ?? '' }}" placeholder="e.g. The Sacred Secret of Golden Radiance">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Hero Description Narrative</label>
                                <textarea name="a_plus_modules[hero_banner][description]" class="form-control form-control-sm" rows="2" placeholder="Rich botanical narrative explaining formulation origin...">{{ $apHero['description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Banner Image URL</label>
                                <input type="text" name="a_plus_modules[hero_banner][image]" class="form-control form-control-sm" value="{{ $apHero['image'] ?? '' }}" placeholder="https://... or /images/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Or Upload Banner File</label>
                                <input type="file" name="a_plus_hero_file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Recommended: 1464 &times; 600 px (JPEG / PNG / WebP)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODULE 2: 4-Column Ayurvedic Value Pillars -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModPillars">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 2</span>
                        <strong>4-Column Ayurvedic Value Pillars</strong>
                    </button>
                </h2>
                <div id="collapseModPillars" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Section Heading</label>
                                <input type="text" name="a_plus_modules[features_grid][title]" class="form-control form-control-sm" value="{{ $apFeatures['title'] ?? 'The Vanriti Purity Promise' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Section Subtitle</label>
                                <input type="text" name="a_plus_modules[features_grid][subtitle]" class="form-control form-control-sm" value="{{ $apFeatures['subtitle'] ?? 'Classical Ayurvedic science formulated for modern cellular wellness.' }}">
                            </div>
                        </div>

                        <div class="row g-2">
                            @for ($i = 0; $i < 4; $i++)
                                @php
                                    $item = $apFeatures['items'][$i] ?? [];
                                    $defaultIcons = ['ri-drop-line', 'ri-leaf-line', 'ri-shield-check-line', 'ri-heart-3-line'];
                                    $defaultTitles = ['100% Ayurvedic Kwath', 'Pure & Toxin-Free', 'Ayush Certified & Tested', 'Ethical & Cruelty-Free'];
                                @endphp
                                <div class="col-md-6 col-lg-3">
                                    <div class="border rounded p-2 bg-white">
                                        <div class="fw-semibold small text-muted mb-2">Card #{{ $i + 1 }}</div>
                                        <div class="mb-2">
                                            <input type="text" name="a_plus_modules[features_grid][items][{{ $i }}][icon]" class="form-control form-control-sm mb-1" value="{{ $item['icon'] ?? ($defaultIcons[$i] ?? 'ri-leaf-line') }}" placeholder="Icon class (e.g. ri-leaf-line)">
                                            <input type="text" name="a_plus_modules[features_grid][items][{{ $i }}][title]" class="form-control form-control-sm fw-semibold" value="{{ $item['title'] ?? ($defaultTitles[$i] ?? '') }}" placeholder="Card Title">
                                        </div>
                                        <textarea name="a_plus_modules[features_grid][items][{{ $i }}][description]" class="form-control form-control-sm" rows="2" placeholder="Description...">{{ $item['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODULE 3: Alternating Split Story Block -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModStory">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 3</span>
                        <strong>Split Story Block (Botanical Extraction & Narrative)</strong>
                    </button>
                </h2>
                <div id="collapseModStory" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Eyebrow Tag</label>
                                <input type="text" name="a_plus_modules[split_story][subtitle]" class="form-control form-control-sm" value="{{ $apStory['subtitle'] ?? 'Botanical Extraction & Science' }}" placeholder="e.g. Botanical Extraction">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Story Title</label>
                                <input type="text" name="a_plus_modules[split_story][title]" class="form-control form-control-sm" value="{{ $apStory['title'] ?? 'Formulated for Transformative Care' }}" placeholder="Story Title">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Story Narrative Content</label>
                                <textarea name="a_plus_modules[split_story][content]" class="form-control form-control-sm" rows="3" placeholder="Explain the botanical action, formulation lineage, or scientific validation...">{{ $apStory['content'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Story Image URL</label>
                                <input type="text" name="a_plus_modules[split_story][image]" class="form-control form-control-sm" value="{{ $apStory['image'] ?? '' }}" placeholder="https://... or /images/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Or Upload Story Image</label>
                                <input type="file" name="a_plus_story_file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Recommended: 800 &times; 800 px (Square or 4:3)</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Key Bullet Points (one per line)</label>
                                @php
                                    $bulletsStr = !empty($apStory['bullets']) && is_array($apStory['bullets']) ? implode("\n", $apStory['bullets']) : '';
                                @endphp
                                <textarea name="a_plus_modules[split_story][bullets_raw]" class="form-control form-control-sm" rows="3" placeholder="Enriched with Kashmiri Saffron&#10;Cold-Pressed Sandalwood Base&#10;Visible radiance in 21 days">{{ $bulletsStr }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODULE 4: Hero Botanical Ingredients Spotlight -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModHerbs">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 4</span>
                        <strong>Hero Botanical Ingredients Spotlight</strong>
                    </button>
                </h2>
                <div id="collapseModHerbs" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Section Heading</label>
                                <input type="text" name="a_plus_modules[ingredients_spotlight][title]" class="form-control form-control-sm" value="{{ $apHerbs['title'] ?? 'Key Botanical Actives' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Section Subtitle</label>
                                <input type="text" name="a_plus_modules[ingredients_spotlight][subtitle]" class="form-control form-control-sm" value="{{ $apHerbs['subtitle'] ?? 'Handpicked medicinal herbs working in synergistic harmony.' }}">
                            </div>
                        </div>

                        <div class="row g-2">
                            @for ($k = 0; $k < 3; $k++)
                                @php
                                    $herb = $apHerbs['items'][$k] ?? [];
                                @endphp
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-white">
                                        <div class="fw-semibold small text-muted mb-2">Herb #{{ $k + 1 }}</div>
                                        <input type="text" name="a_plus_modules[ingredients_spotlight][items][{{ $k }}][name]" class="form-control form-control-sm mb-1 fw-semibold" value="{{ $herb['name'] ?? '' }}" placeholder="Herb Name (e.g. Kashmiri Saffron)">
                                        <input type="text" name="a_plus_modules[ingredients_spotlight][items][{{ $k }}][botanical]" class="form-control form-control-sm mb-1" value="{{ $herb['botanical'] ?? '' }}" placeholder="Botanical / Latin Name">
                                        <textarea name="a_plus_modules[ingredients_spotlight][items][{{ $k }}][benefit]" class="form-control form-control-sm mb-1" rows="2" placeholder="Key Benefit...">{{ $herb['benefit'] ?? '' }}</textarea>
                                        <input type="text" name="a_plus_modules[ingredients_spotlight][items][{{ $k }}][image]" class="form-control form-control-sm" value="{{ $herb['image'] ?? '' }}" placeholder="Herb Image URL">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODULE 5: 3-Step Ayurvedic Daily Ritual -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModRitual">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 5</span>
                        <strong>3-Step Ayurvedic Daily Ritual (How to Use)</strong>
                    </button>
                </h2>
                <div id="collapseModRitual" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Ritual Title</label>
                                <input type="text" name="a_plus_modules[how_to_use_ritual][title]" class="form-control form-control-sm" value="{{ $apRitual['title'] ?? 'The Ayurvedic Daily Ritual' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Ayurvedic Tip</label>
                                <input type="text" name="a_plus_modules[how_to_use_ritual][tip]" class="form-control form-control-sm" value="{{ $apRitual['tip'] ?? 'For optimal results, apply consistently during your morning and evening skincare ritual.' }}">
                            </div>
                        </div>

                        <div class="row g-2">
                            @for ($s = 0; $s < 3; $s++)
                                @php
                                    $step = $apRitual['steps'][$s] ?? [];
                                    $stepDefaults = [
                                        ['title' => 'Mindful Preparation', 'desc' => 'Cleanse with lukewarm water and gently pat skin dry.'],
                                        ['title' => 'Dispense & Activate', 'desc' => 'Take 3-4 drops and warm gently between your fingertips.'],
                                        ['title' => 'Upward Gentle Strokes', 'desc' => 'Apply in mindful upward circular motions until fully absorbed.'],
                                    ];
                                @endphp
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-white">
                                        <div class="fw-semibold small text-muted mb-2">STEP 0{{ $s + 1 }}</div>
                                        <input type="text" name="a_plus_modules[how_to_use_ritual][steps][{{ $s }}][title]" class="form-control form-control-sm mb-1 fw-semibold" value="{{ $step['title'] ?? ($stepDefaults[$s]['title'] ?? '') }}" placeholder="Step Title">
                                        <textarea name="a_plus_modules[how_to_use_ritual][steps][{{ $s }}][desc]" class="form-control form-control-sm" rows="2" placeholder="Step description...">{{ $step['desc'] ?? ($stepDefaults[$s]['desc'] ?? '') }}</textarea>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODULE 6: Clinical Results & Social Proof Counter -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModStats">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 me-2">Module 6</span>
                        <strong>Clinical Results &amp; Social Proof Counter</strong>
                    </button>
                </h2>
                <div id="collapseModStats" class="accordion-collapse collapse" data-bs-parent="#vrAPlusModulesAccordion">
                    <div class="accordion-body bg-light bg-opacity-25">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Banner Heading</label>
                                <input type="text" name="a_plus_modules[clinical_stats][title]" class="form-control form-control-sm" value="{{ $apStats['title'] ?? 'Real Formulations, Real Results' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Banner Subtitle</label>
                                <input type="text" name="a_plus_modules[clinical_stats][subtitle]" class="form-control form-control-sm" value="{{ $apStats['subtitle'] ?? 'Clinically tested and validated by authentic Ayurvedic practitioners.' }}">
                            </div>
                        </div>

                        <div class="row g-2">
                            @for ($m = 0; $m < 3; $m++)
                                @php
                                    $stat = $apStats['stats'][$m] ?? [];
                                    $statDefaults = [
                                        ['number' => '100%', 'label' => 'Classical Ayurvedic Actives', 'sub' => 'Zero artificial fillers'],
                                        ['number' => '96%', 'label' => 'Customer Satisfaction', 'sub' => 'Visible results in 21 days'],
                                        ['number' => 'GMP', 'label' => 'Certified Manufacturing', 'sub' => 'Safe & standardized batches'],
                                    ];
                                @endphp
                                <div class="col-md-4">
                                    <div class="border rounded p-2 bg-white">
                                        <div class="fw-semibold small text-muted mb-2">Metric #{{ $m + 1 }}</div>
                                        <input type="text" name="a_plus_modules[clinical_stats][stats][{{ $m }}][number]" class="form-control form-control-sm mb-1 fw-bold text-success fs-6" value="{{ $stat['number'] ?? ($statDefaults[$m]['number'] ?? '') }}" placeholder="e.g. 96%">
                                        <input type="text" name="a_plus_modules[clinical_stats][stats][{{ $m }}][label]" class="form-control form-control-sm mb-1 fw-semibold" value="{{ $stat['label'] ?? ($statDefaults[$m]['label'] ?? '') }}" placeholder="Metric Label">
                                        <input type="text" name="a_plus_modules[clinical_stats][stats][{{ $m }}][sub]" class="form-control form-control-sm" value="{{ $stat['sub'] ?? ($statDefaults[$m]['sub'] ?? '') }}" placeholder="Supporting Subtitle">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

