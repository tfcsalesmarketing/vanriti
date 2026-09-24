<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Exceptions\DadiOwnershipException;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\DadiContextMessage;
use App\Models\DadiMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    private function builder(): DadiContextBuilder
    {
        return DadiContextBuilder::fromConfig();
    }

    private function say(DadiConversationStore $store, $conversation, int $count): void
    {
        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $store->appendMessage($conversation, DadiMessage::ROLE_USER, "message-{$sequence}");
        }
    }

    public function test_builds_context_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Mere baal dry hain.');

        $context = $this->builder()->forConversation($conversation->id, user: $user);

        self::assertInstanceOf(DadiContext::class, $context);
        self::assertSame($conversation->id, $context->conversationId);
        self::assertSame('hi', $context->locale);
        self::assertTrue($context->state->isEmpty());
        self::assertCount(1, $context->recentMessages);
        self::assertInstanceOf(DadiContextMessage::class, $context->recentMessages[0]);
    }

    public function test_builds_context_for_a_guest_session(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertSame($conversation->id, $context->conversationId);
    }

    public function test_builder_requires_an_identity(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->expectException(DadiOwnershipException::class);

        $this->builder()->forConversation($conversation->id);
    }

    public function test_unknown_or_unowned_conversation_fails_identically(): void
    {
        $this->expectException(DadiOwnershipException::class);

        $this->builder()->forConversation(999, sessionId: 'session-aaa');
    }

    public function test_authenticated_user_cannot_build_another_users_context(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $conversation = $this->store()->start(user: $userA);

        $this->expectException(DadiOwnershipException::class);

        $this->builder()->forConversation($conversation->id, user: $userB);
    }

    public function test_guest_cannot_build_another_session_context(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->expectException(DadiOwnershipException::class);

        $this->builder()->forConversation($conversation->id, sessionId: 'session-bbb');
    }

    public function test_authenticated_user_cannot_build_the_same_row_as_a_guest(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);

        $this->expectException(DadiOwnershipException::class);

        $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');
    }

    public function test_recent_messages_are_bounded_and_chronological(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');
        $this->say($this->store(), $conversation, 25);

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertCount(20, $context->recentMessages);
        self::assertSame(6, $context->recentMessages[0]->sequence);
        self::assertSame(25, $context->recentMessages[19]->sequence);

        $sequences = array_column(
            array_map(static fn (DadiContextMessage $m): array => $m->toArray(), $context->recentMessages),
            'sequence',
        );
        self::assertSame(array_values(array_slice(range(6, 25), 0, null)), $sequences);
    }

    public function test_recent_messages_limit_is_configurable(): void
    {
        Config::set('dadi.context.recent_messages_limit', 5);

        $conversation = $this->store()->start(sessionId: 'session-aaa');
        $this->say($this->store(), $conversation, 12);

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertCount(5, $context->recentMessages);
        self::assertSame(8, $context->recentMessages[0]->sequence);
        self::assertSame(12, $context->recentMessages[4]->sequence);
    }

    public function test_recent_messages_stay_chronological_regardless_of_order(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'first');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'second');

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertSame(['first', 'second'], array_map(
            static fn (DadiContextMessage $m): string => $m->content,
            $context->recentMessages,
        ));
    }

    public function test_state_restores_authoritatively_from_persistence(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->store()->updateState($conversation, ConversationState::fromArray([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'attributes' => ['scalp_condition' => 'dry'],
        ]));

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertSame('hair', $context->state->section->value);
        self::assertSame('dry', $context->state->attribute('scalp_condition'));
        self::assertSame(['hair_dryness'], $context->state->concerns);
    }

    public function test_historical_memory_flows_into_the_context(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->store()->updateState($conversation, (new ConversationState)
            ->remember('concern', 'scalp_condition', 'dry', 2));

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertCount(1, $context->historicalMemory);
        self::assertSame('concern', $context->historicalMemory[0]->category);
        self::assertSame('dry', $context->historicalMemory[0]->value);
    }

    public function test_historical_memory_limit_is_bounded_by_configuration(): void
    {
        Config::set('dadi.context.historical_memory_limit', 3);

        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $state = new ConversationState;

        foreach (['a', 'b', 'c', 'd', 'e'] as $index => $slot) {
            $state = $state->remember('concern', $slot, 'value', $index + 1);
        }

        $this->store()->updateState($conversation, $state);

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertCount(3, $context->historicalMemory);
        self::assertSame(['e', 'd', 'c'], array_map(
            static fn ($item): string => $item->slot,
            $context->historicalMemory,
        ));
    }

    public function test_inactive_memory_is_never_exposed(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->store()->updateState($conversation, (new ConversationState)
            ->remember('topic', 'hair', 'discussed', 2)
            ->forget('topic', 'hair', 4));

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertSame([], $context->historicalMemory);
    }

    public function test_corrections_supersede_stale_memory_in_context(): void
    {
        $conversation = $this->store()->start(sessionId: 'session-aaa');

        $this->store()->updateState($conversation, (new ConversationState)
            ->remember('concern', 'scalp_condition', 'oily', 2)
            ->remember('concern', 'scalp_condition', 'dry', 3));

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        self::assertSame('dry', $context->historicalMemory[0]->value);
        self::assertCount(1, $context->historicalMemory);
        self::assertNotContains('oily', array_column($context->toArray()['historical_memory'], 'value'));
    }

    public function test_context_serialization_never_leaks_product_truth(): void
    {
        $forbidden = ['sku', 'price', 'stock', 'product_id', 'recommended_sku'];

        $conversation = $this->store()->start(sessionId: 'session-aaa');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Baal bahut dry hain.');
        $this->store()->updateState($conversation, (new ConversationState)
            ->remember('concern', 'scalp_condition', 'dry', 1));

        $context = $this->builder()->forConversation($conversation->id, sessionId: 'session-aaa');

        $needleFound = false;

        $serialized = $context->toArray();

        array_walk_recursive($serialized, static function (&$value, $key) use ($forbidden, &$needleFound): void {
            if (in_array((string) $key, $forbidden, true)) {
                $needleFound = true;
            }
        });

        self::assertFalse($needleFound);
    }
}
