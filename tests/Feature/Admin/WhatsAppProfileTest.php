<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        // Configure WhatsApp credentials
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_webhook_verify_token'], ['value' => 'vanriti_verify_token_123', 'group' => 'whatsapp', 'type' => 'text']);
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

    public function test_guest_cannot_access_whatsapp_profile(): void
    {
        $response = $this->get(route('admin.whatsapp.profile'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_whatsapp_profile_with_meta_health_data(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/10987654321?fields=*' => Http::response([
                'verified_name' => 'Vanriti Official',
                'code_verification_status' => 'VERIFIED',
                'display_phone_number' => '+91 98765 43210',
                'quality_rating' => 'GREEN',
                'messaging_limit_tier' => 'TIER_10K',
                'status' => 'APPROVED',
            ], 200),
            'graph.facebook.com/v21.0/10987654321/whatsapp_business_profile?fields=*' => Http::response([
                'data' => [
                    [
                        'about' => 'Vanriti Natural Skincare',
                        'address' => 'Jaipur, Rajasthan',
                        'description' => 'Ayurvedic formulations',
                        'email' => 'care@vanriti.com',
                        'vertical' => 'BEAUTY',
                        'websites' => ['https://vanriti.com'],
                    ],
                ],
            ], 200),
        ]);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.whatsapp.profile'));

        $response->assertStatus(200);
        $response->assertSee('WhatsApp Business Profile & Health');
        $response->assertSee('Vanriti Official');
        $response->assertSee('High Quality');
        $response->assertSee('TIER_10K');
        $response->assertSee('Vanriti Natural Skincare');
    }

    public function test_admin_can_update_profile_and_push_to_meta(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/10987654321/whatsapp_business_profile' => Http::response(['success' => true], 200),
        ]);

        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.profile.update'), [
            'about' => 'Pure Ayurvedic wellness by Vanriti',
            'description' => 'Crafted with potent herbal botanicals.',
            'address' => 'Tonk Road, Jaipur',
            'email' => 'support@vanriti.com',
            'website' => 'https://vanriti.com',
            'vertical' => 'BEAUTY',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/10987654321/whatsapp_business_profile'
                && $request['about'] === 'Pure Ayurvedic wellness by Vanriti'
                && $request['vertical'] === 'BEAUTY'
                && in_array('https://vanriti.com', $request['websites']);
        });
    }

    public function test_admin_can_sync_profile_from_meta(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.profile.sync'));

        $response->assertRedirect(route('admin.whatsapp.profile'));
        $response->assertSessionHas('info');
    }
}
