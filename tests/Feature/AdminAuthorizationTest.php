<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function productStorePayload(): array
    {
        $category = Category::factory()->create();

        return [
            'name' => 'Test Admin Product',
            'status' => 'active',
            'mrp' => 500,
            'selling_price' => 400,
            'category_ids' => [$category->id],
            'primary_category_id' => $category->id,
        ];
    }

    public function test_guest_hitting_admin_products_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_super_admin_can_create_product(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), $this->productStorePayload());

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Test Admin Product']);
    }

    public function test_active_admin_with_manage_products_permission_can_create_product(): void
    {
        $admin = Admin::factory()->create();

        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), $this->productStorePayload());

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Test Admin Product']);
    }

    public function test_inactive_admin_is_logged_out_and_redirected(): void
    {
        $admin = Admin::factory()->superAdmin()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_without_permission_is_forbidden_from_products(): void
    {
        $admin = Admin::factory()->create();

        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'Read-only',
        ]);
        $role->permissions()->attach(Permission::where('slug', 'view-dashboard')->firstOrFail());
        $admin->roles()->attach($role);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_admin_with_manage_products_permission_can_list_products(): void
    {
        $admin = Admin::factory()->create();

        $role = Role::create([
            'name' => 'Catalogue Manager',
            'slug' => 'catalogue-manager',
            'description' => 'Products only',
        ]);
        $role->permissions()->attach(Permission::where('slug', 'manage-products')->firstOrFail());
        $admin->roles()->attach($role);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk();
    }
}
