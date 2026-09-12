<?php

namespace Tests\Unit\Dadi;

use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\ConversationTurn;
use PHPUnit\Framework\TestCase;

class ConversationTurnTest extends TestCase
{
    public function test_turn_holds_message_state_result_and_sequence(): void
    {
        $state = new ConversationState;
        $result = new AiTurnResult(reply: 'Arre beta, baal dry hain? Batao na.');

        $turn = new ConversationTurn(
            customerMessage: 'Mere baal bahut dry hain.',
            state: $state,
            result: $result,
            sequence: 3,
        );

        self::assertSame('Mere baal bahut dry hain.', $turn->customerMessage);
        self::assertSame($state, $turn->state);
        self::assertSame($result, $turn->result);
        self::assertSame(3, $turn->sequence);
    }

    public function test_sequence_defaults_to_one(): void
    {
        $turn = new ConversationTurn(
            customerMessage: 'Namaste Dadi',
            state: new ConversationState,
            result: new AiTurnResult(reply: 'Namaste beta.'),
        );

        self::assertSame(1, $turn->sequence);
    }

    public function test_turn_serializes_to_a_flat_domain_shape(): void
    {
        $turn = new ConversationTurn(
            customerMessage: 'Haan',
            state: new ConversationState,
            result: new AiTurnResult(reply: 'Achha.'),
        );

        self::assertSame(
            ['customer_message', 'state', 'result', 'sequence'],
            array_keys($turn->toArray()),
        );
    }
}
