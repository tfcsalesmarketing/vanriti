<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\SafetyAssessment;
use PHPUnit\Framework\TestCase;

class SafetyResponseGuardrailTest extends TestCase
{
    private function guard(): SafetyResponseGuardrail
    {
        return new SafetyResponseGuardrail;
    }

    public function test_clear_passes_the_ai_reply_through_unchanged(): void
    {
        $turn = new AiTurnResult(reply: 'Arre beta, baal ki baat karte hain.', intent: ConversationIntent::Concern);

        $result = $this->guard()->apply($turn, SafetyAssessment::clear());

        self::assertSame('Arre beta, baal ki baat karte hain.', $result->reply);
        self::assertSame(ConversationIntent::Concern, $result->intent);
        self::assertSame(SafetyVerdict::Clear, $result->safetyAssessment->verdict);
        self::assertNull($result->proposedState);
    }

    public function test_clear_preserves_the_proposed_state(): void
    {
        $proposed = ConversationState::fromArray(['section' => 'hair', 'concerns' => ['hair_dryness']]);
        $turn = new AiTurnResult(reply: 'Samajh gayi beta.', proposedState: $proposed);

        $result = $this->guard()->apply($turn, SafetyAssessment::clear());

        self::assertNotNull($result->proposedState);
        self::assertSame(['hair_dryness'], $result->proposedState->concerns);
    }

    public function test_block_always_replaces_the_reply_with_the_fixed_safety_response(): void
    {
        $proposed = ConversationState::fromArray(['section' => 'skin']);
        $turn = new AiTurnResult(
            reply: 'Yeh lo beta, yeh cream laga lo.',
            proposedState: $proposed,
        );

        $result = $this->guard()->apply($turn, SafetyAssessment::block(['emergency_chest_pain']));

        self::assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $result->reply);
        self::assertNull($result->proposedState);
        self::assertSame(SafetyVerdict::Block, $result->safetyAssessment->verdict);
    }

    public function test_block_replaces_even_a_benign_ai_reply(): void
    {
        $turn = new AiTurnResult(reply: 'Hmm, samajh gayi beta.');

        $result = $this->guard()->apply($turn, SafetyAssessment::block(['emergency_breathing']));

        self::assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $result->reply);
    }

    public function test_advisory_with_medical_claims_replaces_the_ai_reply(): void
    {
        $turn = new AiTurnResult(
            reply: 'Yeh tablet use karein — yeh pregnancy mein bhi safe hai.',
            proposedState: ConversationState::fromArray(['attributes' => ['consumer' => 'pregnant']]),
        );

        $result = $this->guard()->apply($turn, SafetyAssessment::advisory(['pregnancy']));

        self::assertSame(SafetyResponseGuardrail::ADVISORY_REPLY, $result->reply);
        self::assertSame(SafetyVerdict::Advisory, $result->safetyAssessment->verdict);
        self::assertNotNull($result->proposedState);
    }

    public function test_advisory_without_medical_claims_passes_the_ai_reply_through(): void
    {
        $turn = new AiTurnResult(reply: 'Main samajh gayi beta. Is mamle mein doctor se baat karna behtar rahega.');

        $result = $this->guard()->apply($turn, SafetyAssessment::advisory(['allergy']));

        self::assertSame('Main samajh gayi beta. Is mamle mein doctor se baat karna behtar rahega.', $result->reply);
        self::assertSame(SafetyVerdict::Advisory, $result->safetyAssessment->verdict);
    }

    public function test_guardrail_responses_never_leak_product_or_rule_truth(): void
    {
        foreach ([SafetyResponseGuardrail::BLOCK_REPLY, SafetyResponseGuardrail::ADVISORY_REPLY] as $reply) {
            self::assertNotEmpty($reply);
            self::assertFalse($this->guard()->containsForbiddenContent($reply));
            self::assertStringNotContainsString('tablet', $reply);
            self::assertStringNotContainsString('sku', $reply);
        }
    }

    public function test_determinism_same_input_same_output(): void
    {
        $assessment = SafetyAssessment::block(['emergency_chest_pain']);
        $turn = new AiTurnResult(reply: 'Kuch bhi.', intent: ConversationIntent::Unclear);

        $first = $this->guard()->apply($turn, $assessment);
        $second = $this->guard()->apply($turn, $assessment);

        self::assertSame($first->reply, $second->reply);
        self::assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $first->reply);
    }
}
