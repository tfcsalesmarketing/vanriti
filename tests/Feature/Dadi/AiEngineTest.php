<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Ai\ConversationalFailureResponder;
use App\Dadi\Ai\DadiConversationEngine;
use App\Dadi\Ai\Providers\OpenAiProvider;
use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Contracts\ConversationEngine;
use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\ConversationState;
use App\Models\DadiMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FakeAiProvider implements AiProvider
{
    public ?AiRequest $lastRequest = null;

    public ?\Throwable $exception = null;

    public function __construct(public AiResponse $response) {}

    public function generate(AiRequest $request): AiResponse
    {
        $this->lastRequest = $request;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }
}

class AiEngineTest extends TestCase
{
    use RefreshDatabase;

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    private function structuredResponse(array $data): AiResponse
    {
        return new AiResponse(
            content: json_encode($data, JSON_THROW_ON_ERROR),
            data: $data,
        );
    }

    public function test_container_resolves_the_provider_and_engine_bindings(): void
    {
        $this->assertInstanceOf(OpenAiProvider::class, $this->app->make(AiProvider::class));
        $this->assertInstanceOf(ConversationEngine::class, $this->app->make(ConversationEngine::class));
        $this->assertInstanceOf(DadiConversationEngine::class, $this->app->make(ConversationEngine::class));
    }

    public function test_minimal_seam_responds_gracefully_without_an_api_key(): void
    {
        $engine = $this->app->make(ConversationEngine::class);

        $result = $engine->respond(new ConversationState, 'Namaste Dadi');

        $this->assertContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        $this->assertSame(ConversationIntent::Unclear, $result->intent);
        $this->assertNull($result->proposedState);
    }

    public function test_end_to_end_turn_produces_a_mergeable_proposal(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');

        for ($sequence = 1; $sequence <= 25; $sequence++) {
            $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, "message-{$sequence}");
        }

        $this->store()->updateState($conversation, (new ConversationState)
            ->withAttribute('scalp_condition', 'dry')
            ->withConcern('hair_fall')
            ->remember('concern', 'scalp_condition', 'dry', 5));

        $fake = new FakeAiProvider($this->structuredResponse([
            'reply' => 'Arre beta, baal ki baat karte hain. Theek se dhona zaroori hai.',
            'intent' => 'concern',
            'safety_review' => false,
            'understanding' => [
                'section' => 'hair',
                'concerns' => ['hair_dryness'],
                'attributes' => ['scalp_condition' => 'oily'],
                'preferences' => ['wash_frequency' => '1_2'],
                'memory' => [
                    ['category' => 'concern', 'slot' => 'scalp_condition', 'value' => 'oily', 'importance' => 'high'],
                ],
            ],
        ]));

        $this->app->instance(AiProvider::class, $fake);

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);
        $turn = $this->app->make(ConversationEngine::class)->respondWithContext($context, 'Mere baal thode dry lagte hain');

        $this->assertSame('Arre beta, baal ki baat karte hain. Theek se dhona zaroori hai.', $turn->reply);
        $this->assertSame(ConversationIntent::Concern, $turn->intent);

        $proposal = $turn->proposedState;

        $this->assertNotNull($proposal);
        $this->assertSame('hair', $proposal->section->value);
        $this->assertSame(['hair_fall', 'hair_dryness'], $proposal->concerns);
        $this->assertSame('oily', $proposal->attribute('scalp_condition'));
        $this->assertSame('1_2', $proposal->preference('wash_frequency'));

        $memory = $proposal->memoryItem('concern', 'scalp_condition');
        $this->assertNotNull($memory);
        $this->assertSame('oily', $memory->value);
        $this->assertSame('high', $memory->importance);

        $request = $fake->lastRequest;
        $this->assertNotNull($request);
        $this->assertSame('hi', $request->locale);
        $this->assertStringContainsString('message-25', $request->userMessage);
        $this->assertStringNotContainsString('- [customer] message-5', $request->userMessage);
        $this->assertStringNotContainsString('- [customer] message-1 ', $request->userMessage);
        $this->assertStringContainsString('<customer_message>Mere baal thode dry lagte hain</customer_message>', $request->userMessage);

        $this->assertDatabaseMissing('dadi_messages', [
            'conversation_id' => $conversation->id,
            'role' => DadiMessage::ROLE_ASSISTANT,
        ]);
    }

    public function test_authoritative_state_is_updated_only_after_laravel_commit(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $this->store()->updateState($conversation, (new ConversationState)->withAttribute('scalp_condition', 'dry'));

        $fake = new FakeAiProvider($this->structuredResponse([
            'reply' => 'Theek hai beta.',
            'understanding' => ['attributes' => ['scalp_condition' => 'oily']],
        ]));

        $this->app->instance(AiProvider::class, $fake);

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);
        $turn = $this->app->make(ConversationEngine::class)->respondWithContext($context, 'ab maine shampoo change kiya');

        $proposal = $turn->proposedState;
        $this->assertNotNull($proposal);
        $this->assertSame('oily', $proposal->attribute('scalp_condition'));

        $reloadedBefore = $conversation->fresh()->state;
        $this->assertSame('dry', $reloadedBefore->attribute('scalp_condition'));

        $this->store()->updateState($conversation, $proposal);
        $reloadedAfter = $conversation->fresh()->state;
        $this->assertSame('oily', $reloadedAfter->attribute('scalp_condition'));
    }

    public function test_forbidden_product_selection_is_rejected_before_persistence(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $this->store()->updateState($conversation, (new ConversationState)->withAttribute('scalp_condition', 'dry'));

        $fake = new FakeAiProvider($this->structuredResponse([
            'reply' => 'Yeh lo beta.',
            'understanding' => ['recommended_sku' => 'VANRITI-042'],
        ]));

        $this->app->instance(AiProvider::class, $fake);

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);
        $turn = $this->app->make(ConversationEngine::class)->respondWithContext($context, 'kuch recommend karo');

        $this->assertContains($turn->reply, ConversationalFailureResponder::FALLBACKS);
        $this->assertNull($turn->proposedState);

        $this->assertSame('dry', $conversation->fresh()->state->attribute('scalp_condition'));
    }

    public function test_provider_failure_leaves_the_authoritative_state_untouched(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $this->store()->updateState($conversation, (new ConversationState)->withAttribute('scalp_condition', 'dry'));

        $fake = new FakeAiProvider(new AiResponse('{}', []));
        $fake->exception = new AiProviderException(
            AiProviderException::CATEGORY_RATE_LIMIT,
            'OpenAI error (HTTP 429, type rate_limit).',
        );

        $this->app->instance(AiProvider::class, $fake);

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);
        $turn = $this->app->make(ConversationEngine::class)->respondWithContext($context, 'hello dadi');

        $this->assertContains($turn->reply, ConversationalFailureResponder::FALLBACKS);
        $this->assertNull($turn->proposedState);
        $this->assertSame('dry', $conversation->fresh()->state->attribute('scalp_condition'));
    }
}
