<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Ai\DadiConversationEngine;
use App\Dadi\Ai\Validation\AiOutputValidator;
use App\Dadi\Context\DadiContextBuilder;
use App\Dadi\Enums\Concern;
use App\Dadi\Enums\ProductProfileStatus;
use App\Dadi\Enums\Section;
use App\Dadi\Exceptions\InvalidAiOutputException;
use App\Dadi\Exceptions\InvalidProductProfileException;
use App\Dadi\Exceptions\ProductProfileReviewException;
use App\Dadi\Exceptions\ProductProfileUnavailableException;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\ValueObjects\AiResponse;
use App\Dadi\ValueObjects\AiTurnResult;
use App\Dadi\ValueObjects\ConversationState;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\DadiProductProfile;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductProfilePersistenceTest extends TestCase
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

    private function reviewerWithoutPermission(): Admin
    {
        return Admin::factory()->create();
    }

    private function store(): DadiProductProfileStore
    {
        return $this->app->make(DadiProductProfileStore::class);
    }

    /**
     * @return array<string,mixed>
     */
    private function content(array $overrides = []): array
    {
        return array_merge([
            'sections' => ['hair'],
            'concerns' => ['hair_dryness', 'hair_frizz'],
            'positioning' => 'Halka sa daily-care option hai, roz ke use ke liye.',
            'approved_benefits' => ['Gently nourishes dry lengths'],
            'approved_usage_context' => ['Apply to damp lengths'],
            'approved_precautions' => ['Avoid contact with eyes'],
            'suitability_notes' => ['Consider for dry or frizzy hair'],
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

    /* ------------------------------------------------------------------ */
    /* Product profile */
    /* ------------------------------------------------------------------ */

    public function test_authorized_admin_creates_a_draft_profile_referencing_the_product(): void
    {
        $product = $this->approvedProduct();
        $admin = $this->reviewer();

        $profile = $this->store()->create($product, $this->content(), $admin);

        self::assertSame($product->id, $profile->product_id);
        self::assertSame(ProductProfileStatus::Draft, $profile->status);
        self::assertSame('hair', $profile->sections[0]);
        self::assertSame(['hair_dryness', 'hair_frizz'], $profile->concerns);
        self::assertSame($admin->id, $profile->created_by);
    }

    public function test_creation_rejects_unknown_concern_codes(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        $this->store()->create(
            $this->approvedProduct(),
            $this->content(['concerns' => ['cures_dandruff']]),
            $this->reviewer(),
        );
    }

    public function test_creation_rejects_unknown_payload_keys(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        $this->store()->create(
            $this->approvedProduct(),
            $this->content(['price' => 499]),
            $this->reviewer(),
        );
    }

    public function test_creation_forbids_commerce_content_inside_intelligence(): void
    {
        $this->expectException(InvalidProductProfileException::class);

        $this->store()->create(
            $this->approvedProduct(),
            $this->content(['positioning' => 'Best option, price 499 only.']),
            $this->reviewer(),
        );
    }

    public function test_a_product_cannot_have_two_profiles(): void
    {
        $product = $this->approvedProduct();
        $admin = $this->reviewer();
        $this->store()->create($product, $this->content(), $admin);

        $this->expectException(InvalidProductProfileException::class);

        $this->store()->create($product, $this->content(), $admin);
    }

    /* ------------------------------------------------------------------ */
    /* Approval lifecycle */
    /* ------------------------------------------------------------------ */

    public function test_lifecycle_moves_draft_to_pending_to_approved(): void
    {
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());

        $this->store()->submitForReview($profile, $this->reviewer());
        self::assertSame(ProductProfileStatus::PendingReview, $profile->fresh()->status);

        $second = $this->reviewer();
        $this->store()->approve($profile->fresh(), $second);

        $approved = $profile->fresh();
        self::assertSame(ProductProfileStatus::Approved, $approved->status);
        self::assertSame($second->id, $approved->reviewed_by);
        self::assertNotNull($approved->reviewed_at);
    }

    public function test_draft_profiles_cannot_be_approved_directly(): void
    {
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());

        $this->expectException(ProductProfileReviewException::class);

        $this->store()->approve($profile, $this->reviewer());
    }

    public function test_rejected_profiles_are_never_retrievable(): void
    {
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());
        $this->store()->submitForReview($profile, $this->reviewer());
        $this->store()->reject($profile->fresh(), $this->reviewer());

        self::assertSame(ProductProfileStatus::Rejected, $profile->fresh()->status);
        self::assertCount(0, $this->store()->retrieveApproved());
    }

    public function test_draft_and_pending_profiles_are_not_retrievable(): void
    {
        $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());
        $pending = $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());
        $this->store()->submitForReview($pending, $this->reviewer());

        self::assertCount(0, $this->store()->retrieveApproved());
    }

    public function test_editing_approved_content_revokes_the_approval(): void
    {
        $admin = $this->reviewer();
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $admin);
        $approved = $this->approveProfile($profile, $admin);

        $this->store()->updateIntelligence($approved, $this->content(['positioning' => 'Changed positioning.']), $admin);

        $revoked = $approved->fresh();
        self::assertSame(ProductProfileStatus::PendingReview, $revoked->status);
        self::assertNull($revoked->reviewed_by);
        self::assertNull($revoked->reviewed_at);
        self::assertCount(0, $this->store()->retrieveApproved());
    }

    public function test_editing_a_rejected_profile_returns_it_to_draft(): void
    {
        $admin = $this->reviewer();
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $admin);
        $this->store()->submitForReview($profile, $admin);
        $rejected = $this->store()->reject($profile->fresh(), $admin);

        $this->store()->updateIntelligence($rejected, $this->content(['positioning' => 'Revised.']), $admin);

        self::assertSame(ProductProfileStatus::Draft, $rejected->fresh()->status);
    }

    /* ------------------------------------------------------------------ */
    /* Human authority */
    /* ------------------------------------------------------------------ */

    public function test_unauthorized_admin_cannot_approve(): void
    {
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer());
        $this->store()->submitForReview($profile, $this->reviewer());

        $this->expectException(ProductProfileReviewException::class);

        $this->store()->approve($profile->fresh(), $this->reviewerWithoutPermission());
    }

    public function test_unauthorized_admin_cannot_create_a_profile(): void
    {
        $this->expectException(ProductProfileReviewException::class);

        $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewerWithoutPermission());
    }

    public function test_authorized_admin_review_is_recorded_in_activity_log(): void
    {
        $admin = $this->reviewer();
        $profile = $this->store()->create($this->approvedProduct(), $this->content(), $admin);
        $this->store()->submitForReview($profile, $admin);
        $this->store()->approve($profile->fresh(), $admin, notes: 'LGTM');

        $entry = ActivityLog::query()
            ->where('action', 'dadi_product_profile_approved')
            ->where('entity_id', $profile->id)
            ->first();

        self::assertNotNull($entry);
        self::assertSame(Admin::class, $entry->actor_type);
        self::assertSame($admin->id, $entry->actor_id);
        self::assertSame('Dadi product profile approved by '.$admin->name.'.', (string) $entry->description);
        self::assertSame(
            ['reviewed_by' => $admin->id, 'notes' => 'LGTM'],
            array_intersect_key($entry->new_values ?? [], ['reviewed_by' => true, 'notes' => true]),
        );
    }

    /* ------------------------------------------------------------------ */
    /* Product truth */
    /* ------------------------------------------------------------------ */

    public function test_profile_never_caches_price_or_stock(): void
    {
        $product = $this->approvedProduct();
        $profile = $this->store()->create($product, $this->content(), $this->reviewer());
        $approved = $this->approveProfile($profile, $this->reviewer());

        $payload = $this->store()->buildProfile($approved)->toArray();

        self::assertArrayNotHasKey('sku', $payload);
        self::assertArrayNotHasKey('price', $payload);
        self::assertArrayNotHasKey('stock', $payload);
        self::assertArrayNotHasKey('selling_price', $payload);
        self::assertArrayNotHasKey('mrp', $payload);

        self::assertSame(
            (string) $product->selling_price,
            (string) $approved->product->fresh()->selling_price,
        );
        self::assertSame($product->stock, $approved->product->fresh()->stock);
    }

    public function test_price_and_stock_read_live_from_the_catalogue(): void
    {
        $product = $this->approvedProduct(['selling_price' => 399.00, 'stock' => 12]);
        $profile = $this->approveProfile(
            $this->store()->create($product, $this->content(), $this->reviewer()),
            $this->reviewer(),
        );

        $live = $profile->product->fresh();
        self::assertSame(399.00, (float) $live->selling_price);
        self::assertSame(12, $live->stock);
        self::assertSame(12, $live->getAvailableStock());
    }

    /* ------------------------------------------------------------------ */
    /* Product status */
    /* ------------------------------------------------------------------ */

    public function test_active_and_approved_profile_is_usable(): void
    {
        $profile = $this->approveProfile(
            $this->store()->create($this->approvedProduct(), $this->content(), $this->reviewer()),
            $this->reviewer(),
        );

        self::assertTrue($this->store()->usableForRecommendation($profile));
        self::assertCount(1, $this->store()->retrieveApproved());
    }

    public function test_inactive_product_is_never_usable_even_when_approved(): void
    {
        $product = $this->approvedProduct(['status' => 'inactive']);
        $profile = $this->approveProfile(
            $this->store()->create($product, $this->content(), $this->reviewer()),
            $this->reviewer(),
        );

        self::assertFalse($this->store()->usableForRecommendation($profile));
        self::assertCount(0, $this->store()->retrieveApproved());

        $this->expectException(ProductProfileUnavailableException::class);
        $this->store()->buildProfile($profile);
    }

    public function test_approval_is_separate_from_commercial_availability(): void
    {
        $product = $this->approvedProduct(['stock' => 0]);
        $profile = $this->approveProfile(
            $this->store()->create($product, $this->content(), $this->reviewer()),
            $this->reviewer(),
        );

        self::assertTrue($product->fresh()->isOutOfStock());
        self::assertTrue($this->store()->isApproved($profile->fresh()));
        self::assertTrue($this->store()->usableForRecommendation($profile->fresh()));

        $payload = $this->store()->buildProfile($profile->fresh())->toArray();
        self::assertArrayNotHasKey('stock', $payload);
    }

    public function test_soft_deleted_product_invalidates_the_profile(): void
    {
        $product = $this->approvedProduct();
        $profile = $this->approveProfile(
            $this->store()->create($product, $this->content(), $this->reviewer()),
            $this->reviewer(),
        );

        $product->delete();

        self::assertCount(0, $this->store()->retrieveApproved());
        self::assertFalse($this->store()->usableForRecommendation($profile->fresh()));
    }

    public function test_hard_deleted_product_cascades_the_profile(): void
    {
        $product = $this->approvedProduct();
        $profile = $this->store()->create($product, $this->content(), $this->reviewer());

        $product->forceDelete();

        self::assertNull(DadiProductProfile::find($profile->id));
    }

    /* ------------------------------------------------------------------ */
    /* Retrieval */
    /* ------------------------------------------------------------------ */

    public function test_retrieval_filters_by_section(): void
    {
        [$hairId, $skinId] = $this->makeApprovedPair();

        $profiles = $this->store()->retrieveApproved(section: Section::Hair);

        self::assertCount(1, $profiles);
        self::assertSame($hairId, $profiles[0]->productReference);
        self::assertNotContains($skinId, array_map(static fn ($p) => $p->productReference, $profiles));
    }

    public function test_retrieval_filters_by_approved_concern(): void
    {
        [$hairId, $skinId] = $this->makeApprovedPair();

        $profiles = $this->store()->retrieveApprovedByConcern(Concern::SkinDryness);

        self::assertCount(1, $profiles);
        self::assertSame($skinId, $profiles[0]->productReference);
        self::assertNotContains($hairId, array_map(static fn ($p) => $p->productReference, $profiles));
    }

    public function test_retrieval_is_deterministic_by_product_id(): void
    {
        $this->makeApprovedPair();

        $refs = array_map(
            static fn ($profile) => $profile->productReference,
            $this->store()->retrieveApproved(),
        );

        self::assertCount(2, $refs);

        $sorted = $refs;
        sort($sorted);

        self::assertSame($sorted, $refs);
    }

    /**
     * @return array{int, int} 0: hair product id, 1: skin product id
     */
    private function makeApprovedPair(): array
    {
        $admin = $this->reviewer();

        $hair = $this->approvedProduct();
        $skin = $this->approvedProduct();

        foreach ([$hair, $skin] as $index => $product) {
            $profile = $this->store()->create(
                $product,
                $this->content([
                    'sections' => [['hair'], ['skin']][$index],
                    'concerns' => [['hair_dryness'], ['skin_dryness']][$index],
                ]),
                $admin,
            );
            $this->approveProfile($profile, $admin);
        }

        return [$hair->id, $skin->id];
    }

    /* ------------------------------------------------------------------ */
    /* AI boundary */
    /* ------------------------------------------------------------------ */

    public function test_stage_four_schema_still_has_no_product_selection_field(): void
    {
        $turn = new AiTurnResult(reply: 'Haan beta, batao kya chahiye.');

        $forbidden = ['sku', 'product_id', 'recommended_sku', 'recommended_product', 'product'];

        self::assertEmpty(array_intersect(array_keys($turn->toArray()), $forbidden));
        self::assertContains('sku', AiOutputValidator::FORBIDDEN_FIELDS);
        self::assertContains('product_id', AiOutputValidator::FORBIDDEN_FIELDS);
    }

    public function test_ai_output_validator_still_rejects_product_fields(): void
    {
        $validator = new AiOutputValidator;

        foreach (['sku', 'price', 'product_id', 'stock'] as $field) {
            $payload = ['reply' => 'Yeh lo.', $field => 'anything'];

            try {
                $validator->validate(
                    new AiResponse(content: json_encode($payload), data: $payload),
                    new ConversationState,
                );
                self::fail("Field '{$field}' was not rejected.");
            } catch (InvalidAiOutputException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_product_profiles_are_not_injected_into_the_dadi_context(): void
    {
        $user = User::factory()->create();
        $conversation = $this->app->make(DadiConversationStore::class)->start(user: $user, locale: 'hi');

        $context = DadiContextBuilder::fromConfig()->forConversation($conversation->id, user: $user);

        self::assertSame(
            ['conversation_id', 'locale', 'recent_messages', 'state', 'historical_memory'],
            array_keys($context->toArray()),
        );
    }

    public function test_ai_layer_has_no_access_to_approve_or_author_profiles(): void
    {
        $engineConstructor = (new \ReflectionClass(DadiConversationEngine::class))->getConstructor();
        $dependencies = $engineConstructor
            ? array_map(
                static fn ($parameter) => (string) $parameter->getType(),
                $engineConstructor->getParameters(),
            )
            : [];

        self::assertNotContains(DadiProductProfileStore::class, $dependencies);

        $store = new \ReflectionClass(DadiProductProfileStore::class);

        foreach (['create' => 2, 'approve' => 1, 'reject' => 1] as $lifecycleMethod => $actorIndex) {
            $actorType = (string) $store->getMethod($lifecycleMethod)->getParameters()[$actorIndex]->getType();
            self::assertSame(
                Admin::class,
                $actorType,
                $lifecycleMethod.' must require an Admin actor.',
            );
        }
    }
}
