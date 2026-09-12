<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\RecommendationStatus;
use App\Dadi\ValueObjects\RecommendationCandidate;
use App\Dadi\ValueObjects\RecommendationResult;
use PHPUnit\Framework\TestCase;

class RecommendationResultTest extends TestCase
{
    public function test_result_serializes_status_candidates_and_reasons(): void
    {
        $result = new RecommendationResult(
            RecommendationStatus::Available,
            candidates: [
                new RecommendationCandidate(
                    productReference: 41,
                    score: 45,
                    matchedConcerns: ['hair_dryness'],
                    matchedPreferences: ['lightweight'],
                ),
            ],
            reasons: ['Two concerns matched.'],
        );

        self::assertTrue($result->hasCandidates());

        self::assertSame([
            'status' => 'available',
            'candidates' => [
                [
                    'product_reference' => 41,
                    'score' => 45,
                    'matched_concerns' => ['hair_dryness'],
                    'matched_preferences' => ['lightweight'],
                    'exclusion_reasons' => [],
                ],
            ],
            'reasons' => ['Two concerns matched.'],
        ], $result->toArray());
    }

    public function test_non_available_statuses_never_carry_candidates(): void
    {
        foreach ([RecommendationStatus::NoMatch, RecommendationStatus::Unavailable, RecommendationStatus::SafetyBlocked, RecommendationStatus::AdvisoryRestricted] as $status) {
            $result = new RecommendationResult($status, reasons: ['Because.']);

            self::assertFalse($result->hasCandidates());
            self::assertSame([], $result->toArray()['candidates']);
        }
    }

    public function test_ranked_result_is_deterministic_across_serializations(): void
    {
        $first = new RecommendationResult(
            RecommendationStatus::Available,
            candidates: [
                new RecommendationCandidate(productReference: 7, score: 40),
                new RecommendationCandidate(productReference: 9, score: 65),
            ],
        );

        $second = new RecommendationResult(
            RecommendationStatus::Available,
            candidates: [
                new RecommendationCandidate(productReference: 7, score: 40),
                new RecommendationCandidate(productReference: 9, score: 65),
            ],
        );

        self::assertSame($first->toArray(), $second->toArray());
    }

    public function test_candidates_carry_no_commerce_fields(): void
    {
        $result = new RecommendationResult(
            RecommendationStatus::Available,
            candidates: [
                new RecommendationCandidate(productReference: 41, score: 45),
            ],
        );

        $json = json_encode($result->toArray(), JSON_THROW_ON_ERROR);

        foreach (['sku', 'price', 'selling_price', 'mrp', 'stock', 'quantity', 'variant'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
    }
}
