<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Ai\ConversationalFailureResponder;
use App\Dadi\Ai\DadiConversationEngine;
use App\Dadi\Ai\Prompts\DadiPromptBuilder;
use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Enums\ConversationIntent;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\Safety\DadiSafetyAdjudicator;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use PHPUnit\Framework\TestCase;

final class SafetyFakeAiProvider implements AiProvider
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

class DadiEngineSafetyTest extends TestCase
{
    private function engine(SafetyFakeAiProvider $provider): DadiConversationEngine
    {
        return new DadiConversationEngine(
            promptBuilder: new DadiPromptBuilder,
            provider: $provider,
            validator: new AiOutputValidator,
            failureResponder: new ConversationalFailureResponder,
            safetyAdjudicator: new DadiSafetyAdjudicator,
            guardrail: new SafetyResponseGuardrail,
        );
    }

    private function context(): DadiContext
    {
        return new DadiContext(
            conversationId: 1,
            locale: 'hi',
            recentMessages: [],
            state: new ConversationState,
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

    public function test_emergency_message_replaces_the_ai_reply_with_the_block_response(): void
    {
        $provider = new SafetyFakeAiProvider($this->structuredResponse([
            'reply' => 'Hmm interesting, samajh gayi beta.',
            'intent' => 'concern',
        ]));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'Mujhe saans lene mein dikkat hai.');

        self::assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $result->reply);
        self::assertNull($result->proposedState);
        self::assertSame(SafetyVerdict::Block, $result->safetyAssessment->verdict);
        self::assertSame(['emergency_breathing'], $result->safetyAssessment->reasons);
    }

    public function test_advisory_with_unsafe_ai_reply_is_replaced_but_state_is_kept(): void
    {
        $provider = new SafetyFakeAiProvider($this->structuredResponse([
            'reply' => 'Yeh cream pregnancy mein bhi safe hai.',
            'understanding' => ['attributes' => ['consumer' => 'pregnant']],
        ]));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'Mein pregnant hoon, kya use kar sakti hoon?');

        self::assertSame(SafetyResponseGuardrail::ADVISORY_REPLY, $result->reply);
        self::assertSame(SafetyVerdict::Advisory, $result->safetyAssessment->verdict);
        self::assertNotNull($result->proposedState);
        self::assertSame('pregnant', $result->proposedState->attribute('consumer'));
    }

    public function test_benign_turn_passes_the_validated_ai_reply_through(): void
    {
        $provider = new SafetyFakeAiProvider($this->structuredResponse([
            'reply' => 'Arre beta, baal ki baat karte hain.',
            'intent' => 'concern',
        ]));

        $result = $this->engine($provider)->respondWithContext($this->context(), 'Mere baal dry hain.');

        self::assertSame('Arre beta, baal ki baat karte hain.', $result->reply);
        self::assertSame(ConversationIntent::Concern, $result->intent);
        self::assertSame(SafetyVerdict::Clear, $result->safetyAssessment->verdict);
    }

    public function test_provider_failure_still_routes_an_emergency_to_the_safety_response(): void
    {
        $provider = new SafetyFakeAiProvider(new AiResponse('{}', []));
        $provider->exception = new AiProviderException(
            AiProviderException::CATEGORY_RATE_LIMIT,
            'OpenAI error (HTTP 429, type rate_limit).',
        );

        $result = $this->engine($provider)->respondWithContext($this->context(), 'Galti se double dawai le li.');

        self::assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $result->reply);
        self::assertNotContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        self::assertNull($result->proposedState);
        self::assertSame(SafetyVerdict::Block, $result->safetyAssessment->verdict);
    }

    public function test_provider_failure_with_benign_message_keeps_the_graceful_fallback(): void
    {
        $provider = new SafetyFakeAiProvider(new AiResponse('{}', []));
        $provider->exception = new AiProviderException(
            AiProviderException::CATEGORY_RATE_LIMIT,
            'OpenAI error (HTTP 429, type rate_limit).',
        );

        $result = $this->engine($provider)->respondWithContext($this->context(), 'hello dadi');

        self::assertContains($result->reply, ConversationalFailureResponder::FALLBACKS);
        self::assertSame(SafetyVerdict::Clear, $result->safetyAssessment->verdict);
        self::assertNull($result->proposedState);
    }
}
