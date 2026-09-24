<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Exceptions\InvalidConversationStateException;

/**
 * The Laravel-owned safety verdict for a conversational turn. AI may flag
 * conversational safety signals (AiTurnResult::requiresSafetyReview), but only
 * this value object can block or restrict commercial outcomes.
 */
final readonly class SafetyAssessment
{
    /**
     * @param  array<int,string>  $reasons
     */
    public function __construct(
        public SafetyVerdict $verdict,
        public array $reasons = [],
    ) {}

    public static function clear(): self
    {
        return new self(SafetyVerdict::Clear);
    }

    /**
     * @param  array<int,string>  $reasons
     */
    public static function advisory(array $reasons = []): self
    {
        return new self(SafetyVerdict::Advisory, $reasons);
    }

    /**
     * @param  array<int,string>  $reasons
     */
    public static function block(array $reasons = []): self
    {
        return new self(SafetyVerdict::Block, $reasons);
    }

    public function blocksRecommendation(): bool
    {
        return $this->verdict->blocksRecommendation();
    }

    /**
     * The smallest domain-level capability the recommendation engine needs:
     * recommendations are only permitted when the assessment is a clean
     * baseline. An advisory assessment restricts commercial outcomes until the
     * stage-5 policy explicitly lifts the restriction; a block prohibits them.
     */
    public function permitsRecommendation(): bool
    {
        return $this->verdict->permitsRecommendation();
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $verdict = SafetyVerdict::tryFrom((string) ($data['verdict'] ?? ''));

        if ($verdict === null) {
            throw new InvalidConversationStateException("Unknown safety verdict '".($data['verdict'] ?? 'null')."'.");
        }

        return new self($verdict, $data['reasons'] ?? []);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'verdict' => $this->verdict->value,
            'reasons' => $this->reasons,
        ];
    }
}
