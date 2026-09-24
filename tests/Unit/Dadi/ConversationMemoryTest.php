<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Exceptions\InvalidConversationStateException;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiMemoryItem;
use PHPUnit\Framework\TestCase;

class ConversationMemoryTest extends TestCase
{
    public function test_remember_records_an_active_item_with_sequences(): void
    {
        $state = (new ConversationState)->remember('concern', 'scalp_condition', 'dry', 3);

        $item = $state->memoryItem('concern', 'scalp_condition');

        self::assertNotNull($item);
        self::assertSame('dry', $item->value);
        self::assertTrue($item->active);
        self::assertSame('normal', $item->importance);
        self::assertSame(3, $item->sourceSequence);
        self::assertSame(3, $item->updatedSequence);
        self::assertSame([$item], $state->memoryActive());
    }

    public function test_re_stating_a_slot_replaces_the_value_instead_of_appending(): void
    {
        $state = (new ConversationState)
            ->remember('concern', 'scalp_condition', 'oily', 2)
            ->remember('concern', 'scalp_condition', 'dry', 5);

        self::assertSame(1, $state->memoryCount());
        self::assertSame('dry', $state->memoryItem('concern', 'scalp_condition')->value);
        self::assertSame(2, $state->memoryItem('concern', 'scalp_condition')->sourceSequence);
        self::assertSame(5, $state->memoryItem('concern', 'scalp_condition')->updatedSequence);
    }

    public function test_corrections_overwrite_so_stale_never_coexists_with_current(): void
    {
        $state = (new ConversationState)
            ->remember('concern', 'scalp_condition', 'oily', 2)
            ->remember('concern', 'scalp_condition', 'dry', 3);

        $serialized = $state->toArray();

        self::assertSame('dry', $serialized['memory'][0]['value']);
        self::assertNotContains('oily', array_column($serialized['memory'], 'value'));
    }

    public function test_re_stating_an_inactive_slot_reactivates_it(): void
    {
        $state = (new ConversationState)
            ->remember('topic', 'hair', 'discussed', 2)
            ->forget('topic', 'hair', 4)
            ->remember('topic', 'hair', 'discussed_again', 6);

        $item = $state->memoryItem('topic', 'hair');

        self::assertTrue($item->active);
        self::assertSame('discussed_again', $item->value);
        self::assertSame(2, $item->sourceSequence);
        self::assertSame(6, $item->updatedSequence);
    }

    public function test_forget_deactivates_without_erasing_and_is_excluded_from_active_memory(): void
    {
        $state = (new ConversationState)
            ->remember('preference', 'wash_frequency', '1_2', 3)
            ->forget('preference', 'wash_frequency', 8);

        self::assertFalse($state->memoryItem('preference', 'wash_frequency')->active);
        self::assertSame(8, $state->memoryItem('preference', 'wash_frequency')->updatedSequence);
        self::assertSame([], $state->memoryActive());
        self::assertSame(1, $state->memoryCount());
    }

    public function test_forgetting_an_unknown_slot_throws(): void
    {
        $this->expectException(InvalidConversationStateException::class);

        (new ConversationState)->forget('note', 'missing', 1);
    }

    public function test_importance_carries_over_on_rephrase_but_can_be_upgraded_explicitly(): void
    {
        $state = (new ConversationState)
            ->remember('avoidance', 'sulphate', 'avoid', 2, importance: 'high')
            ->remember('avoidance', 'sulphate', 'strictly_avoid', 5);

        self::assertSame('high', $state->memoryItem('avoidance', 'sulphate')->importance);

        $state = $state->remember('avoidance', 'sulphate', 'avoid', 6, importance: 'high');

        self::assertSame('high', $state->memoryItem('avoidance', 'sulphate')->importance);
    }

    public function test_invalid_remember_inputs_are_rejected(): void
    {
        $state = new ConversationState;

        foreach (['remember' => [[], []]] as $invalid) {
            $this->expectException(InvalidConversationStateException::class);
            $state->remember('unknown_category', 'slot', 'value', 1);
        }
    }

    public function test_memory_is_remembered_from_a_new_instance_and_never_mutates_the_source(): void
    {
        $source = new ConversationState;
        $updated = $source->remember('concern', 'hair_fall', 'concerned', 2);

        self::assertSame([], $source->memory);
        self::assertSame(1, $updated->memoryCount());
        self::assertNotSame($source, $updated);
    }

    public function test_capacity_keeps_the_bounded_newest_active_items_per_category(): void
    {
        $state = (new ConversationState)
            ->remember('concern', 'a', 'one', 1, capacity: 2)
            ->remember('concern', 'b', 'two', 2, capacity: 2)
            ->remember('concern', 'c', 'three', 3, capacity: 2)
            ->remember('concern', 'd', 'four', 4, capacity: 2);

        $active = array_map(static fn (DadiMemoryItem $i): string => $i->slot, $state->memoryActive());
        self::assertCount(2, $active);
        self::assertSame(['c', 'd'], $active);
        self::assertSame(4, $state->memoryCount());
    }

    public function test_merge_later_values_win_and_older_only_slots_are_retained(): void
    {
        $earlier = (new ConversationState)
            ->remember('concern', 'scalp_condition', 'dry', 2)
            ->remember('topic', 'hair', 'discussed', 3);

        $later = (new ConversationState)
            ->remember('concern', 'scalp_condition', 'oily', 6)
            ->remember('topic', 'skin', 'discussed', 7);

        $merged = $earlier->merge($later);

        self::assertSame('oily', $merged->memoryItem('concern', 'scalp_condition')->value);
        self::assertSame(2, $merged->memoryItem('concern', 'scalp_condition')->sourceSequence);
        self::assertSame(6, $merged->memoryItem('concern', 'scalp_condition')->updatedSequence);
        self::assertSame('discussed', $merged->memoryItem('topic', 'hair')->value);
        self::assertSame('discussed', $merged->memoryItem('topic', 'skin')->value);
        self::assertSame(3, $merged->memoryCount());
    }

    public function test_memory_round_trips_through_array_serialization(): void
    {
        $state = (new ConversationState)
            ->remember('concern', 'scalp_condition', 'dry', 2, importance: 'high');

        $restored = ConversationState::fromArray($state->toArray());

        self::assertSame($state->toArray(), $restored->toArray());
        self::assertSame('dry', $restored->memoryItem('concern', 'scalp_condition')->value);
        self::assertSame('high', $restored->memoryItem('concern', 'scalp_condition')->importance);
    }

    public function test_memory_never_reaches_empty_isolation(): void
    {
        $state = (new ConversationState)->remember('note', 'greeting', 'namaste', 1);

        self::assertFalse($state->isEmpty());
        self::assertTrue((new ConversationState)->isEmpty());
    }

    public function test_forget_if_present_deactivates_an_active_item(): void
    {
        $state = (new ConversationState)
            ->remember('preference', 'fragrance_free', 'yes', 4)
            ->forgetIfPresent('preference', 'fragrance_free', 7);

        $item = $state->memoryItem('preference', 'fragrance_free');

        self::assertNotNull($item);
        self::assertFalse($item->active);
        self::assertSame([], $state->memoryActive());
    }

    public function test_forget_if_present_returns_same_state_when_slot_absent(): void
    {
        $state = (new ConversationState)->remember('topic', 'skin', 'discussed', 3);

        $unchanged = $state->forgetIfPresent('topic', 'missing', 9);

        self::assertSame($state->toArray(), $unchanged->toArray());
    }
}
