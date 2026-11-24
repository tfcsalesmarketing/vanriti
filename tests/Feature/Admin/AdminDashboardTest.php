<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->admin = Admin::factory()->create([
            'email' => 'admin@vanriti.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    public function test_admin_post_login_redirects_and_renders_dashboard(): void
    {
        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@vanriti.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $dashboardResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Dashboard');
    }
}
