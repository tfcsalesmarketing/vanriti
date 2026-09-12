<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\RecommendationStatus;
use PHPUnit\Framework\TestCase;

class RecommendationStatusTest extends TestCase
{
    public function test_vocabulary_is_small_and_maps_to_real_outcomes(): void
    {
        self::assertSame(['available', 'no_match', 'unavailable', 'safety_blocked', 'advisory_restricted'], [
            RecommendationStatus::Available->value,
            RecommendationStatus::NoMatch->value,
            RecommendationStatus::Unavailable->value,
            RecommendationStatus::SafetyBlocked->value,
            RecommendationStatus::AdvisoryRestricted->value,
        ]);
    }

    public function test_only_available_carries_candidates(): void
    {
        foreach (RecommendationStatus::cases() as $status) {
            self::assertSame(
                $status === RecommendationStatus::Available,
                $status->hasCandidates(),
                $status->value.' candidate expectation.',
            );
        }
    }

    public function test_labels_never_reveal_internal_math(): void
    {
        foreach (RecommendationStatus::cases() as $status) {
            self::assertNotEmpty($status->label());
            self::assertStringNotContainsString('score', $status->label());
            self::assertStringNotContainsString('weight', $status->label());
        }
    }
}
