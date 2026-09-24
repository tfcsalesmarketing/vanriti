<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\ValueObjects\ConversationState;
use App\Models\DadiConversation;
use App\Models\DadiMessage;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ConversationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    public function test_authenticated_user_can_start_a_conversation(): void
    {
        $user = User::factory()->create();

        $conversation = $this->store()->start(user: $user);

        $this->assertDatabaseHas('dadi_conversations', [
            'id' => $conversation->id,
            'user_id' => $user->id,
            'session_id' => null,
            'status' => DadiConversation::STATUS_ACTIVE,
        ]);
    }

    public function test_guest_can_start_a_conversation_with_a_session_id(): void
    {
        $conversation = $this->store()->start(sessionId: 'guest-session-abc');

        $this->assertDatabaseHas('dadi_conversations', [
            'id' => $conversation->id,
            'user_id' => null,
            'session_id' => 'guest-session-abc',
        ]);
    }

    public function test_start_requires_an_owner(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->store()->start();
    }

    public function test_authenticated_ownership_always_wins_over_guest_session(): void
    {
        $user = User::factory()->create();

        $conversation = $this->store()->start(user: $user, sessionId: 'ignored-session');

        $this->assertSame($user->id, $conversation->user_id);
        $this->assertNull($conversation->session_id);
    }

    public function test_conversation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $this->store()->start(user: $user);
        $this->store()->start(user: $user);

        self::assertCount(2, $user->dadiConversations);
        self::assertSame($user->id, $user->dadiConversations->first()->user_id);
        self::assertSame($user->id, $user->dadiConversations->first()->user->id);
    }

    public function test_lifecycle_is_explicit_and_defaults_to_active(): void
    {
        $conversation = $this->store()->start(user: User::factory()->create());

        self::assertTrue($conversation->isActive());

        $conversation->complete();
        self::assertSame(DadiConversation::STATUS_COMPLETED, $conversation->fresh()->status);
        self::assertFalse($conversation->fresh()->isActive());

        $conversation->markAbandoned();
        self::assertSame(DadiConversation::STATUS_ABANDONED, $conversation->fresh()->status);

        $conversation->holdForSafety();
        self::assertSame(DadiConversation::STATUS_SAFETY_HOLD, $conversation->fresh()->status);
    }

    public function test_conversation_is_not_completed_just_because_messages_stop(): void
    {
        $conversation = $this->store()->start(user: User::factory()->create());
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Mere baal dry hain.');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'Arre beta...');

        self::assertSame(DadiConversation::STATUS_ACTIVE, $conversation->fresh()->status);
    }

    public function test_statuses_are_a_controlled_lifecycle_vocabulary(): void
    {
        self::assertSame(
            ['active', 'completed', 'abandoned', 'safety_hold'],
            DadiConversation::statuses(),
        );
    }

    public function test_locale_is_persisted(): void
    {
        $conversation = $this->store()->start(sessionId: 's', locale: 'hi');

        self::assertSame('hi', $conversation->fresh()->locale);
    }

    public function test_last_activity_tracks_activity(): void
    {
        $before = now();
        $conversation = $this->store()->start(sessionId: 's');

        self::assertNotNull($conversation->last_activity_at);
        self::assertTrue($conversation->last_activity_at->greaterThanOrEqualTo($before->subSecond()));

        $conversation->touchActivity();

        self::assertTrue($conversation->fresh()->last_activity_at->greaterThanOrEqualTo($before->subSecond()));
    }

    public function test_authoritative_state_round_trips_through_persistence(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $state = ConversationState::fromArray([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'attributes' => ['scalp_condition' => 'dry'],
        ]);

        $this->store()->updateState($conversation, $state);

        $reloaded = $conversation->fresh();

        self::assertInstanceOf(ConversationState::class, $reloaded->state);
        self::assertSame($state->toArray(), $reloaded->state->toArray());

        $stored = DadiConversation::query()->whereKey($conversation->id)->first()->getRawOriginal('state');
        self::assertNotNull($stored);
    }

    public function test_messages_are_appended_with_deterministic_sequence(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Haan Dadi');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'Samajh gayi beta.');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Aur skin ke liye?');

        self::assertSame(
            ['Haan Dadi', 'Samajh gayi beta.', 'Aur skin ke liye?'],
            $conversation->messages()->pluck('content')->all(),
        );
        self::assertSame([1, 2, 3], $conversation->messages()->pluck('sequence')->all());

        $last = $conversation->lastMessage();
        self::assertSame(3, $last->sequence);
        self::assertSame('Aur skin ke liye?', $last->content);
    }

    public function test_sequence_is_max_plus_one_even_after_a_gap(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'a');
        $middle = $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'b');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'c');

        $middle->delete();

        $next = $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'd');

        self::assertSame(4, $next->sequence);
        self::assertSame([1, 3, 4], $conversation->messages()->pluck('sequence')->all());
    }

    public function test_message_orders_by_sequence_deterministically(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $c = $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'first');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'second');

        self::assertSame(1, $c->sequence);
        self::assertSame(['first', 'second'], $conversation->messages()->pluck('content')->all());
    }

    public function test_message_belongs_to_conversation(): void
    {
        $conversation = $this->store()->start(sessionId: 's');
        $message = $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'Namaste');

        self::assertSame($conversation->id, $message->conversation_id);
        self::assertSame($conversation->id, $message->conversation->id);
    }

    public function test_roles_are_restricted_to_the_domain_set(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'u');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'a');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_SYSTEM, 's');

        $this->expectException(InvalidArgumentException::class);

        $this->store()->appendMessage($conversation, 'narrator', 'x');
    }

    public function test_message_content_is_required(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $this->expectException(InvalidArgumentException::class);

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, '   ');
    }

    public function test_metadata_is_stored_as_non_authoritative_array(): void
    {
        $conversation = $this->store()->start(sessionId: 's');

        $message = $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'Beta', [
            'intent' => 'concern',
            'provider' => 'stub',
        ]);

        self::assertSame(['intent' => 'concern', 'provider' => 'stub'], $message->fresh()->metadata);
        self::assertNotNull($message->created_at);
    }

    public function test_duplicate_sequence_position_is_impossible_at_the_database(): void
    {
        $conversation = $this->store()->start(sessionId: 's');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'first');

        $this->expectException(QueryException::class);

        DadiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => DadiMessage::ROLE_USER,
            'sequence' => 1,
            'content' => 'duplicate position',
        ]);
    }

    public function test_authenticated_user_cannot_reach_another_users_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->store()->start(user: $userA);

        self::assertNull($this->store()->latestActive(user: $userB));
        self::assertNull($this->store()->latestActive(user: $userB, sessionId: 'whatever'));

        self::assertSame(0, DadiConversation::query()->ownedBy($userB, null)->count());
        self::assertSame(1, DadiConversation::query()->ownedBy($userA, null)->count());
    }

    public function test_guest_sessions_are_isolated_from_each_other(): void
    {
        $this->store()->start(sessionId: 'session-aaa');
        $this->store()->start(sessionId: 'session-bbb');

        $forB = $this->store()->latestActive(sessionId: 'session-bbb');

        self::assertNotNull($forB);
        self::assertSame('session-bbb', $forB->session_id);
        self::assertSame(1, DadiConversation::query()->ownedBy(null, 'session-aaa')->count());
        self::assertSame(1, DadiConversation::query()->ownedBy(null, 'session-bbb')->count());
    }

    public function test_guest_cannot_reach_another_guests_conversation(): void
    {
        $this->store()->start(sessionId: 'session-aaa');

        self::assertNull($this->store()->latestActive(sessionId: 'session-bbb'));
        self::assertSame(0, DadiConversation::query()->ownedBy(null, 'session-bbb')->count());
        self::assertSame(1, DadiConversation::query()->ownedBy(null, 'session-aaa')->count());
    }

    public function test_guest_cannot_reach_an_authenticated_users_conversation(): void
    {
        $user = User::factory()->create();
        $this->store()->start(user: $user);

        self::assertSame(0, DadiConversation::query()->ownedBy(null, 'session-aaa')->count());
        self::assertNull($this->store()->latestActive(sessionId: 'session-aaa'));
    }

    public function test_latest_active_returns_only_active_conversations(): void
    {
        $user = User::factory()->create();
        $this->store()->start(user: $user)->complete();
        $active = $this->store()->start(user: $user);

        self::assertSame($active->id, $this->store()->latestActive(user: $user)->id);
    }
}
