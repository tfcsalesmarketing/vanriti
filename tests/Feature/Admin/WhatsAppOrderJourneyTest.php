<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendWhatsAppOrderStatusNotification;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsAppCloudApiService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class WhatsAppOrderJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $user;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        // WhatsApp credentials
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_shipping_notifications_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);

        $this->admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        $this->user = User::factory()->create([
            'phone' => '9876543210',
            'name' => 'John Doe',
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-WA-1001',
            'user_id' => $this->user->id,
            'billing_name' => 'John Doe',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '123 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560001',
            'billing_country' => 'India',
            'shipping_name' => 'John Doe',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '123 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560001',
            'shipping_country' => 'India',
            'subtotal' => 1299.00,
            'grand_total' => 1299.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
        ]);
    }

    public function test_admin_order_show_renders_whatsapp_customer_modal(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $this->order));

        $response->assertStatus(200);
        $response->assertSee('WhatsApp Customer');
        $response->assertSee('sendWhatsAppModal');
        $response->assertSee('Select Order Journey Template');
        $response->assertSee('order_dispatched');
    }

    public function test_admin_can_send_whatsapp_template_from_order_details(): void
    {
        $mockApi = Mockery::mock(WhatsAppCloudApiService::class);
        $mockApi->shouldReceive('sendTemplateMessage')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRT',
            ]);
        $this->app->instance(WhatsAppCloudApiService::class, $mockApi);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.send-whatsapp', $this->order), [
                'send_type' => 'template',
                'template_name' => 'order_dispatched',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_messages', [
            'direction' => 'outbound',
            'status' => 'sent',
            'meta_message_id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRT',
        ]);

        $this->order->refresh();
        $this->assertStringContainsString('WhatsApp template \'order_dispatched\' sent', $this->order->internal_notes);
    }

    public function test_admin_cannot_send_direct_message_when_window_closed(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.send-whatsapp', $this->order), [
                'send_type' => 'custom_text',
                'custom_message' => 'Hello John, your order is being prepared!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_can_send_direct_message_when_window_open(): void
    {
        // Open the 24h window
        $conversation = WhatsAppConversation::findOrCreateForPhone('9876543210', 'John Doe');
        $conversation->update([
            'window_expires_at' => now()->addHours(12),
        ]);

        $mockApi = Mockery::mock(WhatsAppCloudApiService::class);
        $mockApi->shouldReceive('sendTextMessage')
            ->once()
            ->with(Mockery::any(), 'Hello John, your order is being prepared!')
            ->andReturn([
                'success' => true,
                'message_id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSRT2',
            ]);
        $this->app->instance(WhatsAppCloudApiService::class, $mockApi);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.send-whatsapp', $this->order), [
                'send_type' => 'custom_text',
                'custom_message' => 'Hello John, your order is being prepared!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_messages', [
            'direction' => 'outbound',
            'body' => 'Hello John, your order is being prepared!',
        ]);
    }

    public function test_order_status_change_to_shipped_dispatches_notification_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.status', $this->order), [
                'order_status' => 'shipped',
                'description' => 'Shipped via Blue Dart',
            ]);

        $response->assertRedirect();
        Queue::assertPushed(SendWhatsAppOrderStatusNotification::class, function ($job) {
            return $job->order->id === $this->order->id && $job->status === 'shipped';
        });
    }

    public function test_shipping_notification_job_sends_via_meta_api_and_sets_atomic_claim(): void
    {
        Shipment::create([
            'order_id' => $this->order->id,
            'courier' => 'Blue Dart',
            'awb_number' => 'BLUEDART-889922',
            'status' => 'shipped',
        ]);

        $mockApi = Mockery::mock(WhatsAppCloudApiService::class);
        $mockApi->shouldReceive('sendTemplateMessage')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'wamid.SHIP_12345',
            ]);
        $this->app->instance(WhatsAppCloudApiService::class, $mockApi);

        $job = new SendWhatsAppOrderStatusNotification($this->order, 'shipped');
        $job->handle($mockApi, app(\App\Services\WhatsAppOtpService::class));

        $this->order->refresh();
        $this->assertNotNull($this->order->whatsapp_shipped_sent_at);

        $this->assertDatabaseHas('whatsapp_messages', [
            'direction' => 'outbound',
            'meta_message_id' => 'wamid.SHIP_12345',
        ]);

        // Second execution should skip due to atomic claim
        $job2 = new SendWhatsAppOrderStatusNotification($this->order, 'shipped');
        $job2->handle($mockApi, app(\App\Services\WhatsAppOtpService::class));
    }
}
