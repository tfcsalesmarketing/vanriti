<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Exceptions\InvalidConversationStateException;
use App\Dadi\ValueObjects\DadiMemoryItem;
use PHPUnit\Framework\TestCase;

class DadiMemoryItemTest extends TestCase
{
    public function test_constructs_with_domain_defaults(): void
    {
        $item = new DadiMemoryItem(category: 'concern', slot: 'scalp_condition', value: 'dry');

        self::assertTrue($item->active);
        self::assertSame('normal', $item->importance);
        self::assertSame(1, $item->sourceSequence);
        self::assertSame(1, $item->updatedSequence);
    }

    public function test_round_trips_through_array(): void
    {
        $item = new DadiMemoryItem(
            category: 'avoidance',
            slot: 'sulphate',
            value: 'avoid',
            active: true,
            importance: 'high',
            sourceSequence: 4,
            updatedSequence: 9,
        );

        self::assertEquals($item, DadiMemoryItem::fromArray($item->toArray()));
        self::assertSame([
            'category' => 'avoidance',
            'slot' => 'sulphate',
            'value' => 'avoid',
            'active' => true,
            'importance' => 'high',
            'source_sequence' => 4,
            'updated_sequence' => 9,
        ], $item->toArray());
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        new DadiMemoryItem(category: 'shopping', slot: 'budget', value: 'low');
    }

    public function test_unknown_importance_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        new DadiMemoryItem(category: 'note', slot: 'note', value: 'x', importance: 'urgent');
    }

    public function test_empty_slot_or_value_is_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        new DadiMemoryItem(category: 'note', slot: '   ', value: 'x');
    }

    public function test_invalid_sequences_are_rejected(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        new DadiMemoryItem(category: 'note', slot: 'a', value: 'x', sourceSequence: 5, updatedSequence: 3);
    }

    public function test_categories_are_a_controlled_vocabulary(): void
    {
        self::assertSame(
            ['concern', 'preference', 'avoidance', 'topic', 'note'],
            DadiMemoryItem::CATEGORIES,
        );
    }
}
