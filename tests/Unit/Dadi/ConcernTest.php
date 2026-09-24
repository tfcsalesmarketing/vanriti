<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\Section;
use PHPUnit\Framework\TestCase;

class ConcernTest extends TestCase
{
    public function test_vocabulary_covers_hair_skin_and_wellness(): void
    {
        $codes = array_map(static fn (Concern $concern): string => $concern->value, Concern::cases());

        self::assertContains('hair_dryness', $codes);
        self::assertContains('hair_frizz', $codes);
        self::assertContains('skin_dryness', $codes);
        self::assertContains('stress_support', $codes);
    }

    public function test_concerns_map_to_their_primary_section(): void
    {
        self::assertSame(Section::Hair, Concern::HairDryness->section());
        self::assertSame(Section::Hair, Concern::HairFrizz->section());
        self::assertSame(Section::Skin, Concern::SkinRoughness->section());
        self::assertSame(Section::Wellness, Concern::SleepSupport->section());
    }

    public function test_unknown_concern_is_not_in_the_vocabulary(): void
    {
        self::assertNull(Concern::tryFrom('cures_dandruff'));
        self::assertNull(Concern::tryFrom('random_free_text'));
    }

    public function test_labels_are_human_readable(): void
    {
        self::assertSame('Dry skin', Concern::SkinDryness->label());
        self::assertSame('Frizzy hair', Concern::HairFrizz->label());
    }
}
