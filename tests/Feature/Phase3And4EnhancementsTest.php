<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotification;
use App\Services\NdrService;
use App\Services\StockNotificationService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class Phase3And4EnhancementsTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    public function test_customer_can_subscribe_to_back_in_stock_alerts(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 0,
        ]);

        $response = $this->postJson(route('stock.notify'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'phone' => '9876543210',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('stock_notifications', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'phone' => '919876543210',
            'status' => 'pending',
        ]);

        // Duplicate subscription returns already registered message
        $duplicateResponse = $this->postJson(route('stock.notify'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'phone' => '9876543210',
        ]);

        $duplicateResponse->assertStatus(200);
        $duplicateResponse->assertJson([
            'success' => true,
        ]);
    }

    public function test_restocking_variant_triggers_automated_whatsapp_alerts(): void
    {
        $product = Product::factory()->create(['name' => 'Royal Banarasi Dupatta']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Crimson Red',
            'stock' => 0,
        ]);

        StockNotification::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'phone' => '919876543210',
            'status' => 'pending',
            'channel' => 'whatsapp',
        ]);

        $this->mock(WhatsAppCloudApiService::class, function ($mock) {
            $mock->shouldReceive('sendTextMessage')
                ->once()
                ->withArgs(function ($phone, $text) {
                    return $phone === '919876543210' && str_contains($text, 'Royal Banarasi Dupatta');
                })
                ->andReturn(['success' => true, 'messages' => [['id' => 'wamid.restock123']]]);
        });

        // Trigger restock
        $variant->update(['stock' => 8]);

        $this->assertDatabaseHas('stock_notifications', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'status' => 'notified',
        ]);
    }

    public function test_ndr_service_dispatches_prompt_and_handles_customer_actions(): void
    {
        $order = $this->createOrder([
            'order_number' => 'VR-NDR-3001',
            'shipping_mobile' => '9876543210',
        ]);

        $this->mock(WhatsAppCloudApiService::class, function ($mock) {
            $mock->shouldReceive('sendInteractiveButtonMessage')
                ->once()
                ->andReturn(['success' => true, 'messages' => [['id' => 'wamid.ndr123']]]);

            $mock->shouldReceive('sendTextMessage')
                ->once()
                ->withArgs(function ($phone, $text) {
                    return $phone === '919876543210' && str_contains($text, 'reattempt');
                })
                ->andReturn(['success' => true]);
        });

        $ndrService = app(NdrService::class);

        // 1. Dispatch NDR Alert
        $alertRes = $ndrService->handleNdrAlert($order, 'Customer door locked');
        $this->assertTrue($alertRes['success'] ?? false);

        $order->refresh();
        $this->assertStringContainsString('NDR alert sent', (string) $order->internal_notes);

        // 2. Customer confirms reattempt
        $reattemptRes = $ndrService->handleCustomerReattempt('VR-NDR-3001', '919876543210');
        $this->assertTrue($reattemptRes);

        $order->refresh();
        $this->assertStringContainsString('Customer requested delivery reattempt', (string) $order->internal_notes);
    }

    public function test_predictive_search_suggest_endpoint(): void
    {
        $category = Category::factory()->create(['name' => 'Handcrafted Sarees', 'slug' => 'handcrafted-sarees']);
        $product = Product::factory()->create([
            'name' => 'Chanderi Gold Zari Saree',
            'status' => 'active',
            'selling_price' => 2499,
            'mrp' => 3499,
        ]);
        $product->categories()->attach($category);

        $response = $this->getJson(route('search.suggest', ['q' => 'Chanderi']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'products' => [
                '*' => ['id', 'name', 'slug', 'url', 'price', 'in_stock'],
            ],
            'categories',
        ]);

        $this->assertStringContainsString('Chanderi Gold Zari Saree', json_encode($response->json()));
    }
}
