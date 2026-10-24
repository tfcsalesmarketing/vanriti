<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\WhatsAppBotRule;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppQuickReply;
use App\Services\WhatsAppChatService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\WhatsAppBotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(WhatsAppBotSeeder::class);

        // Configure WhatsApp credentials
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_webhook_verify_token'], ['value' => 'vanriti_verify_token_123', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_bot_auto_reply_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);

        // Fake Meta Cloud API endpoints
        Http::fake([
            'graph.facebook.com/v21.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.bot_outbound_999']]], 200),
        ]);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_admin_can_view_bot_rules_and_quick_replies(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.whatsapp.bot.index', ['tab' => 'rules']));
        $response->assertStatus(200);
        $response->assertSee('Chatbot & Quick Replies');
        $response->assertSee('Order Tracking Assistant');

        $responseQr = $this->actingAs($admin, 'admin')->get(route('admin.whatsapp.bot.index', ['tab' => 'quick_replies']));
        $responseQr->assertStatus(200);
        $responseQr->assertSee('Warm Greeting & Welcome');
        $responseQr->assertSee('/greet');
    }

    public function test_admin_can_create_new_bot_rule(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.bot.rules.store'), [
            'name' => 'Store Location Query',
            'keywords' => 'location, address, where is store, shop address',
            'match_type' => 'contains',
            'action_type' => 'text',
            'response_text' => 'Visit us at Tonk Road, Jaipur!',
            'cooldown_minutes' => 10,
            'priority' => 50,
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('whatsapp_bot_rules', [
            'name' => 'Store Location Query',
            'match_type' => 'contains',
        ]);
    }

    public function test_admin_can_toggle_and_delete_bot_rule(): void
    {
        $admin = $this->superAdmin();
        $rule = WhatsAppBotRule::firstOrFail();

        $initialStatus = $rule->is_active;

        $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.bot.rules.toggle', $rule));
        $this->assertEquals(! $initialStatus, $rule->fresh()->is_active);

        $this->actingAs($admin, 'admin')->delete(route('admin.whatsapp.bot.rules.destroy', $rule));
        $this->assertDatabaseMissing('whatsapp_bot_rules', ['id' => $rule->id]);
    }

    public function test_admin_can_create_and_fetch_quick_replies_via_api(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin, 'admin')->post(route('admin.whatsapp.bot.quick-replies.store'), [
            'shortcut' => 'holistic_care',
            'title' => 'Holistic Beauty Consultation',
            'category' => 'Consultation',
            'content' => 'Hello {name}, our Ayurvedic doctor suggests our Kumkumadi oil.',
        ]);

        $this->assertDatabaseHas('whatsapp_quick_replies', ['shortcut' => 'holistic_care']);

        $apiRes = $this->actingAs($admin, 'admin')->getJson(route('admin.whatsapp.bot.quick-replies.api'));
        $apiRes->assertStatus(200);
        $apiRes->assertJsonFragment(['shortcut' => 'holistic_care']);
    }

    public function test_chatbot_intercepts_inbound_tracking_query_and_replies_with_order(): void
    {
        $user = \App\Models\User::factory()->create(['phone' => '9876500000']);

        // 1. Create a customer order
        $order = Order::create([
            'order_number' => 'ORD-987654',
            'user_id' => $user->id,
            'billing_name' => 'Aarav Sharma',
            'billing_mobile' => '9876500000',
            'billing_address_line1' => '123 Civil Lines',
            'billing_city' => 'Jaipur',
            'billing_state' => 'Rajasthan',
            'billing_pincode' => '302001',
            'billing_country' => 'India',
            'shipping_name' => 'Aarav Sharma',
            'shipping_mobile' => '9876500000',
            'shipping_address_line1' => '123 Civil Lines',
            'shipping_city' => 'Jaipur',
            'shipping_state' => 'Rajasthan',
            'shipping_pincode' => '302001',
            'shipping_country' => 'India',
            'subtotal' => 1500,
            'grand_total' => 1500,
            'payment_status' => 'paid',
            'order_status' => 'shipped',
        ]);

        $chatService = app(WhatsAppChatService::class);

        // 2. Customer sends inbound message: "Where is my order status please"
        $chatService->recordInboundMessage('919876500000', [
            'id' => 'wamid.inbound_tracking_1',
            'type' => 'text',
            'text' => ['body' => 'Where is my order status please'],
        ], 'wa_aarav', 'Aarav Sharma');

        // 3. Verify conversation created
        $convo = WhatsAppConversation::where('phone', '919876500000')->firstOrFail();

        // 4. Verify bot sent outbound message with order details
        $botMessage = $convo->messages()->where('direction', 'outbound')->latest()->first();
        $this->assertNotNull($botMessage);
        $this->assertStringContainsString('ORD-987654', $botMessage->body);
        $this->assertStringContainsString('SHIPPED', $botMessage->body);
        $this->assertTrue($botMessage->metadata['is_bot'] ?? false);

        // 5. Verify Meta Cloud API was called
        Http::assertSent(function ($request) {
            return str_contains($request['text']['body'] ?? '', 'ORD-987654');
        });
    }

    public function test_chatbot_intercepts_greeting_and_replies_with_interactive_menu(): void
    {
        $chatService = app(WhatsAppChatService::class);

        // Customer sends "hi"
        $chatService->recordInboundMessage('919999888877', [
            'id' => 'wamid.inbound_hi_1',
            'type' => 'text',
            'text' => ['body' => 'hi'],
        ], 'wa_hi', 'Pooja');

        $convo = WhatsAppConversation::where('phone', '919999888877')->firstOrFail();

        // Verify bot sent interactive button menu
        $botMessage = $convo->messages()->where('direction', 'outbound')->latest()->first();
        $this->assertNotNull($botMessage);
        $this->assertEquals('interactive', $botMessage->message_type);
        $this->assertTrue($botMessage->metadata['is_bot'] ?? false);
    }
}
