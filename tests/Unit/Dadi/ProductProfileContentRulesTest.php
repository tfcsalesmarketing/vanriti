<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Exceptions\InvalidProductProfileException;
use App\Dadi\Validation\ProductProfileContentRules;
use PHPUnit\Framework\TestCase;

class ProductProfileContentRulesTest extends TestCase
{
    public function test_rejects_unexpected_payload_keys(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertKnownKeys(['sections' => ['hair'], 'price' => 499]);
    }

    public function test_accepts_only_the_fixed_key_set(): void
    {
        $normalized = ProductProfileContentRules::assertKnownKeys([
            'sections' => ['skin'],
            'concerns' => [],
            'positioning' => null,
            'approved_benefits' => [],
            'approved_usage_context' => [],
            'approved_precautions' => [],
            'suitability_notes' => [],
        ]);

        self::assertSame('skin', $normalized['sections'][0]);
    }

    public function test_rejects_an_over_capacity_list(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertTextList(array_fill(0, 11, 'x'), 10, 300, 'approved_precautions');
    }

    public function test_rejects_an_oversized_entry(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertTextList(['abcdef'], 10, 5, 'approved_benefits');
    }

    public function test_rejects_unknown_section_and_concern_codes(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertSections(['medicinal']);
    }

    public function test_rejects_unknown_concern_vocabulary(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertConcerns(['free_text_concern']);
    }

    public function test_normalizes_spaced_selling_price_variant(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertNoForbiddenContent('selling price is decided by the store');
    }

    public function test_forbidden_terms_are_word_bounded(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        ProductProfileContentRules::assertNoForbiddenContent('inventory count is live');
    }
}
