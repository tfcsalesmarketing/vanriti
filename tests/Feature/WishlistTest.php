<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_add_and_remove_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();

        $this->actingAs($user, 'web')
            ->post(route('wishlist.toggle', $product), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['added' => true]);

        $wishlist = Wishlist::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $wishlist->items()->count());

        // Toggle again to remove
        $this->actingAs($user, 'web')
            ->post(route('wishlist.toggle', $product), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['added' => false]);

        $this->assertSame(0, $wishlist->fresh()->items()->count());
    }

    public function test_guest_can_toggle_wishlist_without_login(): void
    {
        $product = Product::factory()->active()->create();

        // Guest add — no account required, cookie-backed wishlist is created.
        $this->withCookie('vanriti_wishlist', 'wish_guest_session')
            ->post(route('wishlist.toggle', $product), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['added' => true]);

        $wishlist = Wishlist::where('owner_type', 'guest')
            ->where('session_id', 'wish_guest_session')
            ->firstOrFail();
        $this->assertSame(1, $wishlist->items()->count());

        // Toggle again removes it — still no login required.
        $this->withCookie('vanriti_wishlist', 'wish_guest_session')
            ->post(route('wishlist.toggle', $product), [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['added' => false]);

        $this->assertSame(0, $wishlist->fresh()->items()->count());
    }

    public function test_guest_native_wishlist_post_does_not_redirect_to_login(): void
    {
        $product = Product::factory()->active()->create();

        $response = $this->from(route('home'))->post(route('wishlist.toggle', $product));

        $response->assertRedirect(route('home'));
        $this->assertGuest('web');

        $guestWishlist = Wishlist::where('owner_type', 'guest')->firstOrFail();
        $this->assertSame(1, $guestWishlist->items()->count());
    }

    public function test_wishlist_index_shows_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['name' => 'Luxury Face Mask']);

        $wishlist = Wishlist::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $wishlist->items()->attach($product->id);

        $this->actingAs($user, 'web')
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Luxury Face Mask');
    }

    public function test_guest_wishlist_merges_on_login(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();

        $guestWishlist = Wishlist::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => 'wish_login',
        ]);
        $guestWishlist->items()->attach($product->id);

        $this->withCookie('vanriti_wishlist', 'wish_login')
            ->post(route('login.submit'), [
                'login' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect();

        $userWishlist = Wishlist::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $userWishlist->items()->count());
        $this->assertSame((int) $product->id, (int) $userWishlist->items()->first()->id);

        $this->assertDatabaseMissing('wishlists', ['id' => $guestWishlist->id]);
    }
}
