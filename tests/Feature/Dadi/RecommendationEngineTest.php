<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Enums\RecommendationStatus;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\Recommendation\ApprovedTextMatcher;
use App\Dadi\Recommendation\RecommendationEngine;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\RecommendationResult;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\Admin;
use App\Models\DadiProductProfile;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    private function reviewer(): Admin
    {
        $permission = Permission::firstOrCreate(
            ['slug' => DadiProductProfileStore::REVIEW_PERMISSION],
            ['name' => 'Review dadi product profiles'],
        );

        $role = Role::create(['slug' => 'dadi-reviewer-'.Str::random(6), 'name' => 'Dadi Reviewer']);
        $role->permissions()->attach($permission);

        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $admin->roles()->attach($role);

        return $admin;
    }

    private function store(): DadiProductProfileStore
    {
        return $this->app->make(DadiProductProfileStore::class);
    }

    private function engine(): RecommendationEngine
    {
        return $this->app->make(RecommendationEngine::class);
    }

    /**
     * @return array<string,mixed>
     */
    private function content(array $overrides = []): array
    {
        return array_merge([
            'sections' => ['hair'],
            'concerns' => ['hair_dryness'],
            'positioning' => 'Gentle daily care.',
            'approved_benefits' => ['Nourishes dry lengths'],
            'approved_usage_context' => ['Apply to damp lengths'],
            'approved_precautions' => ['Avoid contact with eyes'],
            'suitability_notes' => ['Consider for dry hair'],
        ], $overrides);
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function approvedProduct(array $overrides = []): Product
    {
        return Product::factory()->create($overrides);
    }

    private function approveProfile(DadiProductProfile $profile, Admin $reviewer): DadiProductProfile
    {
        $this->store()->submitForReview($profile, $reviewer);
        $this->store()->approve($profile, $reviewer);

        return $profile->fresh();
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function madeProfile(Product $product, array $contentOverrides = []): DadiProductProfile
    {
        return $this->approveProfile(
            $this->store()->create($product, $this->content($contentOverrides), $this->reviewer()),
            $this->reviewer(),
        );
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function state(array $overrides = []): ConversationState
    {
        return ConversationState::fromArray(array_merge([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'preferences' => [],
            'attributes' => [],
            'memory' => [],
        ], $overrides));
    }

    private function recommend(ConversationState $state): RecommendationResult
    {
        return $this->engine()->recommend($state, SafetyAssessment::clear());
    }

    /* ------------------------------------------------------------------ */
    /* Eligibility: approval + product truth */
    /* ------------------------------------------------------------------ */

    public function test_approved_active_in_stock_profile_recommends(): void
    {
        $product = $this->approvedProduct();

        $this->madeProfile($product);

        $result = $this->recommend($this->state());

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertCount(1, $result->candidates);
        self::assertSame($product->id, $result->candidates[0]->productReference);
    }

    public function test_draft_pending_and_rejected_profiles_never_recommend(): void
    {
        $product = $this->approvedProduct();
        $profile = $this->store()->create($product, $this->content(), $this->reviewer());

        self::assertSame(RecommendationStatus::NoMatch, $this->recommend($this->state())->status);

        $this->store()->submitForReview($profile, $this->reviewer());
        self::assertSame(RecommendationStatus::NoMatch, $this->recommend($this->state())->status);

        $this->store()->reject($profile->fresh(), $this->reviewer());
        self::assertSame(RecommendationStatus::NoMatch, $this->recommend($this->state())->status);
    }

    public function test_inactive_product_never_recommends_even_when_approved(): void
    {
        $this->madeProfile($this->approvedProduct(['status' => 'inactive']));

        self::assertSame(RecommendationStatus::NoMatch, $this->recommend($this->state())->status);
    }

    public function test_soft_deleted_product_never_recommends(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product);

        $product->delete();

        self::assertSame(RecommendationStatus::NoMatch, $this->recommend($this->state())->status);
    }

    public function test_approved_active_but_out_of_stock_reports_unavailable(): void
    {
        $this->madeProfile($this->approvedProduct(['stock' => 0]));

        $result = $this->recommend($this->state());

        self::assertSame(RecommendationStatus::Unavailable, $result->status);
        self::assertCount(0, $result->candidates);
    }

    public function test_out_of_stock_match_prevails_over_in_stock_non_match(): void
    {
        $this->madeProfile($this->approvedProduct(['stock' => 0]));
        $this->madeProfile(
            $this->approvedProduct(),
            ['sections' => ['skin'], 'concerns' => ['skin_dryness']],
        );

        $result = $this->recommend($this->state());

        self::assertSame(RecommendationStatus::Unavailable, $result->status);
    }

    /* ------------------------------------------------------------------ */
    /* Matching */
    /* ------------------------------------------------------------------ */

    public function test_state_without_any_overlap_returns_no_match(): void
    {
        $this->madeProfile($this->approvedProduct());

        $result = $this->recommend($this->state([
            'section' => null,
            'concerns' => ['skin_pigmentation'],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
    }

    public function test_section_match_alone_produces_a_candidate(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product, ['concerns' => ['hair_frizz']]);

        $result = $this->recommend($this->state([
            'section' => 'hair',
            'concerns' => ['oily_scalp'],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame($product->id, $result->candidates[0]->productReference);
        self::assertSame([], $result->candidates[0]->matchedConcerns);
    }

    public function test_unknown_fabricated_concern_codes_never_produce_a_match(): void
    {
        $this->madeProfile($this->approvedProduct());

        $result = $this->recommend($this->state([
            'section' => null,
            'concerns' => ['cures_everything'],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
    }

    public function test_partial_concern_match_only_counts_known_codes(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product);

        $result = $this->recommend($this->state([
            'section' => null,
            'concerns' => ['skin_pigmentation', 'hair_dryness'],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame(['hair_dryness'], $result->candidates[0]->matchedConcerns);
    }

    public function test_profiles_matching_more_concerns_rank_first(): void
    {
        $single = $this->madeProfile(
            $this->approvedProduct(),
            ['concerns' => ['hair_dryness']],
        );
        $double = $this->madeProfile(
            $this->approvedProduct(),
            ['concerns' => ['hair_dryness', 'hair_frizz']],
        );

        $result = $this->recommend($this->state([
            'section' => null,
            'concerns' => ['hair_dryness', 'hair_frizz'],
        ]));

        self::assertSame([
            $double->product_id,
            $single->product_id,
        ], array_map(static fn ($c) => $c->productReference, $result->candidates));
    }

    public function test_section_match_breaks_concern_only_ties(): void
    {
        $withSection = $this->madeProfile(
            $this->approvedProduct(),
            ['concerns' => ['hair_dryness']],
        );
        $withoutSection = $this->madeProfile(
            $this->approvedProduct(),
            ['sections' => ['skin'], 'concerns' => ['hair_dryness']],
        );

        $result = $this->recommend($this->state([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
        ]));

        self::assertSame($withSection->product_id, $result->candidates[0]->productReference);
    }

    /* ------------------------------------------------------------------ */
    /* Preferences */
    /* ------------------------------------------------------------------ */

    public function test_preference_match_boosts_score_and_is_recorded(): void
    {
        $plain = $this->madeProfile($this->approvedProduct());
        $lightweight = $this->madeProfile(
            $this->approvedProduct(),
            ['approved_benefits' => ['Ultra lightweight, fast absorbing formula']],
        );

        $result = $this->recommend($this->state([
            'preferences' => ['lightweight' => true],
        ]));

        self::assertSame($lightweight->product_id, $result->candidates[0]->productReference);
        self::assertContains('lightweight', $result->candidates[0]->matchedPreferences);
        self::assertGreaterThan($result->candidates[1]->score, $result->candidates[0]->score);
    }

    public function test_unlexiconed_preference_is_neutral_but_never_excludes(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product);

        $result = $this->recommend($this->state([
            'preferences' => ['wash_frequency' => '1_2'],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame([], $result->candidates[0]->matchedPreferences);
    }

    public function test_herbal_preference_matches_approved_text_and_boosts(): void
    {
        $plain = $this->madeProfile(
            $this->approvedProduct(),
            ['positioning' => 'Everyday conditioning care.'],
        );
        $herbal = $this->madeProfile(
            $this->approvedProduct(),
            ['positioning' => 'Traditional ayurvedic hair care.'],
        );

        $result = $this->recommend($this->state([
            'preferences' => ['herbal' => true],
        ]));

        self::assertSame($herbal->product_id, $result->candidates[0]->productReference);
        self::assertContains('herbal', $result->candidates[0]->matchedPreferences);
        self::assertGreaterThan($result->candidates[1]->score, $result->candidates[0]->score);
    }

    public function test_gentle_preference_matches_approved_text_and_boosts(): void
    {
        $plain = $this->madeProfile(
            $this->approvedProduct(),
            ['positioning' => 'Everyday conditioning care.'],
        );
        $gentle = $this->madeProfile($this->approvedProduct());

        $result = $this->recommend($this->state([
            'preferences' => ['gentle' => true],
        ]));

        self::assertSame($gentle->product_id, $result->candidates[0]->productReference);
        self::assertContains('gentle', $result->candidates[0]->matchedPreferences);
        self::assertGreaterThan($result->candidates[1]->score, $result->candidates[0]->score);
    }

    public function test_new_preferences_never_match_without_approved_evidence(): void
    {
        $product = $this->madeProfile(
            $this->approvedProduct(),
            ['positioning' => 'Everyday conditioning care.'],
        );

        $result = $this->recommend($this->state([
            'preferences' => ['herbal' => true, 'gentle' => true],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame([], $result->candidates[0]->matchedPreferences);
        self::assertSame($product->product_id, $result->candidates[0]->productReference);
    }

    /* ------------------------------------------------------------------ */
    /* Avoidances */
    /* ------------------------------------------------------------------ */

    public function test_avoidance_with_positively_asserted_conflict_is_a_hard_exclusion(): void
    {
        $this->madeProfile($this->approvedProduct(), [
            'approved_benefits' => ['Soft finish with fragrance'],
        ]);

        $result = $this->recommend($this->state([
            'memory' => [['category' => 'avoidance', 'slot' => 'fragrance', 'value' => 'not for me']],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
        self::assertCount(0, $result->candidates);
    }

    public function test_avoidance_is_not_triggered_by_negated_or_absent_traits(): void
    {
        $product = $this->madeProfile($this->approvedProduct(), [
            'positioning' => 'Fragrance free formula, non sticky feel.',
        ]);

        $result = $this->recommend($this->state([
            'memory' => [
                ['category' => 'avoidance', 'slot' => 'fragrance', 'value' => 'no'],
                ['category' => 'avoidance', 'slot' => 'sticky', 'value' => 'no'],
            ],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame($product->product_id, $result->candidates[0]->productReference);
    }

    public function test_unknown_avoidance_slot_is_ignored(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product);

        $result = $this->recommend($this->state([
            'memory' => [['category' => 'avoidance', 'slot' => 'unicorn_ingredient', 'value' => 'not for me']],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame($product->id, $result->candidates[0]->productReference);
    }

    public function test_paraben_avoidance_with_positively_asserted_conflict_is_a_hard_exclusion(): void
    {
        $this->madeProfile($this->approvedProduct(), [
            'approved_benefits' => ['Paraben blend keeps the formula stable'],
        ]);

        $result = $this->recommend($this->state([
            'memory' => [['category' => 'avoidance', 'slot' => 'paraben', 'value' => 'nahi chahiye']],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
        self::assertCount(0, $result->candidates);
    }

    public function test_sulphate_avoidance_with_positively_asserted_conflict_is_a_hard_exclusion(): void
    {
        $this->madeProfile($this->approvedProduct(), [
            'approved_benefits' => ['Sulphate lathering complex'],
        ]);

        $result = $this->recommend($this->state([
            'memory' => [['category' => 'avoidance', 'slot' => 'sulphate', 'value' => 'no']],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
        self::assertCount(0, $result->candidates);
    }

    public function test_paraben_and_sulphate_avoidance_are_not_triggered_by_negative_framing(): void
    {
        $product = $this->madeProfile($this->approvedProduct(), [
            'positioning' => 'Sulphate-free and paraben-free formula.',
        ]);

        $result = $this->recommend($this->state([
            'memory' => [
                ['category' => 'avoidance', 'slot' => 'paraben', 'value' => 'no'],
                ['category' => 'avoidance', 'slot' => 'sulphate', 'value' => 'no'],
            ],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame($product->product_id, $result->candidates[0]->productReference);
    }

    public function test_duplicate_avoidance_entries_do_not_change_the_exclusion(): void
    {
        $this->madeProfile($this->approvedProduct(), [
            'approved_benefits' => ['Soft finish with fragrance'],
        ]);

        $result = $this->recommend($this->state([
            'memory' => [
                ['category' => 'avoidance', 'slot' => 'fragrance', 'value' => 'no'],
                ['category' => 'avoidance', 'slot' => 'fragrance', 'value' => 'no'],
            ],
        ]));

        self::assertSame(RecommendationStatus::NoMatch, $result->status);
        self::assertCount(0, $result->candidates);
    }

    /* ------------------------------------------------------------------ */
    /* Ranking guarantees */
    /* ------------------------------------------------------------------ */

    public function test_equal_scores_break_deterministically_by_product_reference(): void
    {
        $productA = $this->madeProfile($this->approvedProduct());
        $productB = $this->madeProfile(
            $this->approvedProduct(),
            ['concerns' => ['hair_frizz']],
        );

        $result = $this->recommend($this->state([
            'concerns' => [],
        ]));

        $ids = array_map(static fn ($c) => $c->productReference, $result->candidates);

        self::assertCount(2, $ids);

        $sorted = [...$ids];
        sort($sorted);

        self::assertSame($sorted, $ids);
        self::assertContains($productA->product_id, $ids);
        self::assertContains($productB->product_id, $ids);
    }

    public function test_ranking_is_deterministic_across_repeated_runs(): void
    {
        $this->madeProfile($this->approvedProduct());
        $this->madeProfile($this->approvedProduct());
        $this->madeProfile(
            $this->approvedProduct(),
            ['sections' => ['skin'], 'concerns' => ['skin_dryness']],
        );

        $first = $this->recommend($this->state());
        $second = $this->recommend($this->state());

        self::assertSame($first->toArray(), $second->toArray());
    }

    public function test_result_is_bounded_by_max_candidates(): void
    {
        foreach (range(1, 5) as $i) {
            $this->madeProfile($this->approvedProduct());
        }

        $result = $this->recommend($this->state());

        self::assertCount(3, $result->candidates);
    }

    public function test_max_candidates_is_respected_when_configured(): void
    {
        foreach (range(1, 4) as $i) {
            $this->madeProfile($this->approvedProduct());
        }

        $settings = config('dadi.recommendation');
        $settings['max_candidates'] = 2;

        $engine = new RecommendationEngine(
            store: $this->app->make(DadiProductProfileStore::class),
            matcher: new ApprovedTextMatcher,
            settings: $settings,
        );

        $result = $engine->recommend($this->state(), SafetyAssessment::clear());

        self::assertCount(2, $result->candidates);
    }

    /* ------------------------------------------------------------------ */
    /* Dynamic catalogue + AI boundary */
    /* ------------------------------------------------------------------ */

    public function test_a_brand_new_dynamic_product_recommends_without_any_code_change(): void
    {
        $product = $this->approvedProduct(['name' => 'Dynamic Champion Serum', 'sku' => 'DYN-99999']);
        $this->madeProfile($product, [
            'positioning' => 'Halka sa daily serum, roz ke use ke liye.',
            'approved_benefits' => ['Deeply hydrates dry scalp and lengths'],
        ]);

        $result = $this->recommend($this->state());

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertCount(1, $result->candidates);
        self::assertSame($product->id, $result->candidates[0]->productReference);
    }

    public function test_ai_supplied_identifiers_cannot_inject_or_replace_a_recommendation(): void
    {
        $product = $this->approvedProduct();
        $this->madeProfile($product);

        $result = $this->recommend($this->state([
            'attributes' => ['product_id' => 999999],
            'preferences' => ['sku' => 'AI-FABRICATED-SKU'],
        ]));

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertSame($product->id, $result->candidates[0]->productReference);
        self::assertNotContains(999999, array_map(static fn ($c) => $c->productReference, $result->candidates));
    }

    public function test_a_product_without_an_approved_profile_is_never_recommended(): void
    {
        $legit = $this->madeProfile($this->approvedProduct());

        $floating = $this->approvedProduct(['name' => 'Floating unauthorised product']);

        $result = $this->recommend($this->state());

        $references = array_map(static fn ($c) => $c->productReference, $result->candidates);

        self::assertContains($legit->product_id, $references);
        self::assertNotContains($floating->id, $references);
    }

    public function test_recommendation_result_carries_no_commerce_fields(): void
    {
        $this->madeProfile($this->approvedProduct(['selling_price' => 499.00, 'stock' => 20, 'sku' => 'HAIR-X1']));

        $result = $this->recommend($this->state());
        $json = json_encode($result->toArray(), JSON_THROW_ON_ERROR);

        self::assertSame(RecommendationStatus::Available, $result->status);

        foreach (['sku', 'price', 'selling_price', 'mrp', 'stock', 'quantity'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $json);
        }
    }
}
