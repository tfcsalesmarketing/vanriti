<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\DadiMessage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DadiPageTest extends TestCase
{
    use RefreshDatabase;

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    public function test_fresh_guest_sees_the_onboarding_entry_on_the_dadi_page(): void
    {
        $response = $this->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('noindex, follow', false);
        $response->assertSee('id="dadiOnboarding"', false);
        $response->assertSee('Aap kya bhaasha mein baat karna chahenge?', false);
        $response->assertSee('Start talking to Dadi', false);
        $response->assertSee('id="dadiForm"', false);
        $response->assertSee('data-conversation-id=""', false);
        $response->assertSee('dadiStage"', false);
        $response->assertDontSee('dadi-msg', false);
        $response->assertDontSee('dadi-empty', false);
        $response->assertDontSee('dadi-locked', false);
    }

    public function test_guest_sees_a_language_aware_welcome_after_hindi_onboarding(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);

        $state = $conversation->state->withPreferredLanguage('hindi');
        $this->store()->updateState($conversation, $state);

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('id="dadiEmpty"', false);
        $response->assertSee('बताओ, क्या परेशानी हो रही है?', false);
        $response->assertSee('id="dadiForm"', false);
        $response->assertDontSee('id="dadiOnboarding"', false);
    }

    public function test_guest_sees_a_neutral_welcome_after_other_language_onboarding(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);

        $state = $conversation->state->withPreferredLanguage('other', 'Marathi');
        $this->store()->updateState($conversation, $state);

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('Baat karein', false);
        $response->assertDontSee('id="dadiOnboarding"', false);
    }

    public function test_page_restores_recent_messages_of_an_active_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'namaste dadi');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'namaste beta, kaise ho?');

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('namaste dadi', false);
        $response->assertSee('namaste beta, kaise ho?', false);
        $response->assertSee('data-conversation-id="'.$conversation->id.'"', false);
        $response->assertSee('id="dadiForm"', false);
        $response->assertSee('dadi-msg user', false);
        $response->assertDontSee('dadi-locked', false);
        $response->assertDontSee('id="dadiOnboarding"', false);
    }

    public function test_page_restores_a_guest_conversation_by_session(): void
    {
        $this->get(route('dadi.index'));
        $sessionId = $this->app['session.store']->getId();

        $conversation = $this->store()->start(sessionId: $sessionId);
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'guest baat hai');

        $response = $this->withCookie(config('session.cookie'), $sessionId)->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('guest baat hai', false);
        $response->assertSee('data-conversation-id="'.$conversation->id.'"', false);
    }

    public function test_held_conversation_is_rendered_read_only(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'mera saans ruk raha hai');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, SafetyResponseGuardrail::BLOCK_REPLY);
        $this->store()->applySafetyAssessment($conversation, SafetyAssessment::block(['emergency_breathing']));

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('mera saans ruk raha hai', false);
        $response->assertSee(substr(SafetyResponseGuardrail::BLOCK_REPLY, 0, 60), false);
        $response->assertSee('dadi-locked', false);
        $response->assertDontSee('id="dadiForm"', false);
    }

    public function test_restored_assistant_message_rehydrates_recommendation_cards(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);

        $product = Product::factory()->create(['name' => 'Baal Tonic', 'stock' => 20]);

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'product batao');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'yeh lo beta', [
            'recommended_product_references' => [$product->id],
        ]);

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('Baal Tonic', false);
        $response->assertSee('dadi-recs', false);
        $response->assertSee(route('product.show', $product), false);
    }

    public function test_the_dadi_entry_is_hidden_from_the_storefront_navigation(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(route('dadi.index'), false);
    }
}
