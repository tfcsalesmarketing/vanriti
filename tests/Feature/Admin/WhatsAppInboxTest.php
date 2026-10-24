<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
        Storage::fake('public');

        // Configure WhatsApp credentials
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_webhook_verify_token'], ['value' => 'vanriti_verify_token_123', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);

        // Fake Meta Cloud API endpoints
        Http::fake([
            'graph.facebook.com/v21.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.outbound_123']]], 200),
            'graph.facebook.com/v21.0/10987654321/media' => Http::response(['id' => 'meta_media_id_999'], 200),
        ]);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_admin_can_view_whatsapp_inbox_workspace(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Kavita Roy',
            'unread_count' => 2,
            'last_message_at' => now(),
            'last_message_preview' => 'Hello VANRITI!',
            'window_expires_at' => now()->addHours(18),
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'body' => 'Hello VANRITI!',
            'meta_message_id' => 'wamid.msg_test_01',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.whatsapp.inbox'));

        $response->assertOk();
        $response->assertSee('WhatsApp Inbox');
        $response->assertSee('Kavita Roy');
        $response->assertSee('Hello VANRITI!');
        $response->assertSee('active windows (24h)');
    }

    public function test_webhook_inbound_message_records_conversation_and_refreshes_window(): void
    {
        $phone = '919876543222';
        $msgId = 'wamid.inbound_webhook_999';

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'contacts' => [
                                    [
                                        'wa_id' => $phone,
                                        'profile' => ['name' => 'Rahul Verma'],
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'id' => $msgId,
                                        'from' => $phone,
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'I would like to know more about the kumkumadi oil.'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/whatsapp/webhook', $payload);
        $response->assertOk();

        // Verify conversation is created with 24h window
        $conversation = WhatsAppConversation::where('phone', $phone)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('Rahul Verma', $conversation->contact_name);
        $this->assertEquals(1, $conversation->unread_count);
        $this->assertTrue($conversation->isWindowOpen());
        $this->assertStringContainsString('kumkumadi', $conversation->last_message_preview);

        // Verify message record
        $msg = WhatsAppMessage::where('meta_message_id', $msgId)->first();
        $this->assertNotNull($msg);
        $this->assertEquals('inbound', $msg->direction);
        $this->assertEquals('I would like to know more about the kumkumadi oil.', $msg->body);
        $this->assertEquals($conversation->id, $msg->whatsapp_conversation_id);
    }

    public function test_webhook_status_delivery_updates_message_status_to_read(): void
    {
        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Pooja',
        ]);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => 'Your order is confirmed!',
            'meta_message_id' => 'wamid.status_test_777',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid.status_test_777',
                                        'status' => 'read',
                                        'timestamp' => (string) time(),
                                        'recipient_id' => '919876543210',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/whatsapp/webhook', $payload);
        $response->assertOk();

        $message->refresh();
        $this->assertEquals('read', $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_admin_can_send_text_message_to_customer(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Meera',
            'window_expires_at' => now()->addHours(12),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.send', $conversation->id), [
                'message' => 'Hello Meera, our Kumkumadi Face Oil is now back in stock!',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'body' => 'Hello Meera, our Kumkumadi Face Oil is now back in stock!',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_send_media_attachment_to_customer(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Anil',
            'window_expires_at' => now()->addHours(20),
        ]);

        $fakeFile = UploadedFile::fake()->image('neem_product.jpg', 600, 600);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.whatsapp.inbox.send-media', $conversation->id), [
                'file' => $fakeFile,
                'caption' => 'Here is our pure organic neem oil certificate',
            ], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $msg = WhatsAppMessage::where('whatsapp_conversation_id', $conversation->id)
            ->where('message_type', 'image')
            ->first();

        $this->assertNotNull($msg);
        $this->assertEquals('Here is our pure organic neem oil certificate', $msg->body);
        $this->assertEquals('sent', $msg->status);
        $this->assertNotNull($msg->media_url);
    }

    public function test_admin_can_poll_new_messages_and_marks_as_read(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Sunita',
            'unread_count' => 1,
            'window_expires_at' => now()->addHours(15),
        ]);

        $msg1 = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'body' => 'Old message',
        ]);

        $msg2 = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'body' => 'New incoming question!',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.whatsapp.inbox.poll', [
                'conversation' => $conversation->id,
                'after_id' => $msg1->id,
            ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'messages');
        $response->assertJsonPath('messages.0.body', 'New incoming question!');

        // Conversation should be marked as read
        $this->assertEquals(0, $conversation->fresh()->unread_count);
    }

    public function test_admin_can_assign_conversation_to_agent(): void
    {
        $admin = $this->superAdmin();
        $agent = Admin::factory()->create(['name' => 'Agent Sneha']);

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Kavita',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.assign', $conversation), [
                'admin_id' => $agent->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('assigned_to', $agent->id);
        $response->assertJsonPath('agent_name', 'Agent Sneha');
        $this->assertEquals($agent->id, $conversation->fresh()->assigned_to);

        // Unassign
        $unassignRes = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.assign', $conversation), [
                'admin_id' => null,
            ]);
        $unassignRes->assertOk();
        $this->assertNull($conversation->fresh()->assigned_to);
    }

    public function test_admin_can_update_conversation_status(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Kavita',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.status', $conversation), [
                'status' => 'resolved',
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'resolved');
        $this->assertEquals('resolved', $conversation->fresh()->status);
    }

    public function test_admin_can_add_internal_staff_note(): void
    {
        $admin = $this->superAdmin();

        $conversation = WhatsAppConversation::create([
            'phone' => '919876543210',
            'contact_name' => 'Kavita',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp.inbox.note', $conversation), [
                'note' => 'Customer called regarding parcel delivery before noon.',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('chat_message.message_type', 'note');
        $response->assertJsonPath('chat_message.direction', 'internal');

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'internal',
            'message_type' => 'note',
            'body' => 'Customer called regarding parcel delivery before noon.',
            'sender_admin_id' => $admin->id,
        ]);
    }
}
