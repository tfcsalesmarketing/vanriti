<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Analytics\EcommerceDataService;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\Dadi\DadiAttributionService;
use App\Services\ShippingService;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CouponService $couponService,
        protected ShippingService $shippingService,
        protected WishlistService $wishlistService,
        protected EcommerceDataService $ecommerceDataService,
        protected DadiAttributionService $dadiAttributionService,
    ) {}

    public function index(): View
    {
        $cartItems = $this->cartService->items();
        $subtotal = $this->cartService->subtotal();
        $count = $this->cartService->count();

        $viewCartPayload = $cartItems->isNotEmpty()
            ? $this->ecommerceDataService->viewCart($this->cartService->getCart())
            : null;

        try {
            $shipping = $this->shippingService->calculate($subtotal, null, 'standard');
        } catch (\Throwable $e) {
            $shipping = ['charge' => 0, 'eligible_for_free' => false, 'estimated_days' => '3-7'];
        }

        $couponDiscount = 0;
        $couponCode = session('cart_coupon.code');

        return view('storefront.cart.index', compact(
            'cartItems', 'subtotal', 'count', 'shipping', 'couponDiscount', 'couponCode', 'viewCartPayload'
        ));
    }

    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
            'variant_id' => 'nullable|integer|exists:product_variants,id,product_id,'.$product->id,
            'dadi_reference' => 'nullable|string|max:40',
            'conversation_id' => 'nullable|integer|min:1',
        ]);

        $variant = $request->filled('variant_id')
            ? ProductVariant::where('id', $request->variant_id)->where('product_id', $product->id)->first()
            : null;

        try {
            $cartItem = $this->cartService->add($product, (int) $request->quantity, $variant);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->boolean('remove_from_wishlist')) {
            $this->wishlistService->remove($product);
        }

        $analytics = $this->ecommerceDataService->addToCart($product, $variant, (int) $request->quantity);

        // Record minimal Dadi attribution (best-effort; never affects cart outcome).
        $this->recordDadiAttribution($request, $product, $cartItem, $request->boolean('buy_now'));

        if ($request->boolean('buy_now')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Adding your order...',
                    'redirect' => route('checkout.index'),
                    'redirect_only' => true,
                    'analytics' => $analytics,
                ]);
            }

            return redirect()->route('checkout.index')->with('pending_add_to_cart', $analytics);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Added to cart.',
                'redirect' => route('cart.index'),
                'cartCount' => $this->cartService->count(),
                'analytics' => $analytics,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Added to cart.')->with('pending_add_to_cart', $analytics);
    }

    private function recordDadiAttribution(
        Request $request,
        Product $product,
        CartItem $cartItem,
        bool $isBuyNow,
    ): void {
        if (! $request->filled('dadi_reference') && ! $request->filled('conversation_id')) {
            return;
        }

        try {
            $this->dadiAttributionService->record(
                action: $isBuyNow ? 'buy_now' : 'add_to_cart',
                user: auth('web')->user(),
                sessionId: $request->session()->getId(),
                product: $product,
                reference: $request->string('dadi_reference')->toString() ?: null,
                cartId: $cartItem->cart_id,
                cartItemId: $cartItem->id,
                guestCartKey: $request->cookie(CartService::COOKIE_NAME),
            );
        } catch (\Throwable) {
            // Attribution is additive and must never affect the cart outcome.
        }
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:5',
        ]);

        try {
            $this->cartService->updateQuantity($cartItem->id, (int) $validated['quantity']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function remove(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->cartService->remove($cartItem->id);

        return back()->with('success', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clear();

        return back()->with('success', 'Cart cleared.');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));

        if ($code === '') {
            session()->forget('cart_coupon');

            return back()->with('success', 'Coupon removed.');
        }

        $cart = $this->cartService->getCart(true);

        if (! $cart || ! $cart->items()->exists()) {
            return back()->with('error', 'Your cart is empty.');
        }

        $result = $this->couponService->validate($code, $cart, auth('web')->user());

        if (! $result['valid']) {
            return back()->with('error', $result['message']);
        }

        session([
            'cart_coupon' => [
                'code' => strtoupper($result['coupon']->code),
                'discount' => $result['discount'],
            ],
        ]);

        return back()->with('success', $result['message']);
    }
}
