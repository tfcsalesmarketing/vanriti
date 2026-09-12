<?php

namespace App\Dadi\ValueObjects;

/**
 * The immutable conversational context handed to the AI layer.
 *
 * Assembles the three memory layers without mutating anything:
 *   - recent_messages      Layer A: bounded, chronological immediate history
 *   - state                Layer B: the authoritative Laravel-owned understanding
 *   - historical_memory    Layer C: bounded, active classified memory items
 *
 * The context is plain domain data only: no Eloquent models, no product
 * catalogue, no provider payloads. It carries opinions about nothing except
 * what the customer said and how Laravel understood it.
 */
final readonly class DadiContext
{
    /**
     * @param  array<int,DadiContextMessage>  $recentMessages
     * @param  array<int,DadiMemoryItem>  $historicalMemory
     */
    public function __construct(
        public int $conversationId,
        public string $locale,
        public array $recentMessages,
        public ConversationState $state,
        public array $historicalMemory,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'locale' => $this->locale,
            'recent_messages' => array_map(
                static fn (DadiContextMessage $message): array => $message->toArray(),
                $this->recentMessages,
            ),
            'state' => $this->state->toArray(),
            'historical_memory' => array_map(
                static fn (DadiMemoryItem $item): array => $item->toArray(),
                $this->historicalMemory,
            ),
        ];
    }
}
