<?php

namespace App\Dadi\Recommendation;

use App\Dadi\Enums\Concern;
use App\Dadi\Enums\RecommendationStatus;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\ProductProfile;
use App\Dadi\ValueObjects\RecommendationCandidate;
use App\Dadi\ValueObjects\RecommendationResult;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\Product;

/**
 * Laravel-owned, fully deterministic recommendation engine.
 *
 * The engine never asks the AI to choose a product, never hard-codes a SKU or
 * product name, and never writes into the conversation state. It consumes only
 * authoritative inputs:
 *
 *   - the safety assessment      (a hard gate; advisory restricts)
 *   - the conversation state     (section, concerns, preferences, avoidances)
 *   - approved product intelligence   (held by the DadiProductProfileStore)
 *   - the live catalogue         (products + aggregate stock, resolved here)
 *
 * Candidates are approved+active+in-stock profiles ranked by a transparent
 * weighted score. Availability is always the live catalogue truth, read at
 * recommendation time — approval of intelligence never implies purchasability.
 *
 * Preference/avoidance matching reads the human-approved intelligence text
 * through a small, centralized lexicon; only positively asserted traits count,
 * and an explicitly avoided trait that the approved text positively asserts is
 * a hard exclusion. Absence never implies anything.
 */
final class RecommendationEngine
{
    public const DEFAULT_MAX_CANDIDATES = 3;

    public const DEFAULT_SECTION_WEIGHT = 20;

    public const DEFAULT_CONCERN_WEIGHT = 25;

    public const DEFAULT_PREFERENCE_WEIGHT = 10;

    /**
     * @param  array<string,mixed>  $settings  dadi.recommendation config slice
     */
    public function __construct(
        private readonly DadiProductProfileStore $store,
        private readonly ApprovedTextMatcher $matcher,
        private readonly array $settings = [],
    ) {}

    public function recommend(ConversationState $state, SafetyAssessment $safety): RecommendationResult
    {
        if ($safety->verdict === SafetyVerdict::Block) {
            return new RecommendationResult(
                RecommendationStatus::SafetyBlocked,
                reasons: $safety->reasons,
            );
        }

        if ($safety->verdict === SafetyVerdict::Advisory) {
            return new RecommendationResult(
                RecommendationStatus::AdvisoryRestricted,
                reasons: $safety->reasons,
            );
        }

        $profiles = $this->store->retrieveApproved();

        if ($profiles === []) {
            return new RecommendationResult(RecommendationStatus::NoMatch);
        }

        $products = $this->availableProducts($profiles);

        $matched = [];
        $matchedUnavailable = 0;

        foreach ($profiles as $profile) {
            $product = $products[$profile->productReference] ?? null;

            if ($product === null) {
                continue;
            }

            $exclusion = $this->exclusionReason($state, $profile);

            if ($exclusion !== null) {
                continue;
            }

            $matchedConcerns = $this->matchedConcerns($state, $profile);
            $matchedPreferences = $this->matchedPreferences($state, $profile);

            if (! $this->hasAnyOverlap($state, $profile, $matchedConcerns, $matchedPreferences)) {
                continue;
            }

            if ($product->isOutOfStock()) {
                $matchedUnavailable++;

                continue;
            }

            $matched[] = new RecommendationCandidate(
                productReference: $profile->productReference,
                score: $this->score($state, $profile, $matchedConcerns, $matchedPreferences),
                matchedConcerns: $matchedConcerns,
                matchedPreferences: $matchedPreferences,
            );
        }

        if ($matched === []) {
            return new RecommendationResult(
                $matchedUnavailable > 0 ? RecommendationStatus::Unavailable : RecommendationStatus::NoMatch,
            );
        }

        return new RecommendationResult(
            RecommendationStatus::Available,
            array_slice($this->rankingOf($matched), 0, $this->maxCandidates()),
        );
    }

    /**
     * Live catalogue truth for the profiles' product references. Approved
     * intelligence matching is never a licence to recommend an inactive
     * product; the store already guarantees the active gate, and this map also
     * drops any reference that no longer resolves to a live product.
     *
     * @param  array<int,ProductProfile>  $profiles
     * @return array<int,Product>
     */
    private function availableProducts(array $profiles): array
    {
        $references = array_values(array_unique(array_map(
            static fn (ProductProfile $profile): int => $profile->productReference,
            $profiles,
        )));

        return Product::query()
            ->with('variants')
            ->whereIn('id', $references)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * A profile is only an eligible match when the customer's current
     * understanding overlaps it somewhere real — a section, a matched concern,
     * or a positively asserted preference. Zero-overlap profiles are not
     * matches at all: they can never become candidates, and they do not count
     * towards the "unavailable" status either.
     *
     * @param  array<int,string>  $matchedConcerns
     * @param  array<int,string>  $matchedPreferences
     */
    private function hasAnyOverlap(
        ConversationState $state,
        ProductProfile $profile,
        array $matchedConcerns,
        array $matchedPreferences,
    ): bool {
        return ($state->section !== null && $profile->hasSection($state->section))
            || $matchedConcerns !== []
            || $matchedPreferences !== [];
    }

    /**
     * @param  array<int,string>  $matchedConcerns
     * @param  array<int,string>  $matchedPreferences
     */
    private function score(
        ConversationState $state,
        ProductProfile $profile,
        array $matchedConcerns,
        array $matchedPreferences,
    ): int {
        $score = 0;

        if ($state->section !== null && $profile->hasSection($state->section)) {
            $score += $this->sectionWeight();
        }

        $score += count($matchedConcerns) * $this->concernWeight();
        $score += count($matchedPreferences) * $this->preferenceWeight();

        return $score;
    }

    /**
     * State concerns matched against the approved profile. Only closed,
     * existing concern codes take part; an unknown or fabricated token is
     * inert and can never fabricate a match.
     *
     * @return array<int,string>
     */
    private function matchedConcerns(ConversationState $state, ProductProfile $profile): array
    {
        $matched = [];

        foreach ($state->concerns as $code) {
            if (in_array($code, $matched, true)) {
                continue;
            }

            $concern = Concern::tryFrom($code);

            if ($concern !== null && $profile->hasConcern($concern)) {
                $matched[] = $code;
            }
        }

        return $matched;
    }

    /**
     * Preferences from the conversation that the approved intelligence text
     * positively asserts. Entries outside the centralized lexicon are neutral
     * — they neither strengthen nor exclude a candidate.
     *
     * @return array<int,string>
     */
    private function matchedPreferences(ConversationState $state, ProductProfile $profile): array
    {
        $codes = array_keys($state->preferences);
        $matched = [];

        foreach ($codes as $code) {
            $tokens = $this->preferenceLexicon()[$code] ?? null;

            if ($tokens === null || in_array($code, $matched, true)) {
                continue;
            }

            if ($this->textAssertsTrait($profile, $tokens)) {
                $matched[] = $code;
            }
        }

        return $matched;
    }

    /**
     * Hard exclusion: the customer explicitly avoids a trait (memory item with
     * category "avoidance") and the approved intelligence text positively
     * asserts that trait. Absence is never treated as a conflict, and an
     * avoidance code nobody declared is ignored.
     */
    private function exclusionReason(ConversationState $state, ProductProfile $profile): ?string
    {
        foreach ($this->customerAvoidances($state) as $code) {
            $tokens = $this->avoidanceLexicon()[$code] ?? null;

            if ($tokens === null) {
                continue;
            }

            if ($this->textAssertsTrait($profile, $tokens)) {
                return "The customer avoids \"{$code}\" and this product's approved intelligence positively asserts it.";
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function customerAvoidances(ConversationState $state): array
    {
        $codes = [];

        foreach ($state->memoryActive() as $item) {
            if ($item->category !== 'avoidance') {
                continue;
            }

            $code = mb_strtolower(trim($item->slot));

            if ($code !== '' && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * @param  array<int,string>  $tokens
     */
    private function textAssertsTrait(ProductProfile $profile, array $tokens): bool
    {
        $text = implode(' ', $this->profileTextParts($profile));

        foreach ($tokens as $token) {
            if ($this->matcher->asserts($text, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The authoritative, human-approved free-text surface of the profile.
     *
     * @return array<int,string>
     */
    private function profileTextParts(ProductProfile $profile): array
    {
        return [
            $profile->positioning,
            ...$profile->approvedBenefits,
            ...$profile->approvedUsageContext,
            ...$profile->approvedPrecautions,
            ...$profile->suitabilityNotes,
        ];
    }

    /**
     * @param  array<int,RecommendationCandidate>  $candidates
     * @return array<int,RecommendationCandidate>
     */
    private function rankingOf(array $candidates): array
    {
        usort(
            $candidates,
            static fn (RecommendationCandidate $a, RecommendationCandidate $b): int => $b->score <=> $a->score
                ?: count($b->matchedConcerns) <=> count($a->matchedConcerns)
                ?: count($b->matchedPreferences) <=> count($a->matchedPreferences)
                ?: $a->productReference <=> $b->productReference,
        );

        return $candidates;
    }

    private function maxCandidates(): int
    {
        return (int) ($this->settings['max_candidates'] ?? self::DEFAULT_MAX_CANDIDATES);
    }

    private function sectionWeight(): int
    {
        return (int) ($this->settings['weights']['section_match'] ?? self::DEFAULT_SECTION_WEIGHT);
    }

    private function concernWeight(): int
    {
        return (int) ($this->settings['weights']['concern_match'] ?? self::DEFAULT_CONCERN_WEIGHT);
    }

    private function preferenceWeight(): int
    {
        return (int) ($this->settings['weights']['preference_match'] ?? self::DEFAULT_PREFERENCE_WEIGHT);
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function preferenceLexicon(): array
    {
        return $this->settings['preferences'] ?? [];
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function avoidanceLexicon(): array
    {
        return $this->settings['avoidances'] ?? [];
    }
}
