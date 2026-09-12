<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Enums\Section;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\DadiContextMessage;
use App\Dadi\ValueObjects\DadiMemoryItem;
use PHPUnit\Framework\TestCase;

class DadiContextTest extends TestCase
{
    private function context(): DadiContext
    {
        return new DadiContext(
            conversationId: 41,
            locale: 'hi',
            recentMessages: [
                new DadiContextMessage(role: 'user', content: 'Mere baal dry hain.', sequence: 1),
                new DadiContextMessage(role: 'assistant', content: 'Samajh gayi beta.', sequence: 2),
            ],
            state: ConversationState::fromArray([
                'section' => 'hair',
                'concerns' => ['hair_dryness'],
                'attributes' => ['scalp_condition' => 'dry'],
            ]),
            historicalMemory: [
                new DadiMemoryItem(category: 'concern', slot: 'scalp_condition', value: 'dry', sourceSequence: 1, updatedSequence: 2),
            ],
        );
    }

    public function test_carries_identity_locale_and_the_three_memory_layers(): void
    {
        $context = $this->context();

        self::assertSame(41, $context->conversationId);
        self::assertSame('hi', $context->locale);
        self::assertSame(Section::Hair, $context->state->section);
        self::assertCount(2, $context->recentMessages);
        self::assertCount(1, $context->historicalMemory);
    }

    public function test_recent_messages_are_plain_context_snapshots(): void
    {
        $context = $this->context();

        foreach ($context->recentMessages as $message) {
            self::assertInstanceOf(DadiContextMessage::class, $message);
        }

        self::assertSame('Mere baal dry hain.', $context->recentMessages[0]->content);
        self::assertSame(1, $context->recentMessages[0]->sequence);
    }

    public function test_historical_memory_is_made_of_memory_items(): void
    {
        foreach ($this->context()->historicalMemory as $item) {
            self::assertInstanceOf(DadiMemoryItem::class, $item);
        }
    }

    public function test_serialization_contains_only_the_domain_shape(): void
    {
        self::assertSame(
            ['conversation_id', 'locale', 'recent_messages', 'state', 'historical_memory'],
            array_keys($this->context()->toArray()),
        );
    }

    public function test_serialization_never_contains_product_truth(): void
    {
        $forbidden = ['sku', 'price', 'stock', 'product_id', 'recommended_sku', 'catalogue'];

        $needleFound = false;

        $serialized = $this->context()->toArray();

        array_walk_recursive($serialized, static function (&$value, $key) use ($forbidden, &$needleFound): void {
            if (in_array((string) $key, $forbidden, true)) {
                $needleFound = true;
            }
        });

        self::assertFalse($needleFound);
    }
}
