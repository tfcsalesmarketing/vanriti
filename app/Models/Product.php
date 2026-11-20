<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Previous slug stored during an update so a 301 redirect can be written after save.
     */
    public ?string $slugRedirectFrom = null;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Product $product): void {
            if (is_string($product->sku)) {
                $product->sku = strtoupper(trim($product->sku));
                if ($product->sku === '') {
                    $product->sku = null;
                }
            }
        });

        static::creating(function (Product $product): void {
            $product->slug = static::uniqueSlugFromName((string) $product->name);
            if (empty($product->channel)) {
                $product->channel = 'vanriti';
            }
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('name')) {
                $product->slug = static::uniqueSlugFromName((string) $product->name, $product->id);
            }

            $from = $product->getOriginal('slug');
            if (is_string($from) && $from !== '' && $product->isDirty('slug') && $from !== $product->slug) {
                $product->slugRedirectFrom = $from;
            }
        });

        static::updated(function (Product $product): void {
            if (! is_string($product->slugRedirectFrom) || $product->slugRedirectFrom === '') {
                return;
            }

            ProductSlugRedirect::query()->updateOrCreate(
                ['slug' => $product->slugRedirectFrom],
                ['product_id' => $product->id]
            );
            $product->slugRedirectFrom = null;
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'barcode',
        'short_description',
        'description',
        'highlights',
        'ingredients',
        'benefits',
        'how_to_use',
        'directions',
        'warnings',
        'precautions',
        'disclaimer',
        'unit',
        'weight',
        'mrp',
        'selling_price',
        'discount_percent',
        'gst_rate',
        'stock',
        'low_stock_threshold',
        'hsn_code',
        'manufacturer',
        'manufacturer_address',
        'country_of_origin',
        'shelf_life',

        'meta_title',
        'meta_description',
        'meta_keywords',
        'search_keywords',
        'video_url',
        'amazon_url',
        'flipkart_url',
        'a_plus_content',
        'has_a_plus',
        'status',
        'channel',
        'is_featured',
        'is_bestseller',
        'is_new_arrival',
        'total_sold',
        'review_count',
        'review_rating',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new_arrival' => 'boolean',
            'mrp' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'a_plus_content' => 'array',
            'has_a_plus' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class, 'faq_product');
    }

    public function primaryCategory()
    {
        return $this->categories()->wherePivot('is_primary', true)->first();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function dadiProductProfile(): HasOne
    {
        return $this->hasOne(DadiProductProfile::class);
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProductSlugRedirect::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('status', 'active')->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('type', 'main')->orderBy('sort_order')->limit(1);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved')->orderByDesc('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeVanriti(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('channel', 'vanriti')->orWhereNull('channel');
        });
    }

    public function scopeTfc(Builder $query): Builder
    {
        return $query->where('channel', 'tfc');
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestsellers(Builder $query): Builder
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('is_new_arrival', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0))
                ->orWhere(function ($noVariant) {
                    $noVariant->doesntHave('variants')->where('stock', '>', 0);
                });
        });
    }

    public function getReviewCountAttribute(): int
    {
        $reviews = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : $this->approvedReviews()->get();

        return $reviews->count();
    }

    public function getReviewRatingAttribute(): float
    {
        $reviews = $this->relationLoaded('approvedReviews') ? $this->approvedReviews : $this->approvedReviews()->get();

        return $reviews->count() ? round((float) $reviews->avg('rating'), 1) : 0.0;
    }

    public function discountPercent(): float
    {
        if (! $this->mrp || $this->mrp <= 0) {
            return 0;
        }

        return round((($this->mrp - $this->selling_price) / $this->mrp) * 100, 1);
    }

    public function getAvailableStock(): int
    {
        if ($this->variants()->exists()) {
            return (int) $this->variants()->sum('stock');
        }

        return (int) $this->stock;
    }

    public function hasStock(): bool
    {
        return $this->getAvailableStock() > 0;
    }

    public function isLowStock(): bool
    {
        $threshold = $this->low_stock_threshold ?: 0;

        return $this->getAvailableStock() > 0 && $this->getAvailableStock() <= $threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->getAvailableStock() <= 0;
    }

    public function getPrimaryImage()
    {
        return $this->images()->where('type', 'main')->first()
            ?? $this->images()->first();
    }

    public function getWhatsAppImage(): ?ProductImage
    {
        return $this->images()->where('type', 'whatsapp')->first()
            ?? $this->getPrimaryImage();
    }

    public function getWhatsAppOrderUrl(): string
    {
        $bizPhone = preg_replace('/[^0-9]/', '', (string) setting('whatsapp_business_phone', setting('whatsapp_phone_number_id', '919876543210')));
        if (strlen($bizPhone) === 10) {
            $bizPhone = '91' . $bizPhone;
        }
        $msg = "Hi Vanriti, I would like to order *{$this->name}* (SKU: {$this->sku}) at " . format_price($this->selling_price) . ". Link: " . route('product.show', $this->slug);

        return "https://wa.me/{$bizPhone}?text=" . rawurlencode($msg);
    }

    public function hasAmazonLink(): bool
    {
        return filled($this->amazon_url);
    }

    public function hasFlipkartLink(): bool
    {
        return filled($this->flipkart_url);
    }

    public function hasMarketplaceLinks(): bool
    {
        return $this->hasAmazonLink() || $this->hasFlipkartLink();
    }

    /**
     * Determine whether custom A+ Content has been explicitly authored and enabled.
     */
    public function hasAPlusContent(): bool
    {
        return (bool) $this->has_a_plus && ! empty($this->a_plus_content['modules']);
    }

    /**
     * Get explicitly configured A+ modules array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAPlusModules(): array
    {
        return $this->a_plus_content['modules'] ?? [];
    }

    /**
     * Get effective A+ modules: custom modules if enabled, otherwise synthesizes
     * compliant smart fallback modules from verified product data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEffectiveAPlusModules(): array
    {
        if ($this->hasAPlusContent()) {
            return $this->getAPlusModules();
        }

        $modules = [];

        // 1. Detect Category Profile & Classification
        $categorySlugs = $this->relationLoaded('categories')
            ? $this->categories->pluck('slug')->all()
            : $this->categories()->pluck('slug')->all();
        $allCats = implode(' ', $categorySlugs);
        $nameLower = strtolower($this->name ?? '');

        $isHairCleansing = str_contains($allCats, 'hair-cleansing')
            || str_contains($nameLower, 'cleansing')
            || str_contains($nameLower, 'reetha')
            || str_contains($nameLower, 'shikakai');

        $isFacePack = str_contains($allCats, 'face-packs-masks')
            || str_contains($allCats, 'skin-care')
            || str_contains($nameLower, 'face')
            || str_contains($nameLower, 'multani')
            || str_contains($nameLower, 'rose')
            || str_contains($nameLower, 'skin');

        $isHairMask = str_contains($allCats, 'hair-care')
            || str_contains($nameLower, 'hair')
            || str_contains($nameLower, 'bhringraj')
            || str_contains($nameLower, 'brahmi')
            || str_contains($nameLower, 'hibiscus')
            || str_contains($nameLower, 'methi');

        $isCombo = str_contains($nameLower, 'combo')
            || str_contains($nameLower, 'pack of 2')
            || str_contains($nameLower, 'pack of 3')
            || str_contains($nameLower, 'kit')
            || str_contains($nameLower, ' and ');

        // Weight resolution
        $weightVal = (int) $this->weight;
        $unit = $this->unit ?? 'g';
        if ($unit === 'gm') {
            $unit = 'g';
        }
        $weightStr = $weightVal > 0 ? "{$weightVal}{$unit}" : 'Standard Pack';

        // Verified botanical dictionary for featured ingredients
        $herbDictionary = [
            'amla' => [
                'name' => 'Amla (Indian Gooseberry)',
                'botanical' => 'Phyllanthus emblica',
                'benefit' => 'Rich in natural fruit acids and antioxidants, traditionally used to condition hair and refresh scalp.',
            ],
            'reetha' => [
                'name' => 'Reetha (Soapnut)',
                'botanical' => 'Sapindus mukorossi',
                'benefit' => 'Natural saponin-rich botanical traditionally utilized as a gentle, foaming herbal hair and scalp cleanser.',
            ],
            'shikakai' => [
                'name' => 'Shikakai',
                'botanical' => 'Acacia concinna',
                'benefit' => 'Traditional fruit pod powder renowned for gentle low-pH scalp cleansing and detangling care.',
            ],
            'bhringraj' => [
                'name' => 'Bhringraj',
                'botanical' => 'Eclipta alba',
                'benefit' => 'Classical false daisy herb celebrated in traditional Ayurvedic routines for strengthening hair roots.',
            ],
            'hibiscus' => [
                'name' => 'Hibiscus Flower',
                'botanical' => 'Hibiscus rosa-sinensis',
                'benefit' => 'Naturally conditioning petal powder rich in botanical mucilage that leaves hair silky and manageable.',
            ],
            'multani' => [
                'name' => 'Multani Mitti (Fuller’s Earth)',
                'botanical' => 'Solum Fullonum',
                'benefit' => 'Naturally absorbent mineral clay traditionally used in deep-cleansing facial and scalp packs.',
            ],
            'rose' => [
                'name' => 'Rose Petal',
                'botanical' => 'Rosa centifolia',
                'benefit' => 'Aromatic petal powder traditionally prized for cooling, toning, and softening delicate facial skin.',
            ],
            'neem' => [
                'name' => 'Neem Leaf',
                'botanical' => 'Azadirachta indica',
                'benefit' => 'Clarifying and purifying botanical powder widely incorporated in home scalp and skin packs.',
            ],
            'moringa' => [
                'name' => 'Moringa Leaf',
                'botanical' => 'Moringa oleifera',
                'benefit' => 'Botanical powder rich in plant nutrients, traditionally utilized for revitalizing hair and skin masks.',
            ],
            'beetroot' => [
                'name' => 'Beetroot',
                'botanical' => 'Beta vulgaris',
                'benefit' => 'Naturally pigmented root botanical traditionally blended into DIY conditioning pastes.',
            ],
            'methi' => [
                'name' => 'Fenugreek (Methi)',
                'botanical' => 'Trigonella foenum-graecum',
                'benefit' => 'Protein-rich seeds traditionally ground into rich, slip-enhancing botanical hair masks.',
            ],
            'brahmi' => [
                'name' => 'Brahmi',
                'botanical' => 'Bacopa monnieri',
                'benefit' => 'Time-honored rejuvenating botanical traditionally used in hair packs to soothe and nourish the scalp.',
            ],
        ];

        $ingredientText = strtolower(strip_tags($this->ingredients ?? '') . ' ' . ($this->name ?? ''));
        $matchedHerbs = [];
        foreach ($herbDictionary as $key => $herbData) {
            if (str_contains($ingredientText, $key)) {
                $matchedHerbs[] = $herbData;
                if (count($matchedHerbs) >= 3) {
                    break;
                }
            }
        }

        // Helper to detect and exclude food/dietary/ingestion text from cosmetic A+ content
        $isIngestionText = function (?string $text): bool {
            if (blank($text)) {
                return false;
            }
            return (bool) preg_match('/serving|recipe|intake|dietary|beverage|drink|food\s*[-_]?grade|consume|consumption/i', $text);
        };

        // Primary Image
        $primaryImgPath = $this->getPrimaryImage()?->image_path;
        $primaryImgUrl = $primaryImgPath ? image_url($primaryImgPath, 'favicon.ico') : asset('images/logo.webp');

        // Clean Description Narrative for Hero
        $heroDesc = strip_tags($this->short_description ?? '');
        if (blank($heroDesc) || $isIngestionText($heroDesc)) {
            $heroDesc = strip_tags($this->benefits ?? '');
        }
        if (blank($heroDesc) || $isIngestionText($heroDesc)) {
            $heroDesc = 'Carefully processed pure botanical powder formulated for traditional personal care routines.';
        }
        $heroDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $heroDesc)), 300);

        // Single powder detection
        $isSinglePowder = str_contains($nameLower, 'pure ')
            || (! $isCombo && count($matchedHerbs) <= 1);

        // Eyebrow determination
        if ($isCombo) {
            $eyebrow = 'Botanical Combination Pack';
        } elseif ($isSinglePowder) {
            $eyebrow = 'Pure Botanical Powder';
        } elseif ($isHairCleansing) {
            $eyebrow = 'Botanical Hair Cleansing';
        } elseif ($isFacePack) {
            $eyebrow = 'Botanical Skin Care';
        } elseif ($isHairMask) {
            $eyebrow = 'Botanical Hair Care';
        } else {
            $eyebrow = 'Pure Botanical Powder';
        }

        // -------------------------------------------------------------
        // MODULE 1: Product Hero Banner (Mandatory Fallback)
        // -------------------------------------------------------------
        $modules[] = [
            'type' => 'hero_banner',
            'eyebrow' => $eyebrow,
            'title' => $this->name,
            'description' => $heroDesc,
            'image' => $primaryImgUrl,
            'image_alt' => $this->name,
        ];

        // -------------------------------------------------------------
        // MODULE 2: Four-Column Product Value Pillars
        // -------------------------------------------------------------
        $modules[] = [
            'type' => 'features_grid',
            'title' => 'Botanical Standards & Product Attributes',
            'subtitle' => 'Factual, transparent product attributes for mindful home care routines.',
            'items' => [
                [
                    'icon' => 'ri-leaf-line',
                    'title' => 'Traditional Botanicals',
                    'description' => 'Formulated with dried plant botanicals traditionally utilized in hair and skincare routines.',
                ],
                [
                    'icon' => 'ri-drop-line',
                    'title' => 'Fresh Powder Format',
                    'description' => 'Clean, dry powder format allowing fresh custom preparation with water or oils right before use.',
                ],
                [
                    'icon' => 'ri-scales-3-line',
                    'title' => 'Declared Net Weight',
                    'description' => 'Packaged in sealed ' . $weightStr . ' packaging to preserve botanical freshness and purity.',
                ],
                [
                    'icon' => 'ri-shield-check-line',
                    'title' => 'External Personal Care',
                    'description' => 'Formulated specifically for topical cosmetic application on hair, scalp, or skin.',
                ],
            ],
        ];

        // -------------------------------------------------------------
        // MODULE 3: Split Story Block
        // -------------------------------------------------------------
        $storyContent = strip_tags($this->benefits ?? ($this->highlights ?? ''));
        if (blank($storyContent) || $isIngestionText($storyContent)) {
            $storyContent = 'Carefully selected botanical powders brought together to support your traditional beauty rituals with pure, plant-based preparation.';
        }
        $storyContent = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $storyContent)), 450);

        $modules[] = [
            'type' => 'split_story',
            'title' => 'Time-Honored Botanical Care',
            'subtitle' => 'Traditional Botanical Heritage',
            'content' => $storyContent,
            'image' => $primaryImgUrl,
            'image_alt' => $this->name . ' Botanical Story',
            'reverse' => false,
            'bullets' => [
                'Dry powder format allows fresh preparation without synthetic liquid preservatives',
                'Traditional plant ingredients suitable for DIY masks, pastes, and rinses',
                'Intended strictly for topical personal care and external cosmetic use',
            ],
        ];

        // -------------------------------------------------------------
        // MODULE 4: Botanical Ingredients Spotlight
        // -------------------------------------------------------------
        $modules[] = [
            'type' => 'ingredients_spotlight',
            'title' => 'Featured Botanical Ingredients',
            'subtitle' => 'Selected plant actives present in this formulation.',
            'raw_content' => $this->ingredients,
            'items' => $matchedHerbs,
        ];

        // -------------------------------------------------------------
        // MODULE 5: Three-Step Usage Ritual (Category-Aware)
        // -------------------------------------------------------------
        if ($isHairCleansing && ! $isSinglePowder) {
            $ritualTitle = 'Three-Step Hair Cleansing Ritual';
            $ritualSubtitle = 'Mindful botanical cleansing for hair and scalp.';
            $steps = [
                [
                    'step' => '01',
                    'title' => 'Mix with Warm Water',
                    'desc' => 'Measure the required powder into a bowl and gradually stir in lukewarm water to create a smooth, fluid paste.',
                ],
                [
                    'step' => '02',
                    'title' => 'Massage into Wet Scalp',
                    'desc' => 'Apply gently onto wet scalp and hair lengths, massaging in gentle circular motions without harsh scrubbing.',
                ],
                [
                    'step' => '03',
                    'title' => 'Rinse Thoroughly',
                    'desc' => 'Rinse out completely with running water until the rinse water runs clear. Avoid contact with eyes.',
                ],
            ];
            $ritualTip = 'Botanical cleansing powders produce a gentle natural cleansing emulsion rather than chemical foam. Focus on scalp massage and thorough rinsing.';
        } elseif ($isFacePack && ! $isSinglePowder) {
            $ritualTitle = 'Three-Step Face Pack Ritual';
            $ritualSubtitle = 'Mindful application steps for traditional clay and botanical packs.';
            $steps = [
                [
                    'step' => '01',
                    'title' => 'Measure & Blend',
                    'desc' => 'Take the required powder in a clean bowl. Blend with clean water or rose water to achieve a smooth, lump-free paste.',
                ],
                [
                    'step' => '02',
                    'title' => 'Apply Evenly',
                    'desc' => 'Gently smooth an even layer over face and neck, keeping completely clear of the delicate eye and lip contours.',
                ],
                [
                    'step' => '03',
                    'title' => 'Rinse Before Dry',
                    'desc' => 'Rinse off gently with lukewarm water before the mask dries completely. Pat dry and follow with your regular moisturizer.',
                ],
            ];
            $ritualTip = 'Always perform a 24-hour patch test behind your ear before initial facial use. Do not allow clay or botanical packs to dry to cracking stage.';
        } elseif ($isHairMask && ! $isSinglePowder) {
            $ritualTitle = 'Three-Step Hair Mask Ritual';
            $ritualSubtitle = 'Traditional botanical conditioning for hair and scalp.';
            $steps = [
                [
                    'step' => '01',
                    'title' => 'Prepare Smooth Paste',
                    'desc' => 'Mix the desired amount of powder with warm water, curd, or aloe vera gel in a clean bowl until smooth.',
                ],
                [
                    'step' => '02',
                    'title' => 'Coat Roots to Ends',
                    'desc' => 'Part damp hair in sections and apply the paste evenly from scalp and roots through to the tips.',
                ],
                [
                    'step' => '03',
                    'title' => 'Rinse Thoroughly',
                    'desc' => 'Leave on for the duration indicated on product packaging, then rinse out thoroughly with plain water.',
                ],
            ];
            $ritualTip = 'Conduct a patch test prior to first use. Covering hair with a damp towel or shower cap helps keep the paste moist during application.';
        } elseif ($isCombo) {
            $ritualTitle = 'Three-Step Dual Powder Ritual';
            $ritualSubtitle = 'Mindful application steps for multi-powder beauty rituals.';
            $steps = [
                [
                    'step' => '01',
                    'title' => 'Review Individual Pouches',
                    'desc' => 'Check packaging directions for each individual pouch before mixing, ensuring ingredients suit your intended application.',
                ],
                [
                    'step' => '02',
                    'title' => 'Measure & Blend',
                    'desc' => 'Measure the powders as instructed by packaging and blend gradually with water to form a smooth paste.',
                ],
                [
                    'step' => '03',
                    'title' => 'Apply & Rinse',
                    'desc' => 'Apply to targeted hair or skin areas as directed on the packaging, then rinse thoroughly with running water.',
                ],
            ];
            $ritualTip = 'Always conduct a patch test prior to combining botanical powders for the first time. For external cosmetic use only.';
        } else {
            $ritualTitle = 'Three-Step Application Ritual';
            $ritualSubtitle = 'Simple, mindful preparation according to product label directions.';
            $steps = [
                [
                    'step' => '01',
                    'title' => 'Check Label Directions',
                    'desc' => 'Review package instructions for recommended quantity and suitable mixing liquid (water, hydrosol, or oil) for your chosen routine.',
                ],
                [
                    'step' => '02',
                    'title' => 'Mix Fresh Paste',
                    'desc' => 'Blend the dry powder gradually with clean water in a non-metallic bowl until smooth and lump-free.',
                ],
                [
                    'step' => '03',
                    'title' => 'Apply & Rinse',
                    'desc' => 'Apply smoothly to target hair, scalp, or skin areas as directed on packaging, then rinse thoroughly with water.',
                ],
            ];
            $ritualTip = 'Always conduct a patch test on a small area of skin 24 hours prior to full application. For external cosmetic personal care use only.';
        }

        $rawRitualContent = $this->how_to_use ?: $this->directions;
        if ($isIngestionText($rawRitualContent)) {
            $rawRitualContent = null;
        }

        $modules[] = [
            'type' => 'how_to_use_ritual',
            'title' => $ritualTitle,
            'subtitle' => $ritualSubtitle,
            'raw_content' => $rawRitualContent,
            'steps' => $steps,
            'tip' => $ritualTip,
        ];

        // -------------------------------------------------------------
        // MODULE 6: Trust & Quality Section (Factual Declarations)
        // -------------------------------------------------------------
        $modules[] = [
            'type' => 'clinical_stats',
            'title' => 'Botanical Standards & Declarations',
            'subtitle' => 'Factual product specifications for transparent consumer trust.',
            'stats' => [
                [
                    'number' => $weightStr,
                    'label' => 'Declared Net Weight',
                    'sub' => 'Net quantity as declared on packaging',
                ],
                [
                    'number' => 'Botanical',
                    'label' => 'Plant-Based Powder',
                    'sub' => 'Traditional personal care botanicals',
                ],
                [
                    'number' => 'Cosmetic',
                    'label' => 'External Use Only',
                    'sub' => 'Formulated for topical hair & skin care',
                ],
            ],
        ];

        return $modules;
    }

    /**
     * Absolute URL for the product video, or null when no video is configured.
     */
    public function videoSrc(): ?string
    {
        return video_url($this->video_url);
    }

    /**
     * 'youtube' | 'vimeo' | null (null means a direct media file).
     */
    public function videoProvider(): ?string
    {
        $raw = trim((string) $this->video_url);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,})#i', $raw, $m)) {
            return 'youtube';
        }

        if (preg_match('#(?:vimeo\.com/)(\d+)#i', $raw, $m)) {
            return 'vimeo';
        }

        return null;
    }

    /**
     * Embed id for a youtube/vimeo video_url, otherwise null.
     */
    public function videoEmbedId(): ?string
    {
        $raw = trim((string) $this->video_url);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,})#i', $raw, $m)) {
            return $m[1];
        }

        if (preg_match('#(?:vimeo\.com/)(\d+)#i', $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * True when the configured video is a direct playable file (uploaded
     * video or an .mp4/.webm/... link) rather than an embed provider.
     */
    public function hasVideoFile(): bool
    {
        $raw = trim((string) $this->video_url);

        return $raw !== '' && $this->videoProvider() === null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $product = $this->where($field, $value)->first();
        if ($product) {
            return $product;
        }

        if ($field === 'slug') {
            $productId = ProductSlugRedirect::query()->where('slug', $value)->value('product_id');
            if ($productId) {
                return $this->whereKey($productId)->first();
            }
        }

        return null;
    }

    public static function uniqueSlugFromName(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'product';
        }

        $base = substr($base, 0, 240);
        $slug = $base;
        $suffix = 2;

        while (static::slugIsTaken($slug, $ignoreId)) {
            $slug = substr($base, 0, 230).'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public static function rewriteSlugFromName(self $product): bool
    {
        $old = (string) $product->slug;
        $new = static::uniqueSlugFromName((string) $product->name, $product->id);

        if ($old === $new) {
            return false;
        }

        $product->slug = $new;
        $product->saveQuietly();

        if ($old !== '') {
            ProductSlugRedirect::query()->updateOrCreate(
                ['slug' => $old],
                ['product_id' => $product->id]
            );
        }

        return true;
    }

    public static function rewriteAllSlugsFromNames(): int
    {
        $updated = 0;

        foreach ([1, 2] as $_) {
            static::query()->orderBy('id')->each(function (self $product) use (&$updated): void {
                if (static::rewriteSlugFromName($product)) {
                    $updated++;
                }
            });
        }

        return $updated;
    }

    protected static function slugIsTaken(string $slug, ?int $ignoreId = null): bool
    {
        $productTaken = static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($productTaken) {
            return true;
        }

        return ProductSlugRedirect::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('product_id', '!=', $ignoreId))
            ->exists();
    }

    public function getNetQuantityAttribute(): ?string
    {
        return isset($this->attributes['weight']) && $this->attributes['weight'] !== null
            ? (string) $this->attributes['weight']
            : null;
    }
}
