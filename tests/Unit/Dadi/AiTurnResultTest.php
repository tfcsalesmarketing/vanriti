<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Exceptions\InvalidAiTurnException;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use PHPUnit\Framework\TestCase;

class AiTurnResultTest extends TestCase
{
    public function test_reply_is_required(): void
    {
        $this->expectException(InvalidAiTurnException::class);

        new AiTurnResult(reply: '   ');
    }

    public function test_serialization_contains_only_the_domain_fields(): void
    {
        $result = new AiTurnResult(reply: 'Arre beta, samajh gayi.', intent: ConversationIntent::Concern);

        self::assertSame(
            ['reply', 'intent', 'proposed_state', 'requires_safety_review', 'safety_assessment'],
            array_keys($result->toArray()),
        );
    }

    public function test_never_contains_a_product_selection_field(): void
    {
        $forbidden = ['recommended_sku', 'recommended_product_id', 'selected_product', 'sku', 'product_id', 'product'];

        $result = new AiTurnResult(
            reply: 'Chalo beta, baat karte hain.',
            proposedState: ConversationState::fromArray(['section' => 'hair']),
        );

        self::assertEmpty(array_intersect(array_keys($result->toArray()), $forbidden));

        $serializedState = $result->toArray()['proposed_state'] ?? [];

        self::assertIsArray($serializedState);
        self::assertEmpty(array_intersect(array_keys($serializedState), $forbidden));
    }

    public function test_defaults_to_unclear_intent_without_safety_review(): void
    {
        $result = new AiTurnResult(reply: 'Haan beta, batao.');

        self::assertSame(ConversationIntent::Unclear, $result->intent);
        self::assertFalse($result->requiresSafetyReview);
        self::assertNull($result->proposedState);
    }

    public function test_carries_proposed_understanding_intent_and_safety_flag(): void
    {
        $proposed = ConversationState::fromArray(['section' => 'skin', 'concerns' => ['dullness']]);

        $result = new AiTurnResult(
            reply: 'Achha, samajh gayi. Skin ki baat karte hain.',
            intent: ConversationIntent::TopicSwitch,
            proposedState: $proposed,
            requiresSafetyReview: true,
        );

        self::assertSame('dullness', $result->proposedState->concerns[0]);
        self::assertTrue($result->requiresSafetyReview);
    }
}
