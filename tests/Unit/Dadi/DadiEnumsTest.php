<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Enums\Section;
use PHPUnit\Framework\TestCase;

class DadiEnumsTest extends TestCase
{
    public function test_sections_are_backed_and_labeled(): void
    {
        self::assertSame('hair', Section::Hair->value);
        self::assertSame('Hair', Section::Hair->label());

        self::assertSame('skin', Section::Skin->value);
        self::assertSame('Skin', Section::Skin->label());

        self::assertSame('wellness', Section::Wellness->value);
        self::assertSame('Wellness', Section::Wellness->label());
    }

    public function test_verdict_can_only_block_when_blocked(): void
    {
        self::assertFalse(SafetyVerdict::Clear->blocksRecommendation());
        self::assertFalse(SafetyVerdict::Advisory->blocksRecommendation());
        self::assertTrue(SafetyVerdict::Block->blocksRecommendation());
    }

    public function test_intent_is_a_closed_backed_telemetry_vocabulary(): void
    {
        $values = array_column(ConversationIntent::cases(), 'value');

        self::assertSame($values, array_unique($values));

        foreach (ConversationIntent::cases() as $intent) {
            self::assertSame($intent->value, (string) $intent->value);
        }

        self::assertContains('topic_switch', $values);
        self::assertContains('unclear', $values);
        self::assertCount(12, $values);
    }
}
