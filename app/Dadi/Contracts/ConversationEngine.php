<?php

namespace App\Dadi\Contracts;

use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;

/**
 * The single AI seam of the Dadi domain.
 *
 * Implementations pair a customer message with the Laravel-owned conversation
 * state and produce the next conversational turn. AI owns the words; Laravel
 * owns the world. Implementations MUST NOT emit product/SKU/eligibility
 * decisions — AiTurnResult has no such field by design.
 */
interface ConversationEngine
{
    public function respond(ConversationState $state, string $message): AiTurnResult;
}
