<?php

namespace App\Dadi\Recommendation;

use App\Dadi\Enums\Concern;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\ProductProfile;
use App\Dadi\ValueObjects\RecommendationCandidate;

/**
 * Deterministic, customer-facing explanation for one recommended product.
 *
 * The explainer turns ONLY authoritative inputs into a short `why` sentence:
 *
 *   - the committed ConversationState (the single Laravel understanding);
 *   - the RecommendationCandidate's own match evidence (this is end-to-end
 *     engine output, never AI chatter), and
 *   - the human-approved ProductProfile intelligence.
 *
 * Output priority is fixed so the explanation always starts from the strongest
 * true reason:
 *
 *   1. matched concerns + matched preferences (both lists, canonical labels);
 *   2. matched concerns alone;
 *   3. matched preferences alone;
 *   4. the approved positioning / approved usage context;
 *   5. a static, safe fallback (a candidate exists, so honest framing only);
 *      null when there is no match evidence at all.
 *
 * It never invents benefits, never mentions commerce data (a profile cannot
 * even carry it — see ProductProfileContentRules) and never repeats internal
 * identifiers. Every string is bounded to `dadi.recommendation.explanation`
 * and returned as plain text; HTML escaping is owned by the render layer.
 */
final class RecommendationExplainer
{
    public const DEFAULT_MAX_LENGTH = 160;

    public const DEFAULT_MAX_CONCERN_LABELS = 2;

    public const DEFAULT_MAX_PREFERENCE_LABELS = 2;

    public const SAFE_FALLBACK = 'Yeh aapke liye chuna gaya hai.';

    /**
     * @param  array<string,mixed>  $settings  dadi.recommendation config slice
     */
    public function __construct(
        private readonly array $settings = [],
    ) {}

    public function explain(
        ConversationState $state,
        RecommendationCandidate $candidate,
        ProductProfile $profile,
    ): ?string {
        $concernLabels = $this->concernLabels($candidate->matchedConcerns);
        $preferenceLabels = $this->preferenceLabels($candidate->matchedPreferences);

        if ($concernLabels !== [] && $preferenceLabels !== []) {
            return $this->bounded(sprintf(
                'Yeh aapke %s aur %s ke liye suitable hai.',
                $this->joinLabels($concernLabels),
                $this->joinLabels($preferenceLabels),
            ));
        }

        if ($concernLabels !== []) {
            return $this->bounded(sprintf(
                'Yeh aapke %s ke liye suitable hai.',
                $this->joinLabels($concernLabels),
            ));
        }

        if ($preferenceLabels !== []) {
            return $this->bounded(sprintf(
                'Yeh aapki %s ke liye suitable hai.',
                $this->joinLabels($preferenceLabels),
            ));
        }

        $approved = $this->approvedText($profile);

        if ($approved !== null) {
            return $approved;
        }

        if ($state->section !== null) {
            return self::SAFE_FALLBACK;
        }

        return null;
    }

    /**
     * Canonical customer-facing labels for the candidate's matched concern
     * codes. Fabricated or unknown codes are inert — they can never leak into
     * an explanation, exactly like the engine that produced them.
     *
     * @param  array<int,string>  $codes
     * @return array<int,string>
     */
    private function concernLabels(array $codes): array
    {
        $labels = [];
        $max = $this->maxConcernLabels();

        foreach ($codes as $code) {
            $concern = Concern::tryFrom((string) $code);

            if ($concern !== null) {
                $labels[] = $concern->label();
            }

            if (count($labels) >= $max) {
                break;
            }
        }

        return $labels;
    }

    /**
     * @param  array<int,string>  $codes
     * @return array<int,string>
     */
    private function preferenceLabels(array $codes): array
    {
        $labels = [];
        $max = $this->maxPreferenceLabels();

        foreach ($codes as $code) {
            $label = $this->preferenceLabel((string) $code);

            if ($label !== null) {
                $labels[] = $label;
            }

            if (count($labels) >= $max) {
                break;
            }
        }

        return $labels;
    }

    /**
     * @param  array<int,string>  $labels
     */
    private function joinLabels(array $labels): string
    {
        if (count($labels) === 1) {
            return "'".$labels[0]."'";
        }

        $last = array_pop($labels);

        return "'".implode("', '", $labels)."' aur '".$last."'";
    }

    /**
     * Priority 4: the approved positioning, falling back to the first approved
     * usage-context entry. Both are human-reviewed and plain-text bounded here.
     */
    private function approvedText(ProductProfile $profile): ?string
    {
        $candidates = [
            (string) $profile->positioning,
            ...$profile->approvedUsageContext,
        ];

        foreach ($candidates as $candidate) {
            $text = $this->cleanApprovedText($candidate);

            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    /**
     * Normalize an approved free-text string into a single bounded plain-text
     * sentence: whitespace is collapsed, control characters are dropped and
     * the length is cut at the configurable cap. Returns null when nothing
     * survives normalization.
     */
    private function cleanApprovedText(string $text): ?string
    {
        $text = preg_replace('/[\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        return $this->bounded($text);
    }

    /**
     * Enforce the hard character cap. When the text is too long it is cut at
     * the nearest word boundary and re-terminated so the customer never sees
     * a truncated word or a dangling template fragment.
     */
    private function bounded(string $text): string
    {
        $max = $this->maxLength();

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > 0) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, ' .,;:!?'.chr(10).chr(13)).'.';
    }

    private function maxLength(): int
    {
        return max(1, (int) ($this->settings['explanation']['max_length'] ?? self::DEFAULT_MAX_LENGTH));
    }

    private function maxConcernLabels(): int
    {
        return max(1, (int) ($this->settings['explanation']['max_concern_labels'] ?? self::DEFAULT_MAX_CONCERN_LABELS));
    }

    private function maxPreferenceLabels(): int
    {
        return max(1, (int) ($this->settings['explanation']['max_preference_labels'] ?? self::DEFAULT_MAX_PREFERENCE_LABELS));
    }

    private function preferenceLabel(string $code): ?string
    {
        $labels = $this->settings['explanation']['preference_labels'] ?? [];

        if (! is_array($labels)) {
            return null;
        }

        $label = $labels[$code] ?? null;

        if (! is_string($label) || trim($label) === '') {
            return null;
        }

        return trim($label);
    }
}
