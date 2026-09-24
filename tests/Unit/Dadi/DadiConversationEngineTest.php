<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Ai\ConversationalFailureResponder;
use App\Dadi\Ai\DadiConversationEngine;
use App\Dadi\Ai\Prompts\DadiPromptBuilder;
use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use App\Dadi\ValueObjects\DadiContextMessage;
use PHPUnit\Framework\TestCase;

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

class DadiConversationEngineTest extends TestCase
{
    private function engine(FakeAiProvider $provider): DadiConversationEngine
    {
        return new DadiConversationEngine(
            promptBuilder: new DadiPromptBuilder,
            provider: $provider,
            validator: new AiOutputValidator,
            failureResponder: new ConversationalFailureResponder,
        );
    }

    private function context(): DadiContext
    {
        return new DadiContext(
            conversationId: 1,
            locale: 'hi',
            recentMessages: [
                new DadiContextMessage(role: 'user', content: 'Mere baal dry hain.', sequence: 1),
                new DadiContextMessage(role: 'assistant', content: 'Samajh gayi beta.', sequence: 2),
            ],
            state: (new ConversationState)
                ->withAttribute('scalp_condition', 'dry')
                ->remember('concern', 'scalp_condition', 'dry', 2),
            historicalMemory: [],
        );
    }

    private function structuredResponse(array $data): AiResponse
    {
        return new AiResponse(
            content: json_encode($data, JSON_THROW_ON_ERROR),
            data: $data,
        );
    }

    public function test_natural_message_produces_a_validated_turn(): void
    {
        $provider = new FakeAiProvider($this->structuredResponse([
            'reply' => 'Arre beta, baal ki baat karte hain.',
            'intent' => 'concern',
            'understanding' => [
                'section' => 'hair',
                'concerns' => ['hair_dryness'],
                'attributes' => ['scalp_condition' => 'oily'],
            ],
        ]));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'Mere baal thode dry lagte hain');

        self::assertSame('Arre beta, baal ki baat karte hain.', $result->reply);
        self::assertSame(ConversationIntent::Concern, $result->intent);
        self::assertNotNull($result->proposedState);
        self::assertSame('oily', $result->proposedState->attribute('scalp_condition'));
        self::assertSame(['hair_dryness'], $result->proposedState->concerns);
    }

    public function test_provider_failure_falls_back_gracefully_with_state_intact(): void
    {
        $provider = new FakeAiProvider(new AiResponse('{}', []));
        $provider->exception = new AiProviderException(
            AiProviderException::CATEGORY_RATE_LIMIT,
            'OpenAI error (HTTP 429, type rate_limit).',
        );

        $result = $this->engine($provider)->respondWithContext($this->context(), 'hello dadi');

        self::assertContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        self::assertSame(ConversationIntent::Unclear, $result->intent);
        self::assertNull($result->proposedState);
    }

    public function test_invalid_output_falls_back_gracefully_with_state_intact(): void
    {
        $provider = new FakeAiProvider(new AiResponse(
            content: '{"reply":"ok","understanding":{"sku":"VANRITI-XYZ"}}',
            data: ['reply' => 'ok', 'understanding' => ['sku' => 'VANRITI-XYZ']],
        ));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'kya recommend karogi');

        self::assertContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        self::assertNull($result->proposedState);
    }

    public function test_context_locale_and_bounded_content_reach_the_provider(): void
    {
        $provider = new FakeAiProvider($this->structuredResponse(['reply' => 'Hmm.']));

        $this->engine($provider)->respondWithContext($this->context(), 'aur batao');

        $request = $provider->lastRequest;

        self::assertNotNull($request);
        self::assertSame('hi', $request->locale);
        self::assertTrue($request->jsonMode);
        self::assertStringContainsString('You are Dadi', $request->systemInstructions);
        self::assertStringContainsString('- [customer] Mere baal dry hain.', $request->userMessage);
        self::assertStringContainsString('<customer_message>aur batao</customer_message>', $request->userMessage);
    }

    public function test_sequence_flow_is_derived_from_the_bounded_context(): void
    {
        $provider = new FakeAiProvider($this->structuredResponse([
            'reply' => 'Note kiya.',
            'understanding' => [
                'memory' => [['category' => 'note', 'slot' => 'hair', 'value' => 'dry winter']],
            ],
        ]));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'note kar lo');

        $item = $result->proposedState->memoryItem('note', 'hair');

        self::assertNotNull($item);
        self::assertSame(3, $item->updatedSequence);
    }

    public function test_unexpected_provider_throwable_falls_back_gracefully(): void
    {
        $provider = new FakeAiProvider(new AiResponse('{}', []));
        $provider->exception = new \RuntimeException('boom');

        $result = $this->engine($provider)->respondWithContext($this->context(), 'hello');

        self::assertContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        self::assertNull($result->proposedState);
    }
}
