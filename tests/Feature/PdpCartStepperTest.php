<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdpCartStepperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_pdp_shows_add_button_and_no_side_cart_drawer_for_empty_cart(): void
    {
        $product = Product::factory()->active()->create([
            'name' => 'Stepper Tonic',
            'selling_price' => 299.00,
            'mrp' => 399.00,
            'stock' => 10,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        // Side cart drawer fully removed.
        $this->assertStringNotContainsString('vrCartDrawer', $html);
        $this->assertStringNotContainsString('cart-drawer', $html);
        $this->assertStringNotContainsString('data-bs-toggle="offcanvas" data-bs-target="#vrCartDrawer"', $html);
        $this->assertStringContainsString('href="'.route('cart.index').'"', $html);

        // Pre-add state visible, steppers hidden.
        $this->assertStringContainsString('id="addToCartForm"', $html);
        $this->assertStringContainsString('data-variant-cart="[]"', $html);
        $this->assertMatchesRegularExpression('/class="js-pdp-add-wrap flex-grow-1[^"]*"/', $html);
        $this->assertStringNotContainsString('js-pdp-add-wrap flex-grow-1 d-none', $html);
        $this->assertMatchesRegularExpression('/class="js-pdp-qty-wrap flex-grow-1 d-none/', $html);
        $this->assertMatchesRegularExpression('/class="js-bar-qty-wrap flex-grow-1 d-none/', $html);

        // Stepper controls are present behind the d-none toggle.
        $this->assertStringContainsString('js-pdp-qty-form', $html);
        $this->assertMatchesRegularExpression('/data-update-url="[^"]*\/cart\/__ITEM__\/update"/', $html);
        $this->assertMatchesRegularExpression('/data-remove-url="[^"]*\/cart\/__ITEM__\/remove"/', $html);
    }

    public function test_pdp_form_contains_buy_now_within_the_single_add_to_cart_form(): void
    {
        $productA = Product::factory()->active()->create([
            'name' => 'Stepper Form A',
            'selling_price' => 299.00,
            'mrp' => 399.00,
            'stock' => 10,
        ]);
        $user = User::factory()->create();

        // Empty cart.
        $html = $this->actingAs($user, 'web')->get(route('product.show', $productA))->assertOk()->getContent();
        $this->assertSingleAddToCartForm($html);

        // A second product with an existing cart line (desktop + mobile steppers shown).
        $productB = Product::factory()->active()->create([
            'name' => 'Stepper Form B',
            'selling_price' => 199.00,
            'mrp' => 299.00,
            'stock' => 10,
        ]);
        $this->actingAs($user, 'web')->postJson(route('cart.add', $productB), ['quantity' => 2])->assertOk();

        $html = $this->actingAs($user, 'web')->get(route('product.show', $productB))->assertOk()->getContent();
        $this->assertSingleAddToCartForm($html);
    }

    private function assertSingleAddToCartForm(string $html): void
    {
        // Exactly one form carries id="addToCartForm" on the page.
        $this->assertSame(1, substr_count($html, 'id="addToCartForm"'));

        // Bound the outer form (its own closing tag is the first </form> after it —
        // no nested <form> may exist inside it).
        $start = strpos($html, 'id="addToCartForm"');
        $this->assertNotFalse($start);
        $end = strpos($html, '</form>', $start);
        $this->assertNotFalse($end);
        $formRegion = substr($html, $start, $end - $start);

        // No nested form start tag inside the add-to-cart form.
        $this->assertStringNotContainsString('<form', $formRegion);

        // The Buy Now and Add buttons are descendants of the form (never orphaned).
        $this->assertStringContainsString('js-pdp-buy', $formRegion);
        $this->assertStringContainsString('js-pdp-add', $formRegion);

        // Steppers are plain wrappers (no inner update <form> element).
        $this->assertStringContainsString('js-pdp-qty-form', $formRegion);
        $this->assertStringNotContainsString('class="js-pdp-qty-form m-0">', $formRegion);
    }

    public function test_pdp_switches_to_stepper_when_default_variant_is_in_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'name' => 'Stepper Serum',
            'selling_price' => 199.00,
            'mrp' => 299.00,
            'stock' => 10,
        ]);

        $json = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 2]);
        $json->assertOk();
        $cartItemId = $json->json('cartItemId');

        $html = $this->actingAs($user, 'web')->get(route('product.show', $product))->assertOk()->getContent();

        // Add state hidden, stepper shown, wired to the cart line.
        $this->assertStringContainsString('js-pdp-add-wrap flex-grow-1 d-none', $html);
        $this->assertMatchesRegularExpression('/class="js-pdp-qty-wrap flex-grow-1[^"]*"/', $html);
        $this->assertStringContainsString('data-cart-item="'.$cartItemId.'"', $html);
        $this->assertMatchesRegularExpression('/<input type="number" name="quantity" value="2" min="1" max="5" readonly aria-label="Quantity in cart">/', $html);
        $this->assertStringContainsString('js-bar-add d-none', $html);
        $this->assertStringContainsString('js-bar-qty-selector d-none', $html);

        // Server-side map reflects the default (no-variant) key.
        $expectedMap = htmlspecialchars((string) json_encode(['__default__' => ['id' => $cartItemId, 'qty' => 2]]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->assertStringContainsString('data-variant-cart="'.$expectedMap.'"', $html);
    }

    public function test_pdp_stepper_is_variant_specific(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'name' => 'Stepper Cleanser',
            'selling_price' => 150.00,
            'mrp' => 200.00,
            'stock' => 10,
        ]);
        $variantA = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 100 ml',
            'status' => 'active',
            'stock' => 8,
            'sort_order' => 1,
        ]);
        $variantB = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Rose 100 ml',
            'status' => 'active',
            'stock' => 6,
            'sort_order' => 2,
        ]);

        $json = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 3,
            'variant_id' => $variantA->id,
        ]);
        $json->assertOk();
        $cartItemId = $json->json('cartItemId');

        $html = $this->actingAs($user, 'web')->get(route('product.show', $product))->assertOk()->getContent();

        // Only the in-cart variant appears in the serialized map.
        $expectedMap = htmlspecialchars((string) json_encode([
            (string) $variantA->id => ['id' => $cartItemId, 'qty' => 3],
        ]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->assertStringContainsString('data-variant-cart="'.$expectedMap.'"', $html);

        // The initially selected (first in-stock) variant is A and renders the stepper.
        $this->assertStringContainsString('js-pdp-add-wrap flex-grow-1 d-none', $html);
        $this->assertMatchesRegularExpression('/class="js-pdp-qty-wrap flex-grow-1[^"]*"/', $html);
        $this->assertStringContainsString('data-cart-item="'.$cartItemId.'"', $html);
        $this->assertMatchesRegularExpression('/<input type="number" name="quantity" value="3" min="1" max="5" readonly aria-label="Quantity in cart">/', $html);
    }
}