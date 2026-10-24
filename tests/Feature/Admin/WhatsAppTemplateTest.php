<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use App\Models\WhatsAppTemplate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\WhatsAppTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(WhatsAppTemplateSeeder::class);

        // Configure WhatsApp credentials
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_waba_id'], ['value' => 'waba_9988776655', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_guest_cannot_access_templates(): void
    {
        $response = $this->get(route('admin.whatsapp.templates.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_templates_dashboard(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.whatsapp.templates.index'));
        $response->assertStatus(200);
        $response->assertSee('Meta Approved Templates');
        $response->assertSee('order_confirmation');
        $response->assertSee('shipping_update');
    }

    public function test_admin_can_sync_templates_from_meta(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/waba_9988776655/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => 'meta_tmpl_101',
                        'name' => 'diwali_wellness_vip',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'language' => 'en',
                        'components' => [
                            ['type' => 'HEADER', 'format' => 'IMAGE'],
                            ['type' => 'BODY', 'text' => 'Hello {{1}}, celebrate Diwali with Vanriti!'],
                            ['type' => 'FOOTER', 'text' => 'Vanriti Care'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.templates.sync'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'diwali_wellness_vip',
            'status' => 'APPROVED',
            'header_type' => 'IMAGE',
        ]);
    }

    public function test_admin_can_submit_new_template_to_meta(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/waba_9988776655/message_templates' => Http::response([
                'id' => 'meta_new_tmpl_202',
                'status' => 'PENDING',
            ], 200),
        ]);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.templates.store'), [
            'name' => 'kumkumadi_ritual_offer',
            'category' => 'MARKETING',
            'language' => 'en',
            'header_type' => 'TEXT',
            'header_text' => 'Pure Radiance Ritual 🌿',
            'body_text' => 'Hello {{1}}, explore our best-selling Kumkumadi Tailam today with code {{2}}!',
            'footer_text' => 'Vanriti Ayurveda',
            'button_type' => 'URL',
            'button_text' => 'Shop Kumkumadi',
            'button_url' => 'https://vanriti.com/shop',
        ]);

        $response->assertRedirect(route('admin.whatsapp.templates.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'kumkumadi_ritual_offer',
            'status' => 'PENDING',
            'header_type' => 'TEXT',
        ]);

        Http::assertSent(function ($request) {
            return $request['name'] === 'kumkumadi_ritual_offer'
                && $request['category'] === 'MARKETING'
                && count($request['components']) >= 3;
        });
    }

    public function test_admin_can_delete_template(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/waba_9988776655/message_templates*' => Http::response(['success' => true], 200),
        ]);

        $admin = $this->superAdmin();
        $template = WhatsAppTemplate::firstOrFail();

        $response = $this->actingAs($admin, 'admin')->delete(route('admin.whatsapp.templates.destroy', $template));
        $response->assertRedirect(route('admin.whatsapp.templates.index'));
        $this->assertDatabaseMissing('whatsapp_templates', ['id' => $template->id]);
    }

    public function test_api_approved_templates_returns_json(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.whatsapp.templates.api.approved'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'templates' => [
                '*' => ['id', 'name', 'status', 'category', 'body_text'],
            ],
        ]);
    }
}
