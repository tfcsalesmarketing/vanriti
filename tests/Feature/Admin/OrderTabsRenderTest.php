<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTabsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_all_order_tabs_render_properly_with_complete_layout(): void
    {
        $admin = $this->superAdmin();

        $tabs = ['pending', 'hold', 'assign_courier', 'ready_to_ship', 'shipped', 'delivered', 'cancelled'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.index', ['tab' => $tab]));

            $response->assertOk();
            $content = $response->getContent();

            // Verify the HTML document is fully closed and not truncated
            $this->assertStringContainsString('</html>', $content, "Tab '{$tab}' missing closing </html> tag");
            $this->assertStringContainsString('</body>', $content, "Tab '{$tab}' missing closing </body> tag");
            $this->assertStringContainsString('Orders Management', $content, "Tab '{$tab}' missing page header");
            $this->assertStringContainsString('</script>', $content, "Tab '{$tab}' missing closing </script> tag");
        }
    }
}
