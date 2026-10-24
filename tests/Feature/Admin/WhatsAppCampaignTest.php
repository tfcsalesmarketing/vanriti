<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessWhatsAppCampaignJob;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppCloudApiService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);

        Http::fake([
            'graph.facebook.com/v21.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.template_mock_888']]], 200),
        ]);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_admin_can_send_approved_meta_template_from_inbox(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Aditi Rao',
            'window_expires_at' => now()->subHours(5), // Window expired!
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.send-template', $conversation->id), [
                'template_name' => 'order_confirmation',
                'lang_code' => 'en',
                'parameters' => ['Aditi', 'VAN-5001', 'in 3 business days'],
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'meta_message_id' => 'wamid.template_mock_888',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_view_campaigns_index(): void
    {
        $admin = $this->superAdmin();

        WhatsAppCampaign::create([
            'name' => 'Festive VIP Offer',
            'template_name' => 'promotional_offer',
            'target_segment' => 'buyers',
            'total_recipients' => 150,
            'sent_count' => 150,
            'delivered_count' => 140,
            'read_count' => 95,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.whatsapp.campaigns.index'));

        $response->assertOk();
        $response->assertSee('WhatsApp Broadcast Campaigns');
        $response->assertSee('Festive VIP Offer');
        $response->assertSee('150');
    }

    public function test_admin_can_view_campaign_create_page(): void
    {
        $admin = $this->superAdmin();

        $user = User::factory()->create(['name' => 'Cart Abandoner', 'phone' => '919876543211']);
        $cart = \App\Models\Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'session_id' => 'cart_test_123',
        ]);
        $product = \App\Models\Product::factory()->create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 599.00,
            'mrp' => 699.00,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.whatsapp.campaigns.create'));

        $response->assertOk();
        $response->assertSee('Create Broadcast Campaign');
        $response->assertSee('Select Target Audience Cohort');
        $response->assertSee('Abandoned Carts (7d)');
    }

    public function test_admin_can_create_campaign_and_target_audience(): void
    {
        $admin = $this->superAdmin();

        User::factory()->create(['name' => 'Customer A', 'phone' => '919876500001']);
        User::factory()->create(['name' => 'Customer B', 'phone' => '919876500002']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.whatsapp.campaigns.store'), [
                'name' => 'Spring Clearance 2026',
                'target_segment' => 'all',
                'template_name' => 'promotional_offer',
                'template_lang' => 'en',
                'parameters' => ['Flat 20% OFF', 'SPRING20'],
                'dispatch_now' => '0',
            ]);

        $response->assertRedirect();

        $campaign = WhatsAppCampaign::where('name', 'Spring Clearance 2026')->first();
        $this->assertNotNull($campaign);
        $this->assertGreaterThanOrEqual(2, $campaign->total_recipients);
        $this->assertEquals(2, $campaign->recipients()->count());
    }

    public function test_admin_can_send_test_message_to_phone(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.campaigns.test-send'), [
                'test_phone' => '9876543210',
                'template_name' => 'promotional_offer',
                'parameters' => ['Flat 15% OFF', 'TEST15'],
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_campaign_job_broadcasts_to_recipients_and_tracks_metrics(): void
    {
        $campaign = WhatsAppCampaign::create([
            'name' => 'Batch Test Campaign',
            'template_name' => 'promotional_offer',
            'target_segment' => 'all',
            'parameters' => ['Flat 10% OFF', 'WELCOME10'],
            'total_recipients' => 2,
            'status' => 'draft',
        ]);

        $r1 = WhatsAppCampaignRecipient::create([
            'whatsapp_campaign_id' => $campaign->id,
            'phone' => '919876500011',
            'customer_name' => 'Aarav',
            'status' => 'pending',
        ]);

        $r2 = WhatsAppCampaignRecipient::create([
            'whatsapp_campaign_id' => $campaign->id,
            'phone' => '919876500012',
            'customer_name' => 'Diya',
            'status' => 'pending',
        ]);

        $job = new ProcessWhatsAppCampaignJob($campaign);
        $job->handle(app(WhatsAppCloudApiService::class));

        $campaign->refresh();
        $this->assertEquals('completed', $campaign->status);
        $this->assertEquals(2, $campaign->sent_count);

        $r1->refresh();
        $this->assertEquals('sent', $r1->status);
        $this->assertEquals('wamid.template_mock_888', $r1->meta_message_id);
    }
}
