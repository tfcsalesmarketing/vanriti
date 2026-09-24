<?php

namespace App\Dadi\Contracts;

use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\SafetyAssessment;

/**
 * Laravel-owned safety gate between the conversation and any commercial
 * outcome. Called with the authoritative state, the raw customer message, the
 * untrusted AI safety flag and the trusted DadiContext, and returns the
 * binding verdict for the turn.
 *
 * The AI may flag conversational safety signals (AiTurnResult::requiresSafetyReview),
 * but that flag is untrusted input here: it can never install a BLOCK and it can
 * never clear one. This contract is the final authority.
 */
interface SafetyAdjudicator
{
    public function adjudicate(
        ConversationState $state,
        string $message,
        bool $aiSafetyFlag = false,
        ?DadiContext $context = null,
    ): SafetyAssessment;
}
