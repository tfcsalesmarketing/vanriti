<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Exceptions\InvalidConversationStateException;
use App\Dadi\ValueObjects\SafetyAssessment;
use PHPUnit\Framework\TestCase;

class SafetyAssessmentTest extends TestCase
{
    public function test_clear_never_blocks_recommendations(): void
    {
        self::assertFalse(SafetyAssessment::clear()->blocksRecommendation());
    }

    public function test_advisory_does_not_block_recommendations(): void
    {
        $assessment = SafetyAssessment::advisory(['pregnancy-sensitive']);

        self::assertSame(SafetyVerdict::Advisory, $assessment->verdict);
        self::assertFalse($assessment->blocksRecommendation());
        self::assertSame(['pregnancy-sensitive'], $assessment->reasons);
    }

    public function test_block_blocks_recommendations_and_exposes_reasons(): void
    {
        $assessment = SafetyAssessment::block(['emergency-language']);

        self::assertSame(SafetyVerdict::Block, $assessment->verdict);
        self::assertTrue($assessment->blocksRecommendation());
        self::assertSame(['emergency-language'], $assessment->reasons);
    }

    public function test_round_trips_through_array(): void
    {
        $assessment = SafetyAssessment::fromArray([
            'verdict' => 'block',
            'reasons' => ['stopping-medication'],
        ]);

        self::assertSame([
            'verdict' => 'block',
            'reasons' => ['stopping-medication'],
        ], $assessment->toArray());
    }

    public function test_unknown_verdict_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        SafetyAssessment::fromArray(['verdict' => 'maybe']);
    }
}
