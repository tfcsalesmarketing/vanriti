<?php

namespace App\Dadi\ValueObjects;

/**
 * One eligible, scored recommendation candidate.
 *
 * Immutable and commerce-free: it carries an internal product_reference (the
 * existing products.id — no SKU copies, no price, no stock) plus the
 * deterministic, explainable match breakdown. Commerce values are resolved
 * live from the existing catalogue by the application layer when needed.
 *
 * @property array<int,string> $matchedConcerns
 * @property array<int,string> $matchedPreferences
 * @property array<int,string> $exclusionReasons
 */
final readonly class RecommendationCandidate
{
    /**
     * @param  array<int,string>  $matchedConcerns
     * @param  array<int,string>  $matchedPreferences
     * @param  array<int,string>  $exclusionReasons
     */
    public function __construct(
        public int $productReference,
        public int $score,
        public array $matchedConcerns = [],
        public array $matchedPreferences = [],
        public array $exclusionReasons = [],
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'product_reference' => $this->productReference,
            'score' => $this->score,
            'matched_concerns' => $this->matchedConcerns,
            'matched_preferences' => $this->matchedPreferences,
            'exclusion_reasons' => $this->exclusionReasons,
        ];
    }
}
