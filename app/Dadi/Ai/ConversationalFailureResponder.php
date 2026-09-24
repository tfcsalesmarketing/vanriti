<?php

namespace App\Dadi\Ai;

use App\Dadi\Enums\ConversationIntent;
use App\Dadi\ValueObjects\AiTurnResult;

/**
 * Graceful, on-brand fallback for the rare moment the AI layer is unavailable.
 *
 * This is NOT a questionnaire fallback and NOT a canned conversation: it is a
 * short, warm apology Dadi-style, chosen deterministically so the same customer
 * message keeps a stable fallback. State is untouched (no proposed state), so
 * the authoritative conversation understanding always stays intact.
 */
final class ConversationalFailureResponder
{
    public const FALLBACKS = [
        'Arre beta, ek pal ruk jao — meri baat thodi atak gayi. Phir se batao, main sun rahi hoon.',
        'Beta, yeh tech ka jhamela hai, sundar to hain na mera intazaar. Thoda aur batao, main yahin hoon.',
        'Haan beta, sun rahi hoon main. Bas ek chhoti si taklif ho gayi meri taraf se — ek baar aur batao, please.',
    ];

    public function respond(string $customerMessage): AiTurnResult
    {
        $index = abs(crc32(trim($customerMessage))) % count(self::FALLBACKS);

        return new AiTurnResult(
            reply: self::FALLBACKS[$index],
            intent: ConversationIntent::Unclear,
        );
    }
}
