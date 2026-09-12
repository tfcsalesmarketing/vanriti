<?php

namespace App\Dadi\Ai;

use App\Dadi\Ai\Prompts\DadiPromptBuilder;
use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Contracts\ConversationEngine;
use App\Dadi\Contracts\SafetyAdjudicator;
use App\Dadi\Exceptions\AiProviderException;
use App\Dadi\Exceptions\InvalidAiOutputException;
use App\Dadi\Safety\DadiSafetyAdjudicator;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\DadiContext;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * The real Dadi conversation engine: pairs a customer message with the bounded
 * DadiContext, produces one AiTurnResult through a pluggable AiProvider, then
 * routes it through the Laravel-owned safety layer.
 *
 * Flow: message → context → prompt → provider → Laravel validation → safety
 * adjudication → response guardrail → AiTurnResult.
 *
 * The engine never persists anything and never touches products or commerce.
 * On any provider/validation failure it returns a graceful fallback turn with
 * an untouched proposed state — which still passes through the safety layer,
 * so an emergency message gets a safety response instead of a technical
 * apology. This class knows nothing about OpenAI or any specific provider.
 */
final class DadiConversationEngine implements ConversationEngine
{
    private readonly SafetyAdjudicator $safetyAdjudicator;

    private readonly SafetyResponseGuardrail $guardrail;

    public function __construct(
        private readonly DadiPromptBuilder $promptBuilder,
        private readonly AiProvider $provider,
        private readonly AiOutputValidator $validator,
        private readonly ConversationalFailureResponder $failureResponder,
        ?SafetyAdjudicator $safetyAdjudicator = null,
        ?SafetyResponseGuardrail $guardrail = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->safetyAdjudicator = $safetyAdjudicator ?? new DadiSafetyAdjudicator;
        $this->guardrail = $guardrail ?? new SafetyResponseGuardrail;
    }

    public function respond(ConversationState $state, string $message): AiTurnResult
    {
        $context = new DadiContext(
            conversationId: 0,
            locale: (string) config('dadi.locale', 'en'),
            recentMessages: [],
            state: $state,
            historicalMemory: [],
        );

        return $this->respondWithContext($context, $message);
    }

    public function respondWithContext(DadiContext $context, string $message): AiTurnResult
    {
        $request = $this->promptBuilder->build($context, $message);
        $sequence = $this->nextSequence($context);

        try {
            $candidate = $this->validator->validate(
                $this->provider->generate($request),
                $context->state,
                $sequence,
            );
        } catch (AiProviderException $e) {
            $this->log('warning', 'Dadi: AI provider failure', [
                'conversation' => $context->conversationId,
                'category' => $e->category,
                'error' => substr($e->getMessage(), 0, 300),
            ]);

            $candidate = $this->failureResponder->respond($message);
        } catch (InvalidAiOutputException $e) {
            $this->log('warning', 'Dadi: AI output failed validation', [
                'conversation' => $context->conversationId,
                'error' => substr($e->getMessage(), 0, 300),
            ]);

            $candidate = $this->failureResponder->respond($message);
        } catch (\Throwable $e) {
            $this->log('error', 'Dadi: unexpected conversation engine failure', [
                'conversation' => $context->conversationId,
                'error' => substr($e->getMessage(), 0, 300),
            ]);

            $candidate = $this->failureResponder->respond($message);
        }

        return $this->assessAndGuard($candidate, $context, $message);
    }

    private function assessAndGuard(AiTurnResult $candidate, DadiContext $context, string $message): AiTurnResult
    {
        $assessment = $this->safetyAdjudicator->adjudicate(
            state: $context->state,
            message: $message,
            aiSafetyFlag: $candidate->requiresSafetyReview,
            context: $context,
        );

        return $this->guardrail->apply($candidate, $assessment);
    }

    private function log(string $level, string $message, array $context): void
    {
        $logger = $this->logger;

        if ($logger === null) {
            try {
                $logger = Log::getFacadeRoot();
            } catch (\Throwable) {
                $logger = null;
            }
        }

        $logger?->log($level, $message, $context);
    }

    /**
     * The sequence the incoming customer message will occupy: one past the
     * newest bounded message in the context.
     */
    private function nextSequence(DadiContext $context): int
    {
        $last = $context->recentMessages[count($context->recentMessages) - 1] ?? null;

        return ($last !== null ? $last->sequence : 0) + 1;
    }
}
