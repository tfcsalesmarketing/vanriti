<?php

namespace App\Providers;

use App\Dadi\Ai\ConversationalFailureResponder;
use App\Dadi\Ai\DadiConversationEngine;
use App\Dadi\Ai\Prompts\DadiPromptBuilder;
use App\Dadi\Ai\Providers\OpenAiProvider;
use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Contracts\AiProvider;
use App\Dadi\Contracts\ConversationEngine;
use App\Dadi\Contracts\SafetyAdjudicator;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\Recommendation\ApprovedTextMatcher;
use App\Dadi\Recommendation\ConcernRecovery;
use App\Dadi\Recommendation\RecommendationEngine;
use App\Dadi\Recommendation\RecommendationExplainer;
use App\Dadi\Safety\DadiSafetyAdjudicator;
use App\Dadi\Safety\SafetyResponseGuardrail;
use App\Services\ActivityLogger;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the Dadi AI layer. The engine depends only on the AiProvider contract,
 * so the OpenAI implementation can be swapped for Gemini/Anthropic later by
 * changing this single binding. All values come from configuration; no secret
 * is ever hardcoded here.
 */
class DadiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiProvider::class, function (): AiProvider {
            $organization = config('services.openai.organization');

            return new OpenAiProvider(
                baseUrl: (string) (config('services.openai.base_url', 'https://api.openai.com/v1')),
                apiKey: (string) (config('services.openai.key') ?? ''),
                organization: is_string($organization) && $organization !== '' ? $organization : null,
                model: (string) config('dadi.ai.model', 'gpt-4o-mini'),
                timeout: (int) config('dadi.ai.timeout', 15),
                connectTimeout: (int) config('dadi.ai.connect_timeout', 5),
                maxOutputTokens: (int) config('dadi.ai.max_output_tokens', 900),
                temperature: (float) config('dadi.ai.temperature', 0.8),
                jsonMode: (bool) config('dadi.ai.json_mode', true),
            );
        });

        $this->app->bind(DadiPromptBuilder::class, fn (): DadiPromptBuilder => new DadiPromptBuilder(
            jsonMode: (bool) config('dadi.ai.json_mode', true),
        ));

        $this->app->bind(AiOutputValidator::class, fn (): AiOutputValidator => new AiOutputValidator(
            maxReplyLength: (int) config('dadi.ai.max_reply_length', 600),
        ));

        $this->app->bind(DadiContextBuilder::class, fn (): DadiContextBuilder => DadiContextBuilder::fromConfig());

        $this->app->bind(SafetyAdjudicator::class, fn (): SafetyAdjudicator => new DadiSafetyAdjudicator);

        $this->app->bind(SafetyResponseGuardrail::class, fn (): SafetyResponseGuardrail => new SafetyResponseGuardrail);

        $this->app->bind(DadiProductProfileStore::class, fn (): DadiProductProfileStore => new DadiProductProfileStore(
            logger: $this->app->make(ActivityLogger::class),
            limits: (array) config('dadi.product_intelligence', []),
        ));

        $this->app->bind(RecommendationEngine::class, fn (): RecommendationEngine => new RecommendationEngine(
            store: $this->app->make(DadiProductProfileStore::class),
            matcher: new ApprovedTextMatcher,
            settings: (array) config('dadi.recommendation', []),
        ));

        $this->app->bind(ConcernRecovery::class, fn (): ConcernRecovery => new ConcernRecovery(
            settings: (array) config('dadi.recovery', []),
        ));

        $this->app->bind(RecommendationExplainer::class, fn (): RecommendationExplainer => new RecommendationExplainer(
            settings: (array) config('dadi.recommendation', []),
        ));

        $this->app->bind(ConversationEngine::class, function (): ConversationEngine {
            return new DadiConversationEngine(
                promptBuilder: $this->app->make(DadiPromptBuilder::class),
                provider: $this->app->make(AiProvider::class),
                validator: $this->app->make(AiOutputValidator::class),
                failureResponder: new ConversationalFailureResponder,
                safetyAdjudicator: $this->app->make(SafetyAdjudicator::class),
                guardrail: $this->app->make(SafetyResponseGuardrail::class),
            );
        });
    }
}
