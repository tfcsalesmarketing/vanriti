<?php

namespace App\Dadi\ValueObjects;

use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Exceptions\InvalidAiTurnException;

/**
 * The output of one AI turn.
 *
 * AI owns the words; Laravel owns the world. This value object deliberately
 * exposes only conversational fields — there is no product, SKU or selection
 * field, and none may be added. Product decisions belong exclusively to the
 * Laravel Recommendation Engine.
 *
 * proposed_state is the AI's UNVALIDATED proposal. A Laravel layer must
 * validate/normalise it before it is merged into the authoritative state.
 */
final readonly class AiTurnResult
{
    public function __construct(
        public string $reply,
        public ConversationIntent $intent = ConversationIntent::Unclear,
        public ?ConversationState $proposedState = null,
        public bool $requiresSafetyReview = false,
        public ?SafetyAssessment $safetyAssessment = null,
    ) {
        if (trim($reply) === '') {
            throw new InvalidAiTurnException('AI reply must not be empty.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'reply' => $this->reply,
            'intent' => $this->intent->value,
            'proposed_state' => $this->proposedState?->toArray(),
            'requires_safety_review' => $this->requiresSafetyReview,
            'safety_assessment' => $this->safetyAssessment?->toArray(),
        ];
    }
}
