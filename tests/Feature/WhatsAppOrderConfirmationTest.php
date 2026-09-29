<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppOrderConfirmation;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WhatsAppOrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Notification::fake();

        Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp Order Confirmations', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '123456789', 'group' => 'whatsapp', 'label' => 'Phone Number ID', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_order_template_name'], ['value' => 'order_confirmation', 'group' => 'whatsapp', 'label' => 'WhatsApp Order Confirmation Template Name', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('fake-test-token'),
            'group' => 'whatsapp',
            'label' => 'WhatsApp Access Token',
            'type' => 'password',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.confirmed1']]], 200),
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZ6G0001',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-WH-0001',
            ], 200),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function addressData(string $paymentMethod = 'cod'): array
    {
        return [
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => $paymentMethod,
            'notes' => null,
        ];
    }

    protected function seedProduct(): Product
    {
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        Inventory::create([
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => $product->stock,
            'low_stock_threshold' => $product->low_stock_threshold ?? 5,
        ]);

        return $product;
    }

    protected function whatsappSends(): int
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))
            ->count();
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => Crypt::encryptString('rzp_test_secret')]);
    }

    protected function placeOrder(User $user, Product $product, string $paymentMethod): Order
    {
        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), $this->addressData($paymentMethod))
            ->assertRedirect();

        return Order::where('user_id', $user->id)->firstOrFail();
    }

    protected function placeRazorpayOrder(User $user, Product $product): Order
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk()
            ->assertJson(['success' => true]);

        return Order::where('user_id', $user->id)->firstOrFail();
    }

    public function test_cod_checkout_sends_confirmation_with_dashboard_order_link(): void
    {
        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $this->assertNotNull($order->whatsapp_confirmation_sent_at);

        Http::assertSent(function ($request) use ($order) {
            $data = $request->data();
            $template = $data['template'] ?? [];

            $this->assertSame('order_confirmation', $template['name'] ?? null);
            $this->assertSame('919876543210', $data['to'] ?? null);

            $body = collect($template['components'] ?? [])->firstWhere('type', 'body');
            $this->assertSame('Aarav', $body['parameters'][0]['text'] ?? null);
            $this->assertSame($order->order_number, $body['parameters'][1]['text'] ?? null);

            $button = collect($template['components'] ?? [])->firstWhere('type', 'button');
            $this->assertSame(
                route('account.order', $order),
                $button['parameters'][0]['url'] ?? null
            );

            return true;
        });
    }

    public function test_razorpay_checkout_sends_nothing_before_payment_is_verified(): void
    {
        $this->enableRazorpay();
        $user = User::factory()->create();
        $order = $this->placeRazorpayOrder($user, $this->seedProduct());

        $this->assertNull($order->fresh()->whatsapp_confirmation_sent_at);
        $this->assertSame(0, $this->whatsappSends());
    }

    public function test_razorpay_payment_confirmation_sends_the_message(): void
    {
        $this->enableRazorpay();
        $user = User::factory()->create();
        $order = $this->placeRazorpayOrder($user, $this->seedProduct());

        $signature = hash_hmac('sha256', 'order_EZ6G0001'.'|'.'pay_TESTL1', 'rzp_test_secret');

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTL1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertNotNull($order->fresh()->whatsapp_confirmation_sent_at);
        $this->assertSame(1, $this->whatsappSends());
    }

    public function test_second_trigger_does_not_send_a_duplicate_message(): void
    {
        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $firstSentAt = $order->fresh()->whatsapp_confirmation_sent_at;
        $this->assertNotNull($firstSentAt);

        // The Razorpay callback and its webhook can both settle one payment.
        SendWhatsAppOrderConfirmation::dispatchSync($order->fresh());

        $this->assertSame(1, $this->whatsappSends());
        $this->assertEquals($firstSentAt, $order->fresh()->whatsapp_confirmation_sent_at);
    }

    public function test_failed_send_releases_the_claim_so_the_other_trigger_can_retry(): void
    {
        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $this->assertNotNull($order->fresh()->whatsapp_confirmation_sent_at);

        // An unconfigured template name makes the send fail before any HTTP
        // call, which must hand the claim back to the other trigger point.
        Setting::updateOrCreate(['key' => 'whatsapp_order_template_name'], ['value' => '']);
        $order->update(['whatsapp_confirmation_sent_at' => null]);

        SendWhatsAppOrderConfirmation::dispatchSync($order->fresh());

        $this->assertNull($order->fresh()->whatsapp_confirmation_sent_at);
    }

    public function test_order_confirmation_works_with_whatsapp_otp_disabled(): void
    {
        // Regression: the order confirmation used to be gated by the OTP switch
        // and the OTP template name, so no message was ever delivered.
        Setting::updateOrCreate(['key' => 'whatsapp_otp_enabled'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'whatsapp_template_name'], ['value' => '']);

        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $this->assertNotNull($order->fresh()->whatsapp_confirmation_sent_at);
        $this->assertSame(1, $this->whatsappSends());
    }

    public function test_nothing_is_sent_when_order_confirmations_are_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '0']);

        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $this->assertNull($order->fresh()->whatsapp_confirmation_sent_at);
        $this->assertSame(0, $this->whatsappSends());
    }

    public function test_message_goes_to_the_order_mobile_not_the_account_phone(): void
    {
        $user = User::factory()->create(['phone' => '9000000000']);
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        Http::assertSent(function ($request) {
            $this->assertSame('919876543210', $request->data()['to'] ?? null);

            return true;
        });

        $this->assertNotSame('919000000000', $order->fresh()->shipping_mobile);
    }

    public function test_missing_credentials_never_block_checkout(): void
    {
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '']);

        $user = User::factory()->create();
        $order = $this->placeOrder($user, $this->seedProduct(), 'cod');

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, $this->whatsappSends());
    }
}
