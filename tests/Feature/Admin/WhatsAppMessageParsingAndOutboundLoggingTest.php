<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendWhatsAppOrderConfirmation;
use App\Jobs\SendWhatsAppOrderStatusNotification;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\CodConfirmationService;
use App\Services\WhatsAppChatService;
use App\Services\WhatsAppOtpService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppMessageParsingAndOutboundLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_webhook_verify_token'], ['value' => 'vanriti_verify_token_123', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);
        Setting::updateOrCreate(['key' => 'whatsapp_otp_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_order_notifications_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_shipping_notifications_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_template_name'], ['value' => 'otp_template', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_welcome_template_name'], ['value' => 'vanriti_welcome', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_order_template_name'], ['value' => 'order_confirmation', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_dispatch_template_name'], ['value' => 'order_dispatched', 'group' => 'whatsapp', 'type' => 'text']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.fake_123456']]], 200),
        ]);

        $this->chatService = app(WhatsAppChatService::class);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_inbound_button_click_parses_action_and_order_number(): void
    {
        $phone = '919664023036';
        $metaMsg = [
            'from' => $phone,
            'id' => 'wamid.btn_test_001',
            'type' => 'button',
            'button' => [
                'text' => 'Confirm Order',
                'payload' => 'COD_CONFIRM_VAN-2026-000028',
            ],
        ];

        $message = $this->chatService->recordInboundMessage($phone, $metaMsg, $phone, 'Lakshita Galani');

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertEquals('✅ Confirmed Order (#VAN-2026-000028)', $message->body);
        $this->assertEquals('✅ Confirmed Order (#VAN-2026-000028)', $message->display_body);
        $this->assertNotEquals('[Message: button]', $message->body);
        $this->assertEquals('button', $message->message_type);

        $convo = WhatsAppConversation::where('phone', $phone)->first();
        $this->assertNotNull($convo);
        $this->assertStringContainsString('✅ Confirmed Order', $convo->last_message_preview);
    }

    public function test_inbound_interactive_button_reply_parses_cancel_action(): void
    {
        $phone = '919876543210';
        $metaMsg = [
            'from' => $phone,
            'id' => 'wamid.interactive_002',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => [
                    'id' => 'COD_CANCEL_VAN-2026-000029',
                    'title' => 'Cancel Order',
                ],
            ],
        ];

        $message = $this->chatService->recordInboundMessage($phone, $metaMsg, $phone, 'John Doe');

        $this->assertEquals('❌ Cancelled Order (#VAN-2026-000029)', $message->body);
        $this->assertEquals('❌ Cancelled Order (#VAN-2026-000029)', $message->display_body);
        $this->assertEquals('interactive', $message->message_type);
    }

    public function test_inbound_ndr_reattempt_button_parses_delivery_action(): void
    {
        $phone = '919876543219';
        $metaMsg = [
            'from' => $phone,
            'id' => 'wamid.btn_reattempt_001',
            'type' => 'button',
            'button' => [
                'text' => 'Reattempt Delivery',
                'payload' => 'NDR_REATTEMPT_VAN-2026-000030',
            ],
        ];

        $message = $this->chatService->recordInboundMessage($phone, $metaMsg, $phone, 'Rohit Mehra');

        $this->assertEquals('🚚 Reattempt Delivery (#VAN-2026-000030)', $message->body);
        $this->assertEquals('🚚 Reattempt Delivery (#VAN-2026-000030)', $message->display_body);
    }

    public function test_inbound_unsupported_type_parses_cleanly_not_raw_developer_tag(): void
    {
        $phone = '916364853632';
        $metaMsg = [
            'from' => $phone,
            'id' => 'wamid.unsupported_003',
            'type' => 'unsupported',
            'errors' => [
                [
                    'code' => 131051,
                    'title' => 'Unsupported message type',
                    'message' => 'Message type is not supported',
                ],
            ],
        ];

        $message = $this->chatService->recordInboundMessage($phone, $metaMsg, $phone, 'Customer 3632');

        $this->assertNotEquals('[Message: unsupported]', $message->body);
        $this->assertStringContainsString('Unsupported message format', $message->body);
        $this->assertEquals('unsupported', $message->message_type);
        $this->assertStringContainsString('Unsupported message format', $message->display_body);
    }

    public function test_historical_placeholder_messages_display_properly_via_accessor(): void
    {
        $convo = WhatsAppConversation::create([
            'phone' => '919811122334',
            'contact_name' => 'Legacy Customer',
            'channel' => 'vanriti',
            'status' => 'active',
        ]);

        // Historical raw [Message: button] with metadata
        $msg1 = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $convo->id,
            'direction' => 'inbound',
            'message_type' => 'button',
            'body' => '[Message: button]',
            'metadata' => [
                'payload' => 'COD_CONFIRM_VAN-2026-000031',
                'text' => 'Confirm Order',
            ],
            'meta_message_id' => 'wamid.hist_01',
            'status' => 'delivered',
        ]);
        $this->assertEquals('✅ Confirmed Order (#VAN-2026-000031)', $msg1->display_body);

        // Historical raw [Message: unsupported]
        $msg2 = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $convo->id,
            'direction' => 'inbound',
            'message_type' => 'unsupported',
            'body' => '[Message: unsupported]',
            'meta_message_id' => 'wamid.hist_02',
            'status' => 'delivered',
        ]);
        $this->assertEquals('ℹ️ [Unsupported message format (e.g. Poll, Live Location, or Flow Reply)]', $msg2->display_body);
    }

    public function test_inbound_location_contacts_reaction_parse_properly(): void
    {
        // 1. Location
        $locMsg = [
            'id' => 'wamid.loc_001',
            'type' => 'location',
            'location' => [
                'latitude' => 28.5355,
                'longitude' => 77.3910,
                'name' => 'Noida Sector 62',
            ],
        ];
        $msg1 = $this->chatService->recordInboundMessage('919876543211', $locMsg);
        $this->assertEquals('📍 Location: Noida Sector 62', $msg1->body);

        // 2. Contact
        $contactMsg = [
            'id' => 'wamid.contact_002',
            'type' => 'contacts',
            'contacts' => [
                [
                    'name' => ['formatted_name' => 'Aarav Sharma'],
                    'phones' => [['phone' => '+919999988888']],
                ],
            ],
        ];
        $msg2 = $this->chatService->recordInboundMessage('919876543212', $contactMsg);
        $this->assertEquals('👤 Contact: Aarav Sharma (+919999988888)', $msg2->body);

        // 3. Reaction
        $reactionMsg = [
            'id' => 'wamid.react_003',
            'type' => 'reaction',
            'reaction' => [
                'message_id' => 'wamid.outbound_123',
                'emoji' => '🌿',
            ],
        ];
        $msg3 = $this->chatService->recordInboundMessage('919876543213', $reactionMsg);
        $this->assertEquals('Reacted 🌿', $msg3->body);
    }

    public function test_record_outbound_system_message_creates_message_and_updates_convo(): void
    {
        $phone = '919811122233';
        $body = 'Your Vanriti package is being prepared with fresh herbs.';

        $msg = $this->chatService->recordOutboundSystemMessage(
            phone: $phone,
            body: $body,
            messageType: 'text',
            metaMessageId: 'wamid.out_test_999',
            metadata: ['source' => 'system_test'],
            channel: 'vanriti',
            contactName: 'Pooja Varma'
        );

        $this->assertInstanceOf(WhatsAppMessage::class, $msg);
        $this->assertEquals('outbound', $msg->direction);
        $this->assertEquals($body, $msg->body);

        $convo = WhatsAppConversation::where('phone', $phone)->first();
        $this->assertNotNull($convo);
        $this->assertEquals('Pooja Varma', $convo->contact_name);
        $this->assertEquals('Your Vanriti package is being prepared with fresh herbs.', $convo->last_message_preview);
    }

    public function test_cod_confirmation_service_dispatch_and_confirm_records_outbound_messages(): void
    {
        $user = User::factory()->create(['phone' => '919664023036']);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'VAN-2026-TEST01',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'subtotal' => 499.00,
            'grand_total' => 499.00,
            'billing_name' => 'Lakshita Galani',
            'billing_mobile' => '919664023036',
            'billing_address_line1' => '123 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560001',
            'billing_country' => 'India',
            'shipping_name' => 'Lakshita Galani',
            'shipping_mobile' => '919664023036',
            'shipping_address_line1' => '123 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560001',
            'shipping_country' => 'India',
        ]);

        $codService = app(CodConfirmationService::class);

        // 1. Dispatch prompt
        $res = $codService->dispatchPrompt($order);
        $this->assertTrue($res['success']);

        $convo = WhatsAppConversation::where('phone', '919664023036')->first();
        $this->assertNotNull($convo);

        $promptMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->where('metadata->type', 'cod_prompt')
            ->first();
        $this->assertNotNull($promptMsg);
        $this->assertStringContainsString('COD Confirmation Prompt', $promptMsg->body);

        // 2. Customer confirms
        $confirmResult = $codService->handleCustomerConfirm($order->order_number, '919664023036');
        $this->assertTrue($confirmResult);

        $confirmReplyMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->where('body', 'like', '%*CONFIRMED*%')
            ->first();
        $this->assertNotNull($confirmReplyMsg);
        $this->assertStringContainsString('*CONFIRMED*', $confirmReplyMsg->body);
    }

    public function test_whatsapp_order_status_notification_job_records_outbound_in_chat_stream(): void
    {
        $user = User::factory()->create(['phone' => '919876543220']);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'VAN-2026-SHIP01',
            'payment_method' => 'prepaid',
            'payment_status' => 'paid',
            'order_status' => 'shipped',
            'subtotal' => 999.00,
            'grand_total' => 999.00,
            'billing_name' => 'Kavita Das',
            'billing_mobile' => '919876543220',
            'billing_address_line1' => '45 Marine Drive',
            'billing_city' => 'Mumbai',
            'billing_state' => 'Maharashtra',
            'billing_pincode' => '400020',
            'billing_country' => 'India',
            'shipping_name' => 'Kavita Das',
            'shipping_mobile' => '919876543220',
            'shipping_address_line1' => '45 Marine Drive',
            'shipping_city' => 'Mumbai',
            'shipping_state' => 'Maharashtra',
            'shipping_pincode' => '400020',
            'shipping_country' => 'India',
        ]);

        $order->shipments()->create([
            'courier' => 'Ekart Express',
            'awb_number' => 'EKART998877',
            'status' => 'shipped',
        ]);

        SendWhatsAppOrderStatusNotification::dispatchSync($order, 'shipped');

        $convo = WhatsAppConversation::where('phone', '919876543220')->first();
        $this->assertNotNull($convo);

        $shipMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->where('metadata->event', 'shipped')
            ->first();

        $this->assertNotNull($shipMsg);
        $this->assertStringContainsString('dispatched via Ekart Express', $shipMsg->body);
        $this->assertStringContainsString('EKART998877', $shipMsg->body);
        $this->assertStringContainsString('VAN-2026-SHIP01', $shipMsg->body);
    }

    public function test_whatsapp_welcome_message_records_in_conversation_stream(): void
    {
        $phone = '919793039774';
        $name = 'Rekha Singh';

        $otpService = app(WhatsAppOtpService::class);
        $res = $otpService->sendWelcome($phone, $name);

        $this->assertEquals('1', $res['result'] ?? null);

        $convo = WhatsAppConversation::where('phone', $phone)->first();
        $this->assertNotNull($convo);

        $welcomeMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->first();
        $this->assertNotNull($welcomeMsg);
        $this->assertStringContainsString('Welcome to VANRITI', $welcomeMsg->body);
    }

    public function test_whatsapp_order_confirmation_records_in_conversation_stream(): void
    {
        $phone = '919755823406';
        $name = 'Keshu';

        $otpService = app(WhatsAppOtpService::class);
        $res = $otpService->sendOrderConfirmation(
            $phone,
            $name,
            'VAN-2026-TEST02',
            '2026-11-25',
            'https://vanriti.com/account/orders'
        );

        $this->assertEquals('1', $res['result'] ?? null);

        $convo = WhatsAppConversation::where('phone', $phone)->first();
        $this->assertNotNull($convo);

        $orderConfMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->where('metadata->is_order_confirmation', true)
            ->first();
        $this->assertNotNull($orderConfMsg);
        $this->assertStringContainsString('VAN-2026-TEST02', $orderConfMsg->body);
    }

    public function test_send_whatsapp_order_confirmation_job_ensures_outbound_message_recorded(): void
    {
        $user = User::factory()->create(['phone' => '919822334455']);
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'VAN-2026-CONF01',
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'subtotal' => 1499.00,
            'grand_total' => 1499.00,
            'billing_name' => 'Meera Patel',
            'billing_mobile' => '919822334455',
            'billing_address_line1' => '10 Ring Road',
            'billing_city' => 'Ahmedabad',
            'billing_state' => 'Gujarat',
            'billing_pincode' => '380001',
            'billing_country' => 'India',
            'shipping_name' => 'Meera Patel',
            'shipping_mobile' => '919822334455',
            'shipping_address_line1' => '10 Ring Road',
            'shipping_city' => 'Ahmedabad',
            'shipping_state' => 'Gujarat',
            'shipping_pincode' => '380001',
            'shipping_country' => 'India',
        ]);

        SendWhatsAppOrderConfirmation::dispatchSync($order);

        $convo = WhatsAppConversation::where('phone', '919822334455')->first();
        $this->assertNotNull($convo);

        $confMsg = WhatsAppMessage::where('whatsapp_conversation_id', $convo->id)
            ->where('direction', 'outbound')
            ->where('metadata->order_number', 'VAN-2026-CONF01')
            ->first();

        $this->assertNotNull($confMsg);
        $this->assertStringContainsString('VAN-2026-CONF01', $confMsg->body);
    }

    public function test_inbox_ui_renders_button_message_with_icon_badge(): void
    {
        $admin = $this->superAdmin();

        $convo = WhatsAppConversation::create([
            'phone' => '919664023036',
            'contact_name' => 'Lakshita Galani',
            'channel' => 'vanriti',
            'status' => 'active',
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $convo->id,
            'direction' => 'inbound',
            'message_type' => 'button',
            'body' => '✅ Confirmed Order (#VAN-2026-000028)',
            'meta_message_id' => 'wamid.btn_ui_001',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.whatsapp.inbox', ['conversation' => $convo->id]));

        $response->assertOk();
        $response->assertSee('✅ Confirmed Order (#VAN-2026-000028)');
        $response->assertDontSeeText('[Message: button]');
        $response->assertSee('bi-hand-index-thumb');
    }
}
