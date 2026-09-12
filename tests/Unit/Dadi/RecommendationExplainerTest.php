<?php

namespace Tests\Unit\Dadi;

use App\Dadi\Recommendation\RecommendationExplainer;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\ProductProfile;
use App\Dadi\ValueObjects\RecommendationCandidate;
use Tests\TestCase;

class RecommendationExplainerTest extends TestCase
{
    private function explainer(array $overrides = []): RecommendationExplainer
    {
        return new RecommendationExplainer(
            settings: array_replace_recursive(config('dadi.recommendation'), $overrides),
        );
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function candidate(array $overrides = []): RecommendationCandidate
    {
        return new RecommendationCandidate(
            productReference: 7,
            score: 45,
            matchedConcerns: $overrides['matchedConcerns'] ?? [],
            matchedPreferences: $overrides['matchedPreferences'] ?? [],
        );
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function state(array $overrides = []): ConversationState
    {
        return ConversationState::fromArray(array_merge([
            'section' => 'hair',
            'concerns' => [],
            'preferences' => [],
            'attributes' => [],
            'memory' => [],
        ], $overrides));
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function profile(array $overrides = []): ProductProfile
    {
        return new ProductProfile(
            productReference: 7,
            name: 'Test Tonic',
            sections: $overrides['sections'] ?? ['hair'],
            concerns: $overrides['concerns'] ?? ['hair_dryness'],
            positioning: $overrides['positioning'] ?? null,
            approvedBenefits: $overrides['approvedBenefits'] ?? [],
            approvedUsageContext: $overrides['approvedUsageContext'] ?? [],
        );
    }

    public function test_concern_only_explanation_uses_canonical_concern_labels(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(['matchedConcerns' => ['hair_dryness']]),
            $this->profile(),
        );

        self::assertSame("Yeh aapke 'Dry hair' ke liye suitable hai.", $why);
    }

    public function test_preference_only_explanation_uses_canonical_preference_labels(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(['matchedPreferences' => ['herbal']]),
            $this->profile(),
        );

        self::assertSame("Yeh aapki 'herbal' ke liye suitable hai.", $why);
    }

    public function test_combined_explanation_lists_concerns_before_preferences(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate([
                'matchedConcerns' => ['hair_dryness'],
                'matchedPreferences' => ['herbal', 'gentle'],
            ]),
            $this->profile(),
        );

        self::assertStringStartsWith("Yeh aapke 'Dry hair' aur 'herbal'", $why);
        self::assertStringContainsString("'herbal' aur 'gentle and mild'", $why);
        self::assertStringEndsWith('suitable hai.', $why);
    }

    public function test_fabricated_or_unknown_concern_codes_are_inert(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate([
                'matchedConcerns' => ['cures_everything', 'hair_frizz'],
            ]),
            $this->profile(['positioning' => 'Approved gentle positioning.']),
        );

        self::assertSame("Yeh aapke 'Frizzy hair' ke liye suitable hai.", $why);
    }

    public function test_lonely_fabricated_concern_code_falls_back_to_approved_text(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(['matchedConcerns' => ['cures_everything']]),
            $this->profile(['positioning' => 'Approved gentle positioning.']),
        );

        self::assertSame('Approved gentle positioning.', $why);
    }

    public function test_preference_code_without_a_canonical_label_is_inert(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(['matchedPreferences' => ['wash_frequency']]),
            $this->profile(['positioning' => 'Approved gentle positioning.']),
        );

        self::assertSame('Approved gentle positioning.', $why);
    }

    public function test_section_only_candidate_uses_approved_positioning(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => 'Gentle daily care.']),
        );

        self::assertSame('Gentle daily care.', $why);
    }

    public function test_approved_usage_context_is_used_when_positioning_is_absent(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile([
                'positioning' => null,
                'approvedUsageContext' => ['Apply daily on damp lengths for softness.'],
            ]),
        );

        self::assertSame('Apply daily on damp lengths for softness.', $why);
    }

    public function test_section_only_candidate_without_approved_text_uses_the_safe_fallback(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => null]),
        );

        self::assertSame(RecommendationExplainer::SAFE_FALLBACK, $why);
    }

    public function test_no_match_evidence_at_all_returns_null(): void
    {
        $why = $this->explainer()->explain(
            $this->state(['section' => null]),
            $this->candidate(),
            $this->profile(['positioning' => null]),
        );

        self::assertNull($why);
    }

    public function test_safe_fallback_only_appears_when_a_candidate_reason_exists(): void
    {
        self::assertSame(RecommendationExplainer::SAFE_FALLBACK, $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => null, 'approvedUsageContext' => []]),
        ));
    }

    public function test_label_counts_are_bounded_by_the_config_caps(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate([
                'matchedConcerns' => ['hair_dryness', 'hair_frizz', 'dandruff'],
                'matchedPreferences' => ['herbal', 'gentle', 'lightweight'],
            ]),
            $this->profile(),
        );

        self::assertStringContainsString("'Dry hair' aur 'Frizzy hair'", $why);
        self::assertStringContainsString("'herbal' aur 'gentle and mild'", $why);
        self::assertStringNotContainsString('Dandruff', $why);
        self::assertStringNotContainsString('lightweight', $why);
    }

    public function test_evidence_sentences_never_exceed_the_max_length(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate([
                'matchedConcerns' => ['hair_dryness', 'hair_frizz'],
                'matchedPreferences' => ['herbal', 'gentle'],
            ]),
            $this->profile(),
        );

        self::assertLessThanOrEqual((int) config('dadi.recommendation.explanation.max_length'), mb_strlen($why));
    }

    public function test_long_approved_text_is_bounded_to_the_config_cap_and_reterminated(): void
    {
        $long = 'Daily care oil crafted from traditional herbs '.str_repeat('and gentle botanicals ', 12);

        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => $long]),
        );

        self::assertNotNull($why);
        self::assertLessThanOrEqual((int) config('dadi.recommendation.explanation.max_length'), mb_strlen($why));
        self::assertStringEndsWith('.', $why);
        self::assertNotSame($long, $why);
        self::assertStringStartsWith('Daily care oil crafted from traditional herbs', $why);
    }

    public function test_multi_line_positioning_is_collapsed_to_a_single_line(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => "Gentle daily care.\nAaram se use karein."]),
        );

        self::assertSame('Gentle daily care. Aaram se use karein.', $why);
    }

    public function test_explanation_never_leaks_commerce_or_internal_identifiers(): void
    {
        $explainer = $this->explainer();

        $outputs = [
            $explainer->explain($this->state(), $this->candidate(['matchedConcerns' => ['hair_dryness']]), $this->profile()),
            $explainer->explain($this->state(), $this->candidate(['matchedPreferences' => ['herbal']]), $this->profile()),
            $explainer->explain($this->state(), $this->candidate(), $this->profile(['positioning' => 'Gentle daily care.'])),
        ];

        foreach ($outputs as $output) {
            self::assertNotNull($output);

            foreach (['sku', 'price', 'mrp', 'stock', 'selling_price', 'reasons', 'score'] as $forbidden) {
                self::assertStringNotContainsStringIgnoringCase($forbidden, $output);
            }
        }
    }

    public function test_explanation_is_deterministic_across_repeated_calls(): void
    {
        $explainer = $this->explainer();

        $first = $explainer->explain(
            $this->state(),
            $this->candidate(['matchedConcerns' => ['hair_dryness'], 'matchedPreferences' => ['herbal']]),
            $this->profile(),
        );

        $second = $explainer->explain(
            $this->state(),
            $this->candidate(['matchedConcerns' => ['hair_dryness'], 'matchedPreferences' => ['herbal']]),
            $this->profile(),
        );

        self::assertSame($first, $second);
    }

    public function test_approved_text_is_emitted_as_plain_data_not_rendered_html(): void
    {
        $why = $this->explainer()->explain(
            $this->state(),
            $this->candidate(),
            $this->profile(['positioning' => '<script>alert(1)</script>']),
        );

        self::assertSame('<script>alert(1)</script>', $why);
    }
}
