<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Enums\ProductProfileStatus;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\DadiProductProfile;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductProfileReviewTest extends TestCase
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

    private function plainAdmin(): Admin
    {
        return Admin::factory()->create();
    }

    private function product(): Product
    {
        return Product::factory()->create();
    }

    /**
     * @return array<string,mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'sections' => ['hair'],
            'concerns' => ['hair_dryness'],
            'positioning' => 'Halka aur sust daily-care choice.',
            'approved_benefits' => "Gently nourishes\nReduces frizz",
            'approved_usage_context' => 'Apply to damp lengths',
            'approved_precautions' => 'Avoid contact with eyes',
            'suitability_notes' => 'Consider for dry hair',
        ], $overrides);
    }

    private function reachPendingReview(Product $product, Admin $admin): DadiProductProfile
    {
        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.store'), ['product_id' => $product->id] + $this->formData())
            ->assertRedirect();

        $profile = DadiProductProfile::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.submit-review', $profile))
            ->assertRedirect()
            ->assertSessionHas('success');

        return $profile->fresh();
    }

    /* ------------------------------------------------------------------ */
    /* Access control */
    /* ------------------------------------------------------------------ */

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dadi.product-profiles.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_without_review_permission_is_forbidden(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get(route('admin.dadi.product-profiles.index'))
            ->assertForbidden();
    }

    public function test_authorized_admin_can_see_the_review_index(): void
    {
        $this->actingAs($this->reviewer(), 'admin')
            ->get(route('admin.dadi.product-profiles.index'))
            ->assertOk()
            ->assertSee('Dadi Product Profiles');
    }

    /* ------------------------------------------------------------------ */
    /* Authoring */
    /* ------------------------------------------------------------------ */

    public function test_authorized_admin_creates_a_draft_via_http(): void
    {
        $product = $this->product();

        $this->actingAs($this->reviewer(), 'admin')
            ->post(route('admin.dadi.product-profiles.store'), ['product_id' => $product->id] + $this->formData())
            ->assertRedirect()
            ->assertSessionHas('success');

        $profile = DadiProductProfile::query()->where('product_id', $product->id)->first();

        self::assertNotNull($profile);
        self::assertSame(ProductProfileStatus::Draft, $profile->status);
        self::assertSame(['Gently nourishes', 'Reduces frizz'], $profile->approved_benefits);
    }

    public function test_create_shows_validation_error_for_forbidden_content(): void
    {
        $product = $this->product();

        $this->actingAs($this->reviewer(), 'admin')
            ->post(
                route('admin.dadi.product-profiles.store'),
                ['product_id' => $product->id] + $this->formData(['positioning' => 'Price 499 and stock is live.']),
            )
            ->assertRedirect()
            ->assertSessionHasErrors('content');
    }

    public function test_create_for_an_unknown_product_is_not_found(): void
    {
        $this->actingAs($this->reviewer(), 'admin')
            ->post(route('admin.dadi.product-profiles.store'), ['product_id' => 999999] + $this->formData())
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /* Human approval */
    /* ------------------------------------------------------------------ */

    public function test_authorized_admin_approves_a_pending_profile_via_http(): void
    {
        $admin = $this->reviewer();
        $product = $this->product();
        $profile = $this->reachPendingReview($product, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.approve', $profile), ['notes' => 'Verified by hand'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $approved = $profile->fresh();
        self::assertSame(ProductProfileStatus::Approved, $approved->status);
        self::assertSame($admin->id, $approved->reviewed_by);

        $log = ActivityLog::query()
            ->where('action', 'dadi_product_profile_approved')
            ->where('entity_id', $profile->id)
            ->first();

        self::assertNotNull($log);
        self::assertSame('Verified by hand', $log->new_values['notes'] ?? null);
    }

    public function test_rejected_profile_is_recorded_via_http(): void
    {
        $admin = $this->reviewer();
        $product = $this->product();
        $profile = $this->reachPendingReview($product, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.reject', $profile), ['notes' => 'Needs rework'])
            ->assertRedirect()
            ->assertSessionHas('success');

        self::assertSame(ProductProfileStatus::Rejected, $profile->fresh()->status);
    }

    public function test_approving_a_draft_is_rejected_and_keeps_status(): void
    {
        $admin = $this->reviewer();
        $product = $this->product();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.store'), ['product_id' => $product->id] + $this->formData())
            ->assertRedirect();

        $profile = DadiProductProfile::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.dadi.product-profiles.approve', $profile))
            ->assertRedirect()
            ->assertSessionHas('error');

        self::assertSame(ProductProfileStatus::Draft, $profile->fresh()->status);
    }

    public function test_unauthorized_admin_cannot_approve_via_http(): void
    {
        $reviewer = $this->reviewer();
        $profile = $this->reachPendingReview($this->product(), $reviewer);

        $this->actingAs($this->plainAdmin(), 'admin')
            ->post(route('admin.dadi.product-profiles.approve', $profile))
            ->assertForbidden();

        self::assertSame(ProductProfileStatus::PendingReview, $profile->fresh()->status);
    }
}
