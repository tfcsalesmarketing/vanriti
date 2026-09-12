<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function guestCartCookie(array $data = []): array
    {
        return Crypt::encryptString($data['session'] ?? 'cart_test_session');
    }

    public function test_guest_cart_merges_into_user_cart_on_registration(): void
    {
        $product = Product::factory()->active()->create();

        // Simulate a guest cart in the DB tied to a known session cookie.
        $guestCart = Cart::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => 'cart_test_session',
        ]);
        $guestCart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        // Register while presenting the guest cart cookie.
        $this->withCookie('vanriti_cart', 'cart_test_session')
            ->post(route('register.submit'), [
                'name' => 'Merge User',
                'email' => 'merge@example.com',
                'phone' => '9876543210',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'merge@example.com')->firstOrFail();

        $this->assertDatabaseHas('carts', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(2, (int) $cart->items()->first()->quantity);
        $this->assertSame((int) $product->id, (int) $cart->items()->first()->product_id);

        // Guest cart should be gone.
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }

    public function test_guest_wishlist_merges_into_user_wishlist_on_registration(): void
    {
        $product = Product::factory()->active()->create();

        $guestWishlist = Wishlist::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => 'wish_test_session',
        ]);
        $guestWishlist->items()->attach($product->id);

        $this->withCookie('vanriti_wishlist', 'wish_test_session')
            ->post(route('register.submit'), [
                'name' => 'Wish User',
                'email' => 'wish@example.com',
                'phone' => '9876543210',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'wish@example.com')->firstOrFail();

        $this->assertDatabaseHas('wishlists', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $userWishlist = Wishlist::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $userWishlist->items()->count());
        $this->assertSame((int) $product->id, (int) $userWishlist->items()->first()->id);

        $this->assertDatabaseMissing('wishlists', ['id' => $guestWishlist->id]);
    }

    public function test_cart_service_merge_guest_into_user_service_level(): void
    {
        $product = Product::factory()->active()->create();
        $user = User::factory()->create();

        $guestCart = Cart::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => 'cart_merge_svc',
        ]);
        $guestCart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        request()->cookies->set('vanriti_cart', 'cart_merge_svc');
        $this->actingAs($user, 'web');

        $cartService = app(CartService::class);
        $cartService->mergeGuestCartIntoUser($user);

        $userCart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $userCart->items()->count());

        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }

    public function test_wishlist_service_merge_guest_into_user_service_level(): void
    {
        $product = Product::factory()->active()->create();
        $user = User::factory()->create();

        $guestWishlist = Wishlist::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => 'wish_merge_svc',
        ]);
        $guestWishlist->items()->attach($product->id);

        request()->cookies->set('vanriti_wishlist', 'wish_merge_svc');
        $this->actingAs($user, 'web');

        $wishlistService = app(WishlistService::class);
        $wishlistService->mergeGuestIntoUser($user);

        $userWishlist = Wishlist::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $userWishlist->items()->count());

        $this->assertDatabaseMissing('wishlists', ['id' => $guestWishlist->id]);
    }
}
