<?php

namespace App\Dadi\ValueObjects;

/**
 * One concrete exchange in a Dadi conversation: what the customer said, the
 * Laravel-owned state it was processed against, and the AI turn that resulted.
 *
 * This is the persistence unit later stages will store and the context/memory
 * layer will consult.
 */
final readonly class ConversationTurn
{
    public function __construct(
        public string $customerMessage,
        public ConversationState $state,
        public AiTurnResult $result,
        public int $sequence = 1,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'customer_message' => $this->customerMessage,
            'state' => $this->state->toArray(),
            'result' => $this->result->toArray(),
            'sequence' => $this->sequence,
        ];
    }
}
