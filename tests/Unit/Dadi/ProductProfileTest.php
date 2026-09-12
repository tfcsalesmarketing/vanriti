<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidProductProfileException;
use App\Dadi\ValueObjects\ProductProfile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProductProfileTest extends TestCase
{
    private function base(
        array $sections = ['hair'],
        array $concerns = ['hair_dryness'],
        ?string $positioning = 'Halka daily-care option.',
    ): ProductProfile {
        return new ProductProfile(
            productReference: 7,
            name: 'Vanriti Hair Repair Oil',
            sections: $sections,
            concerns: $concerns,
            positioning: $positioning,
            approvedBenefits: ['Gently nourishes the lengths'],
            approvedUsageContext: ['Apply to damp lengths'],
            approvedPrecautions: ['Avoid contact with eyes'],
            suitabilityNotes: ['Consider for dry hair'],
        );
    }

    public function test_holds_an_immutable_domain_representation(): void
    {
        $profile = $this->base();

        self::assertTrue((new ReflectionClass($profile))->isReadOnly());
        self::assertSame(7, $profile->productReference);
        self::assertSame('Vanriti Hair Repair Oil', $profile->name);
    }

    public function test_serialization_exposes_only_controlled_fields(): void
    {
        $array = $this->base()->toArray();

        self::assertSame(['product_reference', 'name', 'approved_intelligence'], array_keys($array));

        self::assertSame(
            [
                'sections',
                'concerns',
                'positioning',
                'approved_benefits',
                'approved_usage_context',
                'approved_precautions',
                'suitability_notes',
            ],
            array_keys($array['approved_intelligence']),
        );
    }

    public function test_never_contains_a_commerce_field(): void
    {
        $array = $this->base()->toArray();

        $forbidden = ['sku', 'price', 'stock', 'selling_price', 'mrp', 'inventory', 'discount', 'coupon', 'cart', 'checkout', 'order', 'product_id'];

        self::assertEmpty(array_intersect(array_keys($array), $forbidden));
        self::assertEmpty(array_intersect(array_keys($array['approved_intelligence']), $forbidden));
    }

    public function test_rejects_positive_product_reference_requirement(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(productReference: 0, name: 'X');
    }

    public function test_rejects_an_empty_product_name(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(productReference: 1, name: '  ');
    }

    public function test_rejects_an_unknown_section(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(productReference: 1, name: 'X', sections: ['pharmacy']);
    }

    public function test_rejects_an_unknown_concern(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(productReference: 1, name: 'X', concerns: ['cures_eczema']);
    }

    public function test_rejects_arbitrary_unbounded_text_lists(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(
            productReference: 1,
            name: 'X',
            approvedBenefits: array_fill(0, 16, 'benefit'),
        );
    }

    public function test_whole_profiles_report_section_and_concern_membership(): void
    {
        $profile = $this->base();

        self::assertTrue($profile->hasSection(Section::Hair));
        self::assertFalse($profile->hasSection(Section::Skin));
        self::assertTrue($profile->hasConcern(Concern::HairDryness));
        self::assertFalse($profile->hasConcern(Concern::Acne));
    }

    /**
     * @dataProvider commerceTermsProvider
     */
    public function test_forbidden_commerce_terms_are_rejected_in_positioning(string $term): void
    {
        $this->expectException(InvalidProductProfileException::class);

        new ProductProfile(
            productReference: 1,
            name: 'X',
            positioning: 'This option is great, and the '.$term.' can help you decide.',
        );
    }

    /**
     * @return array<int,array{0:string}>
     */
    public static function commerceTermsProvider(): array
    {
        return array_map(
            static fn (string $term): array => [$term],
            ['sku', 'price', 'selling_price', 'mrp', 'stock', 'inventory', 'discount', 'coupon', 'cart', 'checkout', 'order'],
        );
    }
}
