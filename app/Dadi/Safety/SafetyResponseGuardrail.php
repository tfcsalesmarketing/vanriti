<?php

namespace App\Dadi\Safety;

use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\SafetyAssessment;

/**
 * Deterministic safety gate behind AI generation.
 *
 * AI owns the words; Laravel owns the world. This guardrail inspects the
 * completed AI turn against the authoritative SafetyAssessment and either
 * passes the AI reply through (CLEAR, or ADVISORY that contains no medical
 * guidance or product-safety claim) or replaces it with a fixed, Dadi-styled
 * safe response. No second AI call is ever made.
 *
 *   - Block:    the AI reply is always replaced and any proposed state is
 *               dropped (no commercial outcome from a blocked turn).
 *   - Advisory: the AI reply passes only if deterministic pattern checks find
 *               no diagnosis, dosage, prescription, stopping/replacing
 *               medication or product-safety claim; otherwise a cautious
 *               Dadi reply is used instead.
 *
 * Because the responses are fixed constants, the same situation always yields
 * the same answer — determinism is deliberate and testable.
 */
final class SafetyResponseGuardrail
{
    /**
     * Fixed, on-brand response for a BLOCK verdict. Urges urgent medical help;
     * never references a product, a price or a routine.
     */
    public const BLOCK_REPLY = 'Beta, yeh baat mere haath ki nahi hai. Aap jo bata rahe hain woh Dadi ke daayre se bahar ki baat lagti hai — yeh doctor ya emergency team ke haath ki baat hai. Please turant apne doctor se baat karein, aur agar haalat critical lag rahi hai toh turant emergency number par call karein ya as-paas ke hospital jaayein. Dadi yahan na koi salah de sakti hai, na koi product bataya ja sakta hai. Bas yeh kaam karo — turant madad lo. Apna dhyaan rakho, beta.';

    /**
     * Fixed, cautious response for an ADVISORY verdict whose AI reply crossed
     * into medical guidance. Reminds the customer that Dadi does not diagnose
     * or prescribe and that prescribed routines should not change without a
     * doctor. No product, no price, no claim.
     */
    public const ADVISORY_REPLY = 'Beta, main aapki baat samajh sakti hoon, par yeh meri salah ke daayre se bahar hai. Main na kisi bimari ki diagnosis kar sakti hoon, na dawai ya dose ki salah de sakti hoon. Jo dawai ya ilaaj aap doctor ke saath lekar chal rahe hain, use apni taraf se mat badalna aur mat band karna. Agar zaroorat ho toh please kisi doctor se zaroor milen. Baal, skin aur wellness ki baatein main yahan karti hoon — lekin ilaaj ki baat hamesha doctor aur aapke beech hai.';

    private const FORBIDDEN_REPLY_CONTENT = [
        'sku',
        'recommended_sku',
        'price',
        'stock',
        'catalogue',
    ];

    /**
     * Deterministic pattern checks applied to the AI reply for ADVISORY
     * turns: dose/dosage guidance, prescriptions, stopping or replacing
     * prescribed medication, diagnoses, and product-safety claims for
     * allergy/pregnancy/children.
     *
     * @var array<int,string>
     */
    private const UNSAFE_MEDICAL_CLAIM_PATTERNS = [
        '/\b(?:dosage\w*|dose\w*)\b/i',
        '/\b\d+\s*(?:mg|mcg|\bml\b|gram|gm)\b/i',
        '/\b(?:teaspoon|tablespoon|spoon\w*|chamach|chammach)\b/i',
        '/\bprescrib\w*\b/i',
        '/\b(?:take|use|apply)\b.{0,15}\b(?:medicine|medication|dawai|dava|tablet|goli|capsule|syrup)\b/i',
        '/\b(?:medicine|medication|dawai|dava|tablet|goli|capsule)\b.{0,15}\b(?:lo\b|khao\b|kha\b|khati|khayiye|use\b|apply\b)\b/i',
        '/\bstop\b.{0,20}\b(?:medicine|medication|dawai|dava|tablet|capsule)\b/i',
        '/\b(?:medicine|medication|dawai|dava|tablet|capsule)\b.{0,15}\b(?:stop|band)\b/i',
        '/\bband\b.{0,10}\b(?:kar|karo|kijiye|karein)\b.{0,15}\b(?:dawai|dava|medicine|tablet|goli)\b/i',
        '/\b(?:replace|change|badal\w*)\b.{0,20}\b(?:medicine|medication|dawai|dava|tablet)\b/i',
        '/\b(?:medicine|medication|dawai|dava|tablet)\b.{0,20}\b(?:replace|change|badal\w*)\b/i',
        '/\bdiagnos\w*\b/i',
        '/\byou\s+have\b.{0,25}\b(?:disease|condition|infection|eczema|psoriasis)\b/i',
        '/\b(?:safe|suitable|fine|okay|thik)\b.{0,20}\b(?:for|during|while)\b.{0,15}\b(?:allerg\w*|pregnan\w*|breastfeed\w*|baby|child)\b/i',
        '/\b(?:allerg\w*|pregnan\w*|breastfeed\w*)\b.{0,25}\b(?:safe|suitable|fine|okay|thik|no problem)\b/i',
        '/\b(?:recommend\w*|suggest\w*)\b.{0,25}\b(?:medicine|medication|dawai|dava|tablet|cream\w*|ointment\w*|dose|dosage)\b/i',
        '/\b(?:you|tum|aap)\b.{0,10}\b(?:must|should|chahiye|zaroor)\b.{0,15}\b(?:take|use|apply|lo|khao)\b/i',
        '/\bcure\w*\b.{0,20}\b(?:it|this|yeh|disease|condition)\b/i',
    ];

    public function apply(AiTurnResult $turn, SafetyAssessment $assessment): AiTurnResult
    {
        $reply = $turn->reply;
        $proposedState = $turn->proposedState;

        if ($assessment->verdict === SafetyVerdict::Block) {
            $reply = self::BLOCK_REPLY;
            $proposedState = null;
        } elseif (
            $assessment->verdict === SafetyVerdict::Advisory
            && $this->containsUnsafeMedicalClaim($reply)
        ) {
            $reply = self::ADVISORY_REPLY;
        }

        return new AiTurnResult(
            reply: $reply,
            intent: $turn->intent,
            proposedState: $proposedState,
            requiresSafetyReview: $turn->requiresSafetyReview,
            safetyAssessment: $assessment,
        );
    }

    private function containsUnsafeMedicalClaim(string $reply): bool
    {
        foreach (self::UNSAFE_MEDICAL_CLAIM_PATTERNS as $pattern) {
            if (preg_match($pattern, $reply) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guardrail output must never leak an implementation detail or a product
     * truth: fixed responses are tested for this, not the AI reply.
     */
    public function containsForbiddenContent(string $reply): bool
    {
        foreach (self::FORBIDDEN_REPLY_CONTENT as $term) {
            if (stripos($reply, $term) !== false) {
                return true;
            }
        }

        return false;
    }
}
