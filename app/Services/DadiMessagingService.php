<?php

namespace App\Services;

use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Contracts\ConversationEngine;
use App\Dadi\Enums\RecommendationStatus;
use App\Dadi\Exceptions\DadiOwnershipException;
use App\Dadi\Language\DadiLanguage;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\Recommendation\ConcernRecovery;
use App\Dadi\Recommendation\RecommendationEngine;
use App\Dadi\Recommendation\RecommendationExplainer;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\DadiConversation;
use App\Models\DadiMessage;
use App\Models\DadiRecommendationEvent;
use App\Models\Product;
use App\Models\User;
use App\Services\Dadi\ConversationLockedException;
use App\Services\Dadi\DadiAttributionService;
use App\Services\Dadi\DadiTurnResult;

/**
 * The application orchestrator that maps one customer's message to a complete,
 * Laravel-owned conversational turn.
 *
 * Flow per message:
 *   resolve owned conversation (or start one) -> persist the user message ->
 *   build the bounded context -> engine turn -> commit the proposed state ->
 *   record the safety assessment -> Laravel product recommendation ->
 *   persist the assistant reply -> a safe, UI-only outcome.
 *
 * This class decides nothing on behalf of the domain: state, safety, approval
 * and product truth all stay with their Stage 1-7 owners. Only the small
 * rules that are genuinely UI orchestration live here (which conversation to
 * resume, no new conversation around a safety hold, and which furious fields
 * the front-end may ever see).
 */
class DadiMessagingService
{
    public function __construct(
        private readonly DadiConversationStore $store,
        private readonly ConversationEngine $engine,
        private readonly RecommendationEngine $recommendationEngine,
        private readonly RecommendationExplainer $recommendationExplainer,
        private readonly DadiProductProfileStore $profileStore,
        private readonly DadiAttributionService $dadiAttribution,
        private readonly ConcernRecovery $concernRecovery,
    ) {}

    /**
     * The conversation a returning customer should see on the Dadi page:
     * the latest active one, or the latest owned conversation of any status
     * (for example a safety-held one that must be shown read-only).
     */
    public function currentForCast(?User $user, ?string $sessionId): ?DadiConversation
    {
        return $this->store->latestActive($user, $sessionId)
            ?? $this->latestOfAnyStatus($user, $sessionId);
    }

    /**
     * Record the customer's validated language preference at onboarding, then
     * reveal the conversation. The preference is Laravel-owned state stored on
     * the existing ConversationState; NO greeting message is ever persisted —
     * the first real customer message is the first real conversational turn.
     *
     * A returning guest/auth identity reuses their latest active conversation
     * (its state is updated in place); otherwise a fresh conversation is
     * started in the language's locale hint. A safety-held conversation can
     * never be resumed for this, mirroring the message flow.
     */
    public function onboard(
        ?User $user,
        ?string $sessionId,
        string $preferred,
        ?string $name = null,
    ): DadiConversation {
        $language = DadiLanguage::from($preferred, $name);

        $latest = $this->store->latestActive($user, $sessionId);

        if ($latest instanceof DadiConversation) {
            return $this->store->updateState(
                $latest,
                $latest->state->withPreferredLanguage($language->preferred, $language->name),
            );
        }

        $held = $this->latestOfAnyStatus($user, $sessionId);

        if ($held instanceof DadiConversation && $held->status === DadiConversation::STATUS_SAFETY_HOLD) {
            throw new ConversationLockedException(
                'A safety-held conversation cannot be resumed to record a language preference.',
            );
        }

        return $this->store->start(
            $user,
            $sessionId,
            $language->localeHint(),
            (new ConversationState)->withPreferredLanguage($language->preferred, $language->name),
        );
    }

    /**
     * Complete one turn and produce the UI-safe outcome.
     */
    public function turn(
        ?User $user,
        ?string $sessionId,
        string $message,
        string $locale = 'en',
        ?int $conversationId = null,
    ): DadiTurnResult {
        $conversation = $this->resolveForMessage($user, $sessionId, $locale, $conversationId);

        $this->store->appendMessage($conversation, DadiMessage::ROLE_USER, $message);

        $context = DadiContextBuilder::fromConfig()->forConversation(
            conversationId: (int) $conversation->getKey(),
            user: $user,
            sessionId: $sessionId,
        );

        $turn = $this->engine->respondWithContext($context, $message);
        $assessment = $turn->safetyAssessment ?? SafetyAssessment::clear();

        if ($turn->proposedState instanceof ConversationState) {
            $state = $this->concernRecovery->apply($turn->proposedState, $message);
            $conversation = $this->store->updateState($conversation, $state);
        }

        $conversation = $this->store->applySafetyAssessment($conversation, $assessment);

        $recommendations = [];

        if ($assessment->permitsRecommendation() && $turn->proposedState instanceof ConversationState) {
            $recommendations = $this->cardsForCurrentState($conversation, $assessment);
        }

        $assistantMessage = $this->store->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, $turn->reply, [
            'recommended_product_references' => array_column($recommendations, 'product_reference'),
            'recommendation_explanations' => array_map(
                static fn (array $card): ?string => $card['why'] ?? null,
                $recommendations,
            ),
        ]);

        return new DadiTurnResult(
            conversation: $conversation,
            assistantMessage: $assistantMessage,
            recommendations: $recommendations,
            restricted: ! $assessment->permitsRecommendation(),
        );
    }

    /**
     * The renderable message stream for the Dadi page: recent messages in
     * chronological order, each assistant message carrying the product cards
     * that Laravel recommended for that turn (re-hydrated from the persisted
     * message metadata, never re-computed).
     *
     * @return array<int,array{message:DadiMessage,cards:array<int,array<string,mixed>>}>
     */
    public function renderStream(DadiConversation $conversation): array
    {
        $limit = (int) config('dadi.ui.restored_messages_limit', 50);

        $messages = DadiMessage::query()
            ->where('conversation_id', $conversation->getKey())
            ->orderByDesc('sequence')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        // Batch-load all impression rows for this conversation in one query
        // instead of N individual firstOrCreate calls inside toCard.
        $allProductIds = [];
        foreach ($messages as $message) {
            $refs = $message->metadata['recommended_product_references'] ?? [];
            if (is_array($refs)) {
                $allProductIds = array_merge($allProductIds, $refs);
            }
        }
        $impressionMap = $this->dadiAttribution->impressionsFor($conversation, $allProductIds);

        $items = [];

        foreach ($messages as $message) {
            $items[] = [
                'message' => $message,
                'cards' => $this->cardsForMessage($conversation, $message, $impressionMap),
            ];
        }

        return $items;
    }

    private function resolveForMessage(
        ?User $user,
        ?string $sessionId,
        string $locale,
        ?int $conversationId,
    ): DadiConversation {
        if ($conversationId !== null) {
            $conversation = DadiConversation::query()
                ->ownedBy($user, $sessionId)
                ->whereKey($conversationId)
                ->first();

            if (! $conversation instanceof DadiConversation) {
                throw new DadiOwnershipException(
                    "Conversation [{$conversationId}] is not owned by the given identity.",
                );
            }

            if (! $conversation->isActive()) {
                throw new ConversationLockedException(
                    'Conversation ['.$conversationId.'] cannot accept new messages.',
                );
            }

            return $conversation;
        }

        $latest = $this->store->latestActive($user, $sessionId);

        if ($latest instanceof DadiConversation) {
            return $latest;
        }

        $held = $this->latestOfAnyStatus($user, $sessionId);

        if ($held instanceof DadiConversation && $held->status === DadiConversation::STATUS_SAFETY_HOLD) {
            throw new ConversationLockedException(
                'A safety-held conversation cannot be continued with a fresh one.',
            );
        }

        return $this->store->start($user, $sessionId, $locale);
    }

    private function latestOfAnyStatus(?User $user, ?string $sessionId): ?DadiConversation
    {
        if ($user === null && ($sessionId === null || $sessionId === '')) {
            return null;
        }

        return DadiConversation::query()
            ->ownedBy($user, $sessionId)
            ->orderByDesc('last_activity_at')
            ->first();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function cardsForCurrentState(DadiConversation $conversation, SafetyAssessment $assessment): array
    {
        $result = $this->recommendationEngine->recommend($conversation->state, $assessment);

        if (! $result->hasCandidates() || $result->status !== RecommendationStatus::Available) {
            return [];
        }

        $references = array_map(
            static fn ($candidate): int => $candidate->productReference,
            $result->candidates,
        );

        $products = Product::query()
            ->active()
            ->whereIn('id', $references)
            ->with(['activeVariants', 'variants', 'images'])
            ->get()
            ->keyBy('id');

        // The approved profiles backing the same candidate references. Only
        // ever the store's approved+active view, keyed like the candidates.
        $profilesByReference = [];

        foreach ($this->profileStore->retrieveApproved() as $profile) {
            $profilesByReference[$profile->productReference] = $profile;
        }

        // Batch impressions for turn-time cards (no pre-existing map).
        $impressionMap = $this->dadiAttribution->impressionsFor($conversation, $references);

        $cards = [];

        foreach ($result->candidates as $candidate) {
            $product = $products->get($candidate->productReference);

            if (! $product instanceof Product) {
                continue;
            }

            $profile = $profilesByReference[$candidate->productReference] ?? null;

            $why = $profile !== null
                ? $this->recommendationExplainer->explain($conversation->state, $candidate, $profile)
                : null;

            $cards[] = $this->toCard(
                $product,
                $conversation,
                $impressionMap[$candidate->productReference] ?? null,
                $why,
            );
        }

        return $cards;
    }

    /**
     * Re-hydrate the cards for one persisted assistant message from the
     * Laravel-owned recommendation record written at turn time. The `why`
     * strings are persisted alongside the references so a restored historical
     * message always shows the explanation that was recomputed at its own
     * turn, never the current conversation state's.
     *
     * @return array<int,array<string,mixed>>
     */
    private function cardsForMessage(DadiConversation $conversation, DadiMessage $message, array $impressionMap = []): array
    {
        $stored = $message->metadata['recommended_product_references'] ?? [];

        if (! is_array($stored) || $stored === []) {
            return [];
        }

        $references = array_values(array_unique(array_map('intval', $stored)));

        $explanations = $message->metadata['recommendation_explanations'] ?? [];

        if (! is_array($explanations)) {
            $explanations = [];
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', $references)
            ->with(['activeVariants', 'variants', 'images'])
            ->get()
            ->keyBy('id');

        $cards = [];

        foreach ($references as $index => $reference) {
            $product = $products->get($reference);

            if ($product instanceof Product) {
                $why = $explanations[$index] ?? null;

                $cards[] = $this->toCard(
                    $product,
                    $conversation,
                    $impressionMap[$reference] ?? null,
                    is_string($why) ? $why : null,
                );
            }
        }

        return $cards;
    }

    /**
     * The only product shape the customer UI may receive: identity, name,
     * image, formatted price/currency, live availability, an opaque attribution
     * reference and the existing quick-add action. Commerce never trusts the
     * rendered values: the add button posts to the existing cart.add route,
     * and Laravel re-resolves product, variant, price and stock server-side.
     *
     * Variant handling intentionally mirrors the storefront quick-add card:
     * the first in-stock active variant is used deterministically when one
     * exists, otherwise the product is only viewable (no add button).
     *
     * Scores, matched concerns/preferences, exclusion reasons and SKU copies
     * are deliberately never exposed. The only reasoning surface is `why` —
     * a plain, bounded, deterministic sentence (or null) produced from match
     * evidence and approved intelligence.
     *
     * @return array<string,mixed>
     */
    private function toCard(Product $product, DadiConversation $conversation, ?DadiRecommendationEvent $impression = null, ?string $why = null): array
    {
        $activeVariants = $product->relationLoaded('activeVariants')
            ? $product->activeVariants
            : $product->activeVariants()->get();

        $hasVariants = $activeVariants->isNotEmpty();
        $firstInStock = $activeVariants->first(fn ($variant) => (int) $variant->stock > 0);

        // Replicates Product::isOutOfStock() against the eager-loaded collection
        // instead of issuing an extra query per card: availability counts stock
        // across ALL variants (active or not), exactly like getAvailableStock().
        $allVariants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->get();
        $available = $allVariants->isNotEmpty()
            ? $allVariants->sum(fn ($variant) => (int) $variant->stock) > 0
            : (int) $product->stock > 0;

        $addable = $available && (! $hasVariants || $firstInStock !== null);

        // Replicates Product::getPrimaryImage() (type 'main', then first by
        // sort_order) against the eager-loaded images collection.
        $images = $product->relationLoaded('images')
            ? $product->images
            : $product->images()->get();
        $primaryImage = $images->firstWhere('type', 'main') ?? $images->first();

        $effectiveImpression = $impression
            ?? $this->dadiAttribution->impressionFor($conversation, $product);

        return [
            'product_reference' => (int) $product->getKey(),
            'name' => (string) $product->name,
            'image_url' => image_url($primaryImage?->image_path),
            'price' => (float) $product->selling_price,
            'price_formatted' => format_price($product->selling_price),
            'mrp' => (float) $product->mrp,
            'mrp_formatted' => format_price($product->mrp),
            'available' => $available,
            'product_url' => route('product.show', $product),
            'reference' => (string) $effectiveImpression->reference,
            'add_url' => $addable ? route('cart.add', $product) : null,
            'variant_id' => $firstInStock?->id,
            'addable' => $addable,
            'why' => $why,
        ];
    }
}
