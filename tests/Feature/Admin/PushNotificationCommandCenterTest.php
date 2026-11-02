<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Product;
use App\Models\PushCampaign;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PushNotificationCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());
    }

    public function test_public_key_endpoint_generates_and_returns_vapid_key(): void
    {
        $response = $this->getJson(route('push.public-key'));

        $response->assertOk()
            ->assertJsonStructure(['success', 'publicKey']);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('publicKey'));
    }

    public function test_visitor_can_subscribe_to_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-token-' . uniqid();
        $payload = [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QT9AcUbVJaBoeWcvn',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'contentEncoding' => 'aes128gcm',
            'device_type' => 'mobile',
            'browser' => 'chrome',
        ];

        $response = $this->postJson(route('push.subscribe'), $payload);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => md5($endpoint),
            'device_type' => 'mobile',
            'browser' => 'chrome',
            'status' => 'active',
        ]);
    }

    public function test_visitor_can_unsubscribe_from_push_notifications(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-unsub-' . uniqid();
        PushSubscription::create([
            'endpoint' => $endpoint,
            'endpoint_hash' => md5($endpoint),
            'public_key' => 'fake-key',
            'auth_token' => 'fake-token',
            'device_type' => 'desktop',
            'browser' => 'firefox',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('push.unsubscribe'), [
            'endpoint' => $endpoint,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => md5($endpoint),
            'status' => 'unsubscribed',
        ]);
    }

    public function test_side_cart_captures_guest_phone_and_attaches_to_cart(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $product = Product::factory()->create(['status' => 'active']);

        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 299,
            'mrp' => 399,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('cart.save-whatsapp'), [
                'phone' => '9876543210',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Cart should have guest_phone set
        $cart->refresh();
        $this->assertEquals('9876543210', $cart->guest_phone);

        // User without phone should be backfilled
        $user->refresh();
        $this->assertEquals('9876543210', $user->phone);
    }

    public function test_guest_visitor_can_save_cart_to_whatsapp(): void
    {
        $sessionId = 'cart_' . \Illuminate\Support\Str::random(32);
        $product = Product::factory()->create(['status' => 'active']);

        $cart = Cart::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => $sessionId,
        ]);

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 299,
            'mrp' => 399,
        ]);

        $response = $this->withCookie(CartService::COOKIE_NAME, $sessionId)
            ->post(route('cart.save-whatsapp'), [
                'phone' => '9876543210',
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $cart->refresh();
        $this->assertEquals('9876543210', $cart->guest_phone);
    }

    public function test_admin_can_view_push_notifications_command_center(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.push.index'));

        $response->assertOk();
        $response->assertSee('Push Notifications Command Center');
        $response->assertSee('Broadcast Notification to Customers');
        $response->assertSee('Destination Link');
    }

    public function test_admin_can_broadcast_push_campaign(): void
    {
        // Create 2 active subscribers
        PushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sub1',
            'endpoint_hash' => md5('https://fcm.googleapis.com/fcm/send/sub1'),
            'public_key' => 'fake-key-1',
            'auth_token' => 'fake-token-1',
            'device_type' => 'mobile',
            'browser' => 'chrome',
            'status' => 'active',
        ]);

        PushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sub2',
            'endpoint_hash' => md5('https://fcm.googleapis.com/fcm/send/sub2'),
            'public_key' => 'fake-key-2',
            'auth_token' => 'fake-token-2',
            'device_type' => 'desktop',
            'browser' => 'safari',
            'status' => 'active',
        ]);

        $payload = [
            'title' => 'Flash Sale: 20% Off All Chyawanprash!',
            'body' => 'Handcrafted Vedic Chyawanprash at flat 20% discount today only.',
            'url' => 'https://vanriti.com/shop?offer=flash20',
            'target_audience' => 'all',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.push.send'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('push_campaigns', [
            'title' => 'Flash Sale: 20% Off All Chyawanprash!',
            'url' => 'https://vanriti.com/shop?offer=flash20',
            'target_audience' => 'all',
            'total_targeted' => 2,
        ]);
    }

    public function test_admin_can_update_auto_push_settings(): void
    {
        $payload = [
            'push_abandoned_cart_enabled' => '1',
            'push_abandoned_cart_delay_minutes' => '60',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.push.settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('1', Setting::where('key', 'push_abandoned_cart_enabled')->value('value'));
        $this->assertEquals('60', Setting::where('key', 'push_abandoned_cart_delay_minutes')->value('value'));
    }

    public function test_admin_can_delete_individual_push_campaign(): void
    {
        $campaign = PushCampaign::create([
            'title' => 'Old Flash Sale to Delete',
            'body' => 'Limited stock chyawanprash offer.',
            'url' => 'https://vanriti.com/shop',
            'target_audience' => 'all',
            'status' => 'completed',
            'total_targeted' => 10,
            'total_sent' => 10,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.push.campaigns.destroy', $campaign));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('push_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_admin_can_cleanup_older_push_campaigns(): void
    {
        // Create 1 old campaign (40 days ago) and 1 recent campaign (yesterday)
        $oldCampaign = PushCampaign::create([
            'title' => 'Campaign from last month',
            'body' => 'Older notification',
            'url' => 'https://vanriti.com/shop',
            'target_audience' => 'all',
            'status' => 'completed',
        ]);
        PushCampaign::where('id', $oldCampaign->id)->update(['created_at' => now()->subDays(40)]);

        $recentCampaign = PushCampaign::create([
            'title' => 'Recent yesterday campaign',
            'body' => 'Recent notification',
            'url' => 'https://vanriti.com/shop',
            'target_audience' => 'all',
            'status' => 'completed',
        ]);
        PushCampaign::where('id', $recentCampaign->id)->update(['created_at' => now()->subDay()]);

        // Cleanup campaigns older than 30 days
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.push.campaigns.cleanup'), ['scope' => 'older_30']);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('push_campaigns', ['id' => $oldCampaign->id]);
        $this->assertDatabaseHas('push_campaigns', ['id' => $recentCampaign->id]);

        // Cleanup all campaigns
        $responseAll = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.push.campaigns.cleanup'), ['scope' => 'all']);

        $responseAll->assertRedirect();
        $this->assertDatabaseMissing('push_campaigns', ['id' => $recentCampaign->id]);
    }

    public function test_admin_can_send_test_notification_to_specific_subscription_via_ajax(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test_admin_device_' . \Illuminate\Support\Str::random(16);
        $sub = PushSubscription::create([
            'endpoint' => $endpoint,
            'endpoint_hash' => md5($endpoint),
            'public_key' => 'BM5_fake_key_for_testing_purposes_only',
            'auth_token' => 'fake_auth_token',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'status' => 'active',
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.push.test'), [
                'title' => 'Flash Sale Test',
                'body' => 'Testing test push alert message',
                'url' => '/shop',
                'subscription_id' => $sub->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_admin_send_test_notification_returns_error_when_no_subscribers(): void
    {
        PushSubscription::query()->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.push.test'), [
                'title' => 'Flash Sale Test',
                'body' => 'Testing test push alert message',
                'url' => 'https://vanriti.com/shop',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_abandoned_cart_push_artisan_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('push:send-abandoned-cart-reminders');
        $this->assertEquals(0, $exitCode);
    }
}
