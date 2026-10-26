<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_service_generates_and_stores_standard_a4_pdf(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Kumkumadi Facial Oil',
            'selling_price' => 999.00,
            'weight' => 100,
        ]);

        $order = Order::create([
            'order_number' => 'VR-INV-TEST-01',
            'user_id' => $user->id,
            'billing_name' => 'Aarav Sharma',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Plot 42, Sector 15',
            'billing_city' => 'Faridabad',
            'billing_state' => 'Haryana',
            'billing_pincode' => '121007',
            'shipping_name' => 'Aarav Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Plot 42, Sector 15',
            'shipping_city' => 'Faridabad',
            'shipping_state' => 'Haryana',
            'shipping_pincode' => '121007',
            'subtotal' => 999.00,
            'taxable_amount' => 951.43,
            'tax_amount' => 47.57,
            'cgst_amount' => 23.78,
            'sgst_amount' => 23.79,
            'grand_total' => 999.00,
            'amount_paid' => 999.00,
            'amount_due' => 0.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 999.00,
            'total_price' => 999.00,
        ]);

        $service = app(InvoiceService::class);
        $url = $service->generateAndStorePdf($order);

        $order->refresh();

        $this->assertNotEmpty($url);
        $this->assertEquals('INV-VR-INV-TEST-01', $order->invoice_number);
        $this->assertNotNull($order->invoice_url);
        $this->assertNotNull($order->invoice_generated_at);
        $this->assertEquals('invoices/INV-VR-INV-TEST-01.pdf', $order->invoice_pdf_path);
    }

    public function test_user_can_view_and_download_pdf_invoice(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $order = Order::create([
            'order_number' => 'VR-INV-TEST-02',
            'user_id' => $user->id,
            'billing_name' => 'Test User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Test Address',
            'billing_city' => 'Delhi',
            'billing_state' => 'Delhi',
            'billing_pincode' => '110001',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Test Address',
            'shipping_city' => 'Delhi',
            'shipping_state' => 'Delhi',
            'shipping_pincode' => '110001',
            'subtotal' => 500.00,
            'grand_total' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'processing',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 500.00,
            'total_price' => 500.00,
        ]);

        // Stream inline view
        $response = $this->actingAs($user)->get(route('account.order.invoice', $order));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('inline; filename="INV-VR-INV-TEST-02.pdf"', $response->headers->get('content-disposition'));

        // Force download
        $downloadResponse = $this->actingAs($user)->get(route('account.order.invoice', ['order' => $order->id, 'download' => 1]));
        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment; filename="INV-VR-INV-TEST-02.pdf"', $downloadResponse->headers->get('content-disposition'));

        // HTML format parameter
        $htmlResponse = $this->actingAs($user)->get(route('account.order.invoice', ['order' => $order->id, 'format' => 'html']));
        $htmlResponse->assertStatus(200);
        $htmlResponse->assertSee('TAX INVOICE');
    }
}
