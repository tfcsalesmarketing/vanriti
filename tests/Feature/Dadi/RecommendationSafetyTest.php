<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Enums\RecommendationStatus;
use App\Dadi\Enums\SafetyVerdict;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\Recommendation\RecommendationEngine;
use App\Dadi\ValueObjects\ConversationState;
use App\Dadi\ValueObjects\SafetyAssessment;
use App\Models\Admin;
use App\Models\DadiProductProfile;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationSafetyTest extends TestCase
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
    private function content(): array
    {
        return [
            'sections' => ['hair'],
            'concerns' => ['hair_dryness'],
            'positioning' => 'Gentle daily care.',
            'approved_benefits' => ['Nourishes dry lengths'],
            'approved_usage_context' => ['Apply to damp lengths'],
            'approved_precautions' => ['Avoid contact with eyes'],
            'suitability_notes' => ['Consider for dry hair'],
        ];
    }

    private function madeApprovedProfile(): DadiProductProfile
    {
        $admin = $this->reviewer();
        $profile = $this->store()->create(Product::factory()->create(), $this->content(), $admin);
        $this->store()->submitForReview($profile, $admin);
        $this->store()->approve($profile->fresh(), $admin);

        return $profile->fresh();
    }

    private function state(): ConversationState
    {
        return ConversationState::fromArray([
            'section' => 'hair',
            'concerns' => ['hair_dryness'],
            'preferences' => [],
            'attributes' => [],
            'memory' => [],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Stage 5 verdict policies */
    /* ------------------------------------------------------------------ */

    public function test_clear_assessment_permits_recommendations(): void
    {
        self::assertTrue(SafetyAssessment::clear()->permitsRecommendation());
        self::assertSame(SafetyVerdict::Clear, SafetyAssessment::clear()->verdict);

        $this->madeApprovedProfile();

        $result = $this->engine()->recommend($this->state(), SafetyAssessment::clear());

        self::assertSame(RecommendationStatus::Available, $result->status);
        self::assertTrue($result->hasCandidates());
    }

    public function test_block_is_a_hard_zero_candidate_gate(): void
    {
        $assessment = SafetyAssessment::block(['Medical symptoms reported.']);

        self::assertTrue($assessment->blocksRecommendation());
        self::assertFalse($assessment->permitsRecommendation());

        $this->madeApprovedProfile();

        $result = $this->engine()->recommend($this->state(), $assessment);

        self::assertSame(RecommendationStatus::SafetyBlocked, $result->status);
        self::assertCount(0, $result->candidates);
        self::assertSame(['Medical symptoms reported.'], $result->reasons);
    }

    public function test_advisory_restricts_recommendations_until_permission_returns(): void
    {
        $assessment = SafetyAssessment::advisory(['Customer is a minor.']);

        self::assertFalse($assessment->blocksRecommendation());
        self::assertFalse($assessment->permitsRecommendation());

        $this->madeApprovedProfile();

        $result = $this->engine()->recommend($this->state(), $assessment);

        self::assertSame(RecommendationStatus::AdvisoryRestricted, $result->status);
        self::assertCount(0, $result->candidates);
        self::assertSame(['Customer is a minor.'], $result->reasons);
    }

    public function test_advisory_still_restricts_even_when_a_valid_profile_exists(): void
    {
        $profile = $this->madeApprovedProfile();

        $result = $this->engine()->recommend(
            $this->state(),
            SafetyAssessment::advisory(['Caution needed.']),
        );

        self::assertSame(RecommendationStatus::AdvisoryRestricted, $result->status);
        self::assertNotContains(
            $profile->product_id,
            array_map(static fn ($c) => $c->productReference, $result->candidates),
        );
    }

    public function test_blocked_result_carries_no_candidates_never_matching_approved_intelligence(): void
    {
        $profile = $this->madeApprovedProfile();

        $result = $this->engine()->recommend($this->state(), SafetyAssessment::block(['Hard gate.']));

        self::assertSame([], array_map(static fn ($c) => $c->productReference, $result->candidates));
        self::assertNotContains($profile->product_id, array_map(static fn ($c) => $c->productReference, $result->candidates));
    }

    /* ------------------------------------------------------------------ */
    /* AI boundary */
    /* ------------------------------------------------------------------ */

    public function test_the_engine_has_no_ai_input_and_cannot_be_coaxed_into_fabrication(): void
    {
        $constructor = (new \ReflectionClass(RecommendationEngine::class))->getConstructor();
        $dependencies = $constructor
            ? array_map(
                static fn ($parameter) => (string) $parameter->getType(),
                $constructor->getParameters(),
            )
            : [];

        self::assertContains(DadiProductProfileStore::class, $dependencies);
        self::assertNotContains('App\Dadi\Contracts\AiProvider', $dependencies);
        self::assertNotContains('App\Dadi\Ai\Providers\OpenAiProvider', $dependencies);
    }
}
