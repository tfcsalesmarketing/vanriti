<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Storage::fake('s3');
    }

    protected function product(): Product
    {
        return Product::factory()->active()->create([
            'selling_price' => 150.00, 'mrp' => 150.00, 'gst_rate' => 0, 'stock' => 10,
        ]);
    }

    protected function deliveredOrder(User $user, Product $product): Order
    {
        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => 0,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);

        $order->update(['order_status' => 'delivered']);

        return $order->fresh();
    }

    /**
     * @return list<string>
     */
    protected function storedFiles(): array
    {
        return array_values(Storage::disk('s3')->allFiles('reviews'));
    }

    // RIMG-001 -------------------------------------------------------------

    public function test_customer_can_submit_review_with_images(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'title' => 'Excellent',
                'comment' => 'Looks great in person.',
                'images' => [
                    UploadedFile::fake()->image('front.jpg', 200, 200),
                    UploadedFile::fake()->image('back.png', 200, 200),
                    UploadedFile::fake()->image('detail.webp', 200, 200),
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        $review = Review::where('order_item_id', $orderItem->id)->firstOrFail();

        $this->assertSame(3, $review->images()->count());

        $images = $review->images()->orderBy('sort_order')->get();
        $this->assertSame([0, 1, 2], $images->pluck('sort_order')->all());

        foreach ($images as $image) {
            $this->assertSame('s3', $image->disk);
            $this->assertStringStartsWith('reviews/'.$review->id.'/', $image->path);
            Storage::disk('s3')->assertExists($image->path);
        }

        $this->assertCount(3, $this->storedFiles());
    }

    // RIMG-002 -------------------------------------------------------------

    public function test_more_than_five_images_are_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'images' => array_map(
                    fn (int $i) => UploadedFile::fake()->image("shot-{$i}.jpg", 100, 100),
                    range(1, 6)
                ),
            ]);

        $response->assertSessionHasErrors('images');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_images', 0);
        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-003 -------------------------------------------------------------

    public function test_non_image_upload_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'images' => [UploadedFile::fake()->create('notes.txt', 8, 'text/plain')],
            ]);

        $response->assertSessionHasErrors('images.0');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-004 -------------------------------------------------------------

    public function test_image_over_size_limit_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'images' => [UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg')],
            ]);

        $response->assertSessionHasErrors('images.0');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-005 -------------------------------------------------------------

    public function test_review_without_images_still_succeeds(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 4,
                'comment' => 'No photos this time.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('review_images', 0);
        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-006 -------------------------------------------------------------

    public function test_non_delivered_order_stores_no_images(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => 0,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $order->items()->firstOrFail()->id,
                'rating' => 5,
                'images' => [UploadedFile::fake()->image('early.jpg', 100, 100)],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_images', 0);
        $this->assertSame([], $this->storedFiles(), 'The delivered gate must run before any file is stored.');
    }

    // RIMG-007 -------------------------------------------------------------

    public function test_user_cannot_upload_images_to_another_users_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);

        $response = $this->actingAs($other, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $order->items()->firstOrFail()->id,
                'rating' => 5,
                'images' => [UploadedFile::fake()->image('intruder.jpg', 100, 100)],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-008 -------------------------------------------------------------

    public function test_duplicate_review_with_images_stores_nothing(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'images' => [UploadedFile::fake()->image('first.jpg', 100, 100)],
            ])
            ->assertSessionHas('success');

        $this->assertSame(1, Review::where('order_item_id', $orderItem->id)->count());
        $this->assertCount(1, $this->storedFiles());

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 1,
                'images' => [
                    UploadedFile::fake()->image('second.jpg', 100, 100),
                    UploadedFile::fake()->image('third.jpg', 100, 100),
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Review::where('order_item_id', $orderItem->id)->count());
        $this->assertCount(
            1,
            $this->storedFiles(),
            'The duplicate guard must run before any file is stored.'
        );
    }

    // RIMG-009 -------------------------------------------------------------

    public function test_deleting_review_removes_image_rows_and_files(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'images' => [
                    UploadedFile::fake()->image('one.jpg', 100, 100),
                    UploadedFile::fake()->image('two.jpg', 100, 100),
                ],
            ])
            ->assertSessionHas('success');

        $review = Review::where('order_item_id', $orderItem->id)->firstOrFail();
        $paths = $review->images()->pluck('path')->all();

        $this->assertCount(2, $paths);
        $this->assertDatabaseCount('review_images', 2);

        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.reviews.destroy', $review->id))
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_images', 0);

        foreach ($paths as $path) {
            Storage::disk('s3')->assertMissing($path);
        }

        $this->assertSame([], $this->storedFiles());
    }

    // RIMG-010 -------------------------------------------------------------

    public function test_only_approved_review_images_are_public(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->superAdmin()->create();

        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $pending = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'rating' => 5,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);
        $pendingImage = $pending->images()->create(['path' => 'reviews/pending.jpg', 'disk' => 's3', 'sort_order' => 0]);
        Storage::disk('s3')->put('reviews/pending.jpg', 'x');

        // A second, approved review on the same product so the section renders.
        $otherUser = User::factory()->create();
        $otherOrder = $this->deliveredOrder($otherUser, $product);
        $approved = Review::create([
            'product_id' => $product->id,
            'user_id' => $otherUser->id,
            'order_item_id' => $otherOrder->items()->firstOrFail()->id,
            'rating' => 4,
            'status' => 'approved',
            'is_verified_purchase' => true,
        ]);
        $approvedImage = $approved->images()->create(['path' => 'reviews/approved.jpg', 'disk' => 's3', 'sort_order' => 0]);
        Storage::disk('s3')->put('reviews/approved.jpg', 'x');

        $response = $this->get(route('product.show', $product));

        $response->assertOk();
        $response->assertSee(Storage::disk('s3')->url($approvedImage->path), escape: false);
        $response->assertDontSee(Storage::disk('s3')->url($pendingImage->path), escape: false);

        // Moderation still governs the images: approving the pending review
        // makes its photo appear, rejecting it takes it away again.
        $this->actingAs($admin, 'admin')
            ->post(route('admin.reviews.approve', $pending->id))
            ->assertRedirect();

        $this->get(route('product.show', $product))
            ->assertSee(Storage::disk('s3')->url($pendingImage->path), escape: false);
    }

    // RIMG-011 -------------------------------------------------------------

    public function test_review_option_disappears_once_reviewed(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        // Delivered but not yet reviewed: the option is offered.
        $this->actingAs($user, 'web')
            ->get(route('account.order', $order))
            ->assertOk()
            ->assertSee('data-bs-target="#review-'.$orderItem->id.'"', escape: false);

        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
            ])
            ->assertSessionHas('success');

        // Reviewed: the option must be gone (regression test for the dead
        // reviewExist / alreadyReviewing gate that used to keep it visible).
        $this->actingAs($user, 'web')
            ->get(route('account.order', $order))
            ->assertOk()
            ->assertDontSee('data-bs-target="#review-'.$orderItem->id.'"', escape: false);
    }
}
