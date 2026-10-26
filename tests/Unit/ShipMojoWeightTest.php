<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ShipMojoService;
use Tests\TestCase;

class ShipMojoWeightTest extends TestCase
{
    public function test_calculate_order_weight_sums_actual_product_weights(): void
    {
        $service = app(ShipMojoService::class);

        $order = new Order();
        $order->id = 9999;

        $p1 = new Product(['weight' => 100, 'unit' => 'gm']);
        $p2 = new Product(['weight' => 200, 'unit' => 'gm']);

        $item1 = new OrderItem(['quantity' => 2]);
        $item1->setRelation('product', $p1);
        $item1->setRelation('variant', null);

        $item2 = new OrderItem(['quantity' => 1]);
        $item2->setRelation('product', $p2);
        $item2->setRelation('variant', null);

        $order->setRelation('items', collect([$item1, $item2]));

        // Item 1: 100g * 2 = 200g
        // Item 2: 200g * 1 = 200g
        // Total = 400g
        $this->assertEquals(400, $service->calculateOrderWeight($order));
    }

    public function test_two_items_of_100g_equals_200g(): void
    {
        $service = app(ShipMojoService::class);

        $order = new Order();
        $order->id = 9998;

        $product = new Product(['weight' => 100, 'unit' => 'gm']);

        $item = new OrderItem(['quantity' => 2]);
        $item->setRelation('product', $product);
        $item->setRelation('variant', null);

        $order->setRelation('items', collect([$item]));

        // 2 items * 100g = 200g
        $this->assertEquals(200, $service->calculateOrderWeight($order));
    }

    public function test_package_dimensions_do_not_exceed_actual_volumetric_weight(): void
    {
        $service = app(ShipMojoService::class);

        // For a 200g order:
        $dim200 = $service->calculatePackageDimensions(200);
        // Volumetric weight: (L * W * H) / 5 in grams
        $volWeight200 = ($dim200['length'] * $dim200['width'] * $dim200['height']) / 5;

        // Volumetric weight must be less than or equal to actual weight (200g)
        $this->assertLessThanOrEqual(200, $volWeight200);
    }
}
