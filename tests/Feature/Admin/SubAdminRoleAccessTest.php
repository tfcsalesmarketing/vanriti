<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubAdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function adminWithRole(string $roleSlug): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', $roleSlug)->firstOrFail());

        return $admin;
    }

    public function test_manager_role_has_content_and_catalogue_permissions(): void
    {
        $role = Role::where('slug', 'manager')->firstOrFail();

        $this->assertTrue($role->permissions()->whereIn('permissions.slug', [
            'manage-media', 'manage-banners', 'manage-blogs', 'manage-pages', 'manage-faqs',
            'manage-newsletters', 'manage-inventory', 'manage-coupons',
        ])->count() === 8);
    }

    public function test_support_role_is_limited_to_order_customer_domain(): void
    {
        $role = Role::where('slug', 'support')->firstOrFail();
        $slugs = $role->permissions()->pluck('permissions.slug');

        $this->assertTrue($slugs->contains('manage-orders'));
        $this->assertTrue($slugs->contains('manage-returns'));
        $this->assertTrue($slugs->contains('manage-customers'));
        $this->assertFalse($slugs->contains('manage-media'));
        $this->assertFalse($slugs->contains('manage-products'));
        $this->assertFalse($slugs->contains('manage-settings'));
    }

    public function test_manager_can_open_media_library_and_content_features(): void
    {
        $admin = $this->adminWithRole('manager');

        foreach (['admin.media.index', 'admin.blogs.index', 'admin.banners.index'] as $route) {
            $this->actingAs($admin, 'admin')
                ->get(route($route))
                ->assertOk();
        }
    }

    public function test_support_cannot_open_media_library(): void
    {
        $admin = $this->adminWithRole('support');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.media.index'))
            ->assertForbidden();
    }

    public function test_support_can_open_orders(): void
    {
        $admin = $this->adminWithRole('support');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_sidebar_hides_forbidden_links_for_manager(): void
    {
        $admin = $this->adminWithRole('manager');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Media Library')
            ->assertDontSee('Roles &amp; Permissions')
            ->assertDontSee('Settings');
    }
}
