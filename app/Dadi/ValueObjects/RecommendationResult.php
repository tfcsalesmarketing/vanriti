<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Enums\RecommendationStatus;

/**
 * The deterministic, Laravel-owned recommendation outcome.
 *
 * This is a domain result, never AI output. When status::hasCandidates() is
 * true the candidates are already ranked and bounded; every other status
 * carries an empty candidate set so no unapproved, inactive or unsafe product
 * can ever leak into an application layer.
 */
final readonly class RecommendationResult
{
    /**
     * @param  array<int,RecommendationCandidate>  $candidates
     * @param  array<int,string>  $reasons
     */
    public function __construct(
        public RecommendationStatus $status,
        public array $candidates = [],
        public array $reasons = [],
    ) {}

    public function hasCandidates(): bool
    {
        return $this->status->hasCandidates();
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'candidates' => array_map(
                static fn (RecommendationCandidate $candidate): array => $candidate->toArray(),
                $this->candidates,
            ),
            'reasons' => $this->reasons,
        ];
    }
}
