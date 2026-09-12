<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Contracts\ConversationEngine;
use App\Dadi\Contracts\SafetyAdjudicator;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\ConversationState;
use App\Models\DadiConversation;
use App\Models\DadiMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

class SafetyHoldTest extends TestCase
{
    use RefreshDatabase;

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    private function adjudicator(): SafetyAdjudicator
    {
        return $this->app->make(SafetyAdjudicator::class);
    }

    private function structuredResponse(array $data): AiResponse
    {
        return new AiResponse(
            content: json_encode($data, JSON_THROW_ON_ERROR),
            data: $data,
        );
    }

    public function test_block_applies_safety_hold_and_records_signal_codes(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $message = 'Mujhe saans lene mein dikkat ho rahi hai.';
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, $message);

        $assessment = $this->adjudicator()->adjudicate($conversation->state, $message);

        $this->store()->applySafetyAssessment($conversation, $assessment);

        $reloaded = $conversation->fresh();

        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $reloaded->status);
        $this->assertSame(['emergency_breathing'], $reloaded->state->safetySignals);
        $this->assertTrue($assessment->blocksRecommendation());
    }

    public function test_hold_is_authoritative_across_later_benign_turns(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');
        $this->store()->applySafetyAssessment(
            $conversation,
            $this->adjudicator()->adjudicate(new ConversationState, 'Mujhe chest mein dard hai.'),
        );

        $benign = $this->adjudicator()->adjudicate(
            $conversation->fresh()->state,
            'Dhanyavaad Dadi',
            false,
        );

        $this->assertSame(SafetyVerdict::Block, $benign->verdict);
        $this->assertSame(['emergency_chest_pain'], $benign->reasons);

        $this->store()->applySafetyAssessment($conversation, $benign);

        $reloaded = $conversation->fresh();
        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $reloaded->status);
        $this->assertSame(['emergency_chest_pain'], $reloaded->state->safetySignals);
    }

    public function test_ai_flag_cannot_install_or_clear_a_block(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');

        $aiFlaggedBenign = $this->adjudicator()->adjudicate(new ConversationState, 'Namaste Dadi', true);
        $this->assertSame(SafetyVerdict::Clear, $aiFlaggedBenign->verdict);

        $this->store()->applySafetyAssessment(
            $conversation,
            $this->adjudicator()->adjudicate(new ConversationState, 'Mujhe behosh jaisa ho gaya.'),
        );

        $clearAttempt = $this->adjudicator()->adjudicate(
            $conversation->fresh()->state,
            'Ab main bilkul theek hoon',
            false,
        );

        $this->assertSame(SafetyVerdict::Block, $clearAttempt->verdict);
        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $conversation->fresh()->status);
    }

    public function test_advisory_signals_persist_without_a_hold(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');

        $assessment = $this->adjudicator()->adjudicate(
            $conversation->state,
            'Mein pregnant hoon, kya use kar sakti hoon?',
        );

        $this->assertSame(SafetyVerdict::Advisory, $assessment->verdict);

        $this->store()->applySafetyAssessment($conversation, $assessment);

        $reloaded = $conversation->fresh();
        $this->assertSame(DadiConversation::STATUS_ACTIVE, $reloaded->status);
        $this->assertSame(['pregnancy'], $reloaded->state->safetySignals);

        $followUp = $this->adjudicator()->adjudicate($reloaded->state, 'Dhanyavaad Dadi');

        $this->assertSame(SafetyVerdict::Advisory, $followUp->verdict);
        $this->assertSame(['pregnancy'], $followUp->reasons);
    }

    public function test_engine_responds_with_a_safety_response_for_an_emergency_message(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user, locale: 'hi');

        $fake = new SafetyFakeAiProvider($this->structuredResponse([
            'reply' => 'Hmm, samajh gayi beta.',
            'intent' => 'concern',
        ]));
        $this->app->instance(AiProvider::class, $fake);

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);
        $turn = $this->app->make(ConversationEngine::class)
            ->respondWithContext($context, 'Mujhe saans lene mein takleef hai.');

        $this->assertSame(SafetyResponseGuardrail::BLOCK_REPLY, $turn->reply);
        $this->assertNull($turn->proposedState);
        $this->assertSame(SafetyVerdict::Block, $turn->safetyAssessment->verdict);
        $this->assertSame(['emergency_breathing'], $turn->safetyAssessment->reasons);

        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, $turn->reply);
        $this->store()->applySafetyAssessment($conversation, $turn->safetyAssessment);

        $reloaded = $conversation->fresh();
        $this->assertSame(DadiConversation::STATUS_SAFETY_HOLD, $reloaded->status);
        $this->assertSame(['emergency_breathing'], $reloaded->state->safetySignals);
        $this->assertSame(
            SafetyResponseGuardrail::BLOCK_REPLY,
            $reloaded->messages->last()->content,
        );
    }

    public function test_no_product_logic_leaks_into_the_safety_layer(): void
    {
        $adjudicator = $this->adjudicator();

        $assessment = $adjudicator->adjudicate(
            new ConversationState,
            'Mujhe isi brand ka shampoo chahiye, price kya hai?',
        );

        $this->assertSame(SafetyVerdict::Clear, $assessment->verdict);
        $this->assertSame([], $assessment->reasons);
    }
}
