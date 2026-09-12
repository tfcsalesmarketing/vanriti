<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidProductProfileException;
use App\Dadi\Validation\ProductProfileContentRules;

/**
 * The immutable, controlled representation of one product's Dadi-approved
 * intelligence. This is what a future RecommendationEngine (and only Laravel)
 * may hand to Dadi for natural explanation.
 *
 * It deliberately mirrors the existing ProductModel only through a
 * product_reference and a display name. There is no sku, price, MRP, stock,
 * inventory or status here: commerce truth stays in the catalogue and is
 * resolved dynamically later. Every string is validated against the closed
 * section/concern vocabularies and against the forbidden commerce-word scan,
 * so Dadi-authored content can never smuggle authoritative commerce data.
 *
 * Constructing a ProductProfile is only legal for a human-approved profile
 * over an active product — enforced by DadiProductProfileStore::buildProfile.
 */
final readonly class ProductProfile
{
    public const DEFAULT_MAX_POSITIONING = 1000;

    public const DEFAULT_MAX_BENEFITS = 15;

    public const DEFAULT_BENEFIT_LENGTH = 300;

    public const DEFAULT_MAX_USAGE = 10;

    public const DEFAULT_USAGE_LENGTH = 300;

    public const DEFAULT_MAX_PRECAUTIONS = 10;

    public const DEFAULT_PRECAUTION_LENGTH = 300;

    public const DEFAULT_MAX_SUITABILITY = 10;

    public const DEFAULT_SUITABILITY_LENGTH = 500;

    public const DEFAULT_MAX_CONCERNS = 12;

    /**
     * @var array<int,string>
     */
    public readonly array $sections;

    /**
     * @var array<int,string>
     */
    public readonly array $concerns;

    public readonly ?string $positioning;

    /**
     * @var array<int,string>
     */
    public readonly array $approvedBenefits;

    /**
     * @var array<int,string>
     */
    public readonly array $approvedUsageContext;

    /**
     * @var array<int,string>
     */
    public readonly array $approvedPrecautions;

    /**
     * @var array<int,string>
     */
    public readonly array $suitabilityNotes;

    /**
     * @param  array<mixed>  $sections
     * @param  array<mixed>  $concerns
     * @param  array<mixed>  $approvedBenefits
     * @param  array<mixed>  $approvedUsageContext
     * @param  array<mixed>  $approvedPrecautions
     * @param  array<mixed>  $suitabilityNotes
     */
    public function __construct(
        public int $productReference,
        public string $name,
        array $sections = [],
        array $concerns = [],
        mixed $positioning = null,
        array $approvedBenefits = [],
        array $approvedUsageContext = [],
        array $approvedPrecautions = [],
        array $suitabilityNotes = [],
    ) {
        if ($this->productReference < 1) {
            throw new InvalidProductProfileException('Product references must be positive integers.');
        }

        if (trim($this->name) === '') {
            throw new InvalidProductProfileException('A product name is required.');
        }

        $this->sections = ProductProfileContentRules::assertSections($sections);
        $this->concerns = ProductProfileContentRules::assertConcerns($concerns, self::DEFAULT_MAX_CONCERNS);
        $this->positioning = ProductProfileContentRules::assertText($positioning, self::DEFAULT_MAX_POSITIONING);
        $this->approvedBenefits = ProductProfileContentRules::assertTextList(
            $approvedBenefits,
            self::DEFAULT_MAX_BENEFITS,
            self::DEFAULT_BENEFIT_LENGTH,
            'approved_benefits',
        );
        $this->approvedUsageContext = ProductProfileContentRules::assertTextList(
            $approvedUsageContext,
            self::DEFAULT_MAX_USAGE,
            self::DEFAULT_USAGE_LENGTH,
            'approved_usage_context',
        );
        $this->approvedPrecautions = ProductProfileContentRules::assertTextList(
            $approvedPrecautions,
            self::DEFAULT_MAX_PRECAUTIONS,
            self::DEFAULT_PRECAUTION_LENGTH,
            'approved_precautions',
        );
        $this->suitabilityNotes = ProductProfileContentRules::assertTextList(
            $suitabilityNotes,
            self::DEFAULT_MAX_SUITABILITY,
            self::DEFAULT_SUITABILITY_LENGTH,
            'suitability_notes',
        );
    }

    /**
     * The Laravel-built payload for Dadi use. product_reference is an internal
     * Laravel identifier; commerce resolution happens later in the
     * commerce/recommendation stages — never here.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'product_reference' => $this->productReference,
            'name' => $this->name,
            'approved_intelligence' => [
                'sections' => $this->sections,
                'concerns' => $this->concerns,
                'positioning' => $this->positioning,
                'approved_benefits' => $this->approvedBenefits,
                'approved_usage_context' => $this->approvedUsageContext,
                'approved_precautions' => $this->approvedPrecautions,
                'suitability_notes' => $this->suitabilityNotes,
            ],
        ];
    }

    public function hasSection(Section $section): bool
    {
        return in_array($section->value, $this->sections, true);
    }

    public function hasConcern(Concern $concern): bool
    {
        return in_array($concern->value, $this->concerns, true);
    }
}
