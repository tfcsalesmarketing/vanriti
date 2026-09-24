<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Contracts\AiProvider;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Models\Admin;
use App\Models\DadiConversation;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class OnboardingFakeProvider implements AiProvider
{
    public ?AiRequest $lastRequest = null;

    public int $calls = 0;

    public function __construct(public array $data) {}

    public function generate(AiRequest $request): AiResponse
    {
        $this->lastRequest = $request;
        $this->calls++;

        return new AiResponse(
            json_encode($this->data, JSON_THROW_ON_ERROR),
            $this->data,
        );
    }
}

class DadiOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    private function onboardAsGuest(array $payload, ?string $sessionId = null): TestResponse
    {
        $request = $this;

        if ($sessionId !== null) {
            // postJson silently drops plain cookies unless the test client also
            // carries them, so credentials are required for identity reuse.
            $request = $request->withCredentials()->withCookie(config('session.cookie'), $sessionId);
        }

        return $request->postJson(route('dadi.onboarding'), $payload);
    }

    private function messageAsGuest(string $message, ?int $conversationId, ?string $sessionId): TestResponse
    {
        $payload = ['message' => $message];

        if ($conversationId !== null) {
            $payload['conversation_id'] = $conversationId;
        }

        return $this
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->postJson(route('dadi.message'), $payload);
    }

    public function test_english_guest_onboarding_creates_a_blank_conversation_with_the_preference(): void
    {
        $response = $this->onboardAsGuest(['language' => 'english']);

        $response->assertOk();
        $response->assertJsonPath('conversation.status', 'active');
        $response->assertJsonPath('language.code', 'english');
        $response->assertJsonPath('language.name', null);

        $conversation = DadiConversation::find($response->json('conversation.id'));

        $this->assertNotNull($conversation);
        $this->assertNull($conversation->user_id);
        $this->assertNotNull($conversation->session_id);
        $this->assertSame('english', $conversation->state->preferredLanguage);
        $this->assertNull($conversation->state->preferredLanguageName);

        // Correction 1: onboarding must NEVER persist a greeting message. The
        // first real user turn stays the first conversational message.
        $this->assertDatabaseCount('dadi_messages', 0);
    }

    public function test_non_greeting_welcome_titles_render_per_language_after_onboarding(): void
    {
        foreach ([
            'english' => "Tell me, what's bothering you?",
            'hindi' => 'बताओ, क्या परेशानी हो रही है?',
            'hinglish' => 'Batao, kya pareshaan kar raha hai?',
        ] as $language => $title) {
            $response = $this->onboardAsGuest(['language' => $language]);
            $response->assertOk();

            $sessionId = $this->app['session.store']->getId();
            $id = $response->json('conversation.id');

            $page = $this
                ->withCookie(config('session.cookie'), $sessionId)
                ->get(route('dadi.index'));

            $page->assertOk();
            // The title goes through Blade escaping (what&#039;s, &middot;), so
            // assert with HTML escaping enabled on the expected needle.
            $page->assertSee($title);
            $page->assertSee('data-conversation-id="'.$id.'"', false);
            $page->assertDontSee('id="dadiOnboarding"', false);
            $page->assertDontSee('dadi-msg', false);
        }
    }

    public function test_other_language_onboarding_stores_the_bounded_name_and_neutral_welcome(): void
    {
        $response = $this->onboardAsGuest(['language' => 'other', 'language_name' => '  Marathi ']);

        $response->assertOk();
        $response->assertJsonPath('language.code', 'other');
        $response->assertJsonPath('language.name', 'Marathi');

        $conversation = DadiConversation::find($response->json('conversation.id'));

        $this->assertSame('other', $conversation->state->preferredLanguage);
        $this->assertSame('Marathi', $conversation->state->preferredLanguageName);
        $this->assertSame('en', $conversation->locale);
        $this->assertDatabaseCount('dadi_messages', 0);

        $sessionId = $this->app['session.store']->getId();

        $page = $this->withCookie(config('session.cookie'), $sessionId)->get(route('dadi.index'));

        $page->assertSee("Baat karein · Let's talk");
    }

    public function test_other_language_without_a_name_is_rejected_and_creates_nothing(): void
    {
        $response = $this->onboardAsGuest(['language' => 'other']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('language_name');
        $this->assertSame(0, DadiConversation::count());
        $this->assertDatabaseCount('dadi_messages', 0);
    }

    public function test_invalid_language_and_control_characters_are_rejected(): void
    {
        $this->onboardAsGuest(['language' => 'french'])->assertStatus(422);
        $this->assertSame(0, DadiConversation::count());

        $this->onboardAsGuest([
            'language' => 'other',
            'language_name' => "Mar\ra thi",
        ])->assertStatus(422);
        $this->assertSame(0, DadiConversation::count());
    }

    public function test_mid_conversation_switch_is_natural_and_the_preference_stays_laravel_owned(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $fake = new OnboardingFakeProvider([
            'reply' => 'Bilkul beta, Hindi mein baat karte hain.',
            'understanding' => ['section' => null, 'concerns' => [], 'attributes' => [], 'preferences' => [], 'memory' => []],
        ]);
        $this->app->instance(AiProvider::class, $fake);

        $this->messageAsGuest('Ab Hindi mein samjhao', $id, $sessionId)->assertOk();

        // The trusted context line carries the stored preference to the AI.
        $this->assertNotNull($fake->lastRequest);
        $this->assertStringContainsString("Customer's preferred language: hindi", $fake->lastRequest->userMessage);

        // And the stored preference never changed: the customer's field is
        // Laravel-created state, not AI-generated state.
        $conversation = DadiConversation::find($id);
        $this->assertSame('hindi', $conversation->state->preferredLanguage);
    }

    public function test_guest_continuity_reuses_the_same_conversation_after_onboarding(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'english']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'Namaste beta.',
            'understanding' => ['section' => null, 'concerns' => [], 'attributes' => [], 'preferences' => [], 'memory' => []],
        ]));

        $message = $this->messageAsGuest('namaste', null, $sessionId);

        $message->assertOk();
        $this->assertSame($id, $message->json('conversation.id'));
        $this->assertSame(1, DadiConversation::count());

        // First real turn: user sequence 1 (nothing was ever written at onboarding).
        $this->assertDatabaseHas('dadi_messages', [
            'conversation_id' => $id,
            'role' => 'user',
            'sequence' => 1,
            'content' => 'namaste',
        ]);
    }

    public function test_authenticated_user_onboarding_owns_by_user_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $response = $this->onboardAsGuest(['language' => 'hindi']);

        $response->assertOk();

        $conversation = DadiConversation::find($response->json('conversation.id'));

        $this->assertSame($user->id, $conversation->user_id);
        $this->assertNull($conversation->session_id);
        $this->assertSame('hindi', $conversation->state->preferredLanguage);
    }

    public function test_safety_hold_preserves_the_language_and_blocks_a_fresh_onboarding(): void
    {
        $approved = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $approved->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'thik hai beta',
            'understanding' => ['section' => null, 'concerns' => [], 'attributes' => [], 'preferences' => [], 'memory' => []],
        ]));

        $turn = $this->messageAsGuest('mera saans ruk raha hai, kya karun?', $id, $sessionId);

        $turn->assertOk();
        $turn->assertJsonPath('message.content', SafetyResponseGuardrail::BLOCK_REPLY);
        $turn->assertJsonPath('safety.restricted', true);

        $held = DadiConversation::find($id);

        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $held->status);
        $this->assertSame('hindi', $held->state->preferredLanguage);
        $this->assertNull($held->state->preferredLanguageName);

        // The held conversation can never be resumed for a fresh onboarding.
        $retry = $this->onboardAsGuest(['language' => 'english'], $sessionId);

        $retry->assertStatus(409);
        $retry->assertJsonPath('error', 'conversation_locked');
        $this->assertSame(1, DadiConversation::count());
    }

    public function test_emergency_reply_is_used_and_the_hold_still_keeps_the_language(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'kuch bhi',
            'understanding' => ['section' => null, 'concerns' => [], 'attributes' => [], 'preferences' => [], 'memory' => []],
        ]));

        $turn = $this->messageAsGuest('mera saans ruk raha hai', $id, $sessionId);

        $turn->assertOk();
        $turn->assertJsonPath('message.content', SafetyResponseGuardrail::BLOCK_REPLY);
        $turn->assertJsonPath('safety.restricted', true);

        $conversation = DadiConversation::find($id);

        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $conversation->status);
        $this->assertSame('hindi', $conversation->state->preferredLanguage);
    }

    public function test_recommendations_flow_after_onboarding_and_the_language_is_preserved_by_the_turn(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $product = Product::factory()->create(['name' => 'Dry Hair Oil', 'stock' => 15]);
        $this->approveProduct($product);

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'Beta, dryness ke liye yeh dekhen.',
            'intent' => 'concern',
            'safety_review' => false,
            'understanding' => [
                'section' => 'hair',
                'concerns' => ['hair_dryness'],
                'attributes' => ['scalp_condition' => 'dry'],
                'preferences' => [],
                'memory' => [],
            ],
        ]));

        $turn = $this->messageAsGuest('mere baal dry ho gaye hain', $id, $sessionId);

        $turn->assertOk();
        $turn->assertJsonCount(1, 'recommendations');
        $turn->assertJsonPath('recommendations.0.product_reference', (int) $product->id);

        $conversation = DadiConversation::find($id);

        $this->assertSame('hindi', $conversation->state->preferredLanguage);
        $this->assertTrue($conversation->state->hasConcern('hair_dryness'));
    }

    public function test_ai_output_cannot_overwrite_the_system_owned_language(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'Theek hai.',
            'understanding' => [
                'section' => null,
                'concerns' => [],
                'attributes' => ['preferred_language' => 'french', 'preferred_language_name' => 'oops'],
                'preferences' => ['preferred_language' => 'french'],
                'memory' => [],
            ],
        ]));

        $this->messageAsGuest('hello', $id, $sessionId)->assertOk();

        $conversation = DadiConversation::find($id);

        $this->assertSame('hindi', $conversation->state->preferredLanguage);
        $this->assertNull($conversation->state->preferredLanguageName);
        $this->assertArrayNotHasKey('preferred_language', $conversation->state->attributes);
        $this->assertArrayNotHasKey('preferred_language_name', $conversation->state->attributes);
        $this->assertArrayNotHasKey('preferred_language', $conversation->state->preferences);
    }

    public function test_two_real_turns_after_onboarding_keep_deterministic_sequence(): void
    {
        $onboarding = $this->onboardAsGuest(['language' => 'hinglish']);
        $id = $onboarding->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $this->app->instance(AiProvider::class, new OnboardingFakeProvider([
            'reply' => 'Batao beta.',
            'understanding' => ['section' => null, 'concerns' => [], 'attributes' => [], 'preferences' => [], 'memory' => []],
        ]));

        $first = $this->messageAsGuest('hello', $id, $sessionId);

        $first->assertOk();

        $this->assertDatabaseHas('dadi_messages', ['conversation_id' => $id, 'role' => 'user', 'sequence' => 1]);
        $this->assertDatabaseHas('dadi_messages', ['conversation_id' => $id, 'role' => 'assistant', 'sequence' => 2]);

        $second = $this->messageAsGuest('skin ki baat', $id, $sessionId);

        $second->assertOk();

        $this->assertDatabaseHas('dadi_messages', ['conversation_id' => $id, 'role' => 'user', 'sequence' => 3]);
        $this->assertDatabaseHas('dadi_messages', ['conversation_id' => $id, 'role' => 'assistant', 'sequence' => 4]);
    }

    public function test_resuming_an_onboarding_guest_updates_language_in_place_without_a_new_conversation(): void
    {
        $first = $this->onboardAsGuest(['language' => 'hindi']);
        $id = $first->json('conversation.id');
        $sessionId = $this->app['session.store']->getId();

        $second = $this->onboardAsGuest(['language' => 'other', 'language_name' => 'Bengali'], $sessionId);

        $second->assertOk();
        $this->assertSame($id, $second->json('conversation.id'));
        $this->assertSame(1, DadiConversation::count());

        $conversation = DadiConversation::find($id);

        $this->assertSame('other', $conversation->state->preferredLanguage);
        $this->assertSame('Bengali', $conversation->state->preferredLanguageName);
    }

    public function test_onboarding_never_touches_messages_or_recommendation_records(): void
    {
        $this->onboardAsGuest(['language' => 'english']);

        $this->assertSame(1, DadiConversation::count());
        $this->assertDatabaseCount('dadi_messages', 0);
        $this->assertDatabaseCount('dadi_recommendation_events', 0);
    }

    private function approveProduct(Product $product): void
    {
        $store = $this->app->make(DadiProductProfileStore::class);

        $admin = Admin::factory()->create();
        $permission = Permission::firstOrCreate([
            'slug' => DadiProductProfileStore::REVIEW_PERMISSION,
        ], ['name' => 'Review dadi product profiles']);
        $role = Role::create(['slug' => 'dadi-reviewer-'.Str::random(6), 'name' => 'Dadi Reviewer']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        $profile = $store->create($product, [
            'sections' => ['hair'],
            'concerns' => ['hair_dryness'],
            'positioning' => 'Gentle daily care.',
            'approved_benefits' => ['Nourishes dry lengths'],
            'approved_usage_context' => ['Apply to damp lengths'],
            'approved_precautions' => ['Avoid contact with eyes'],
            'suitability_notes' => ['Consider for dry hair'],
        ], $admin);

        $store->submitForReview($profile, $admin);
        $store->approve($profile, $admin);
    }
}
