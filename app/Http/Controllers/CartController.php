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

        $coupon = $this->couponService->syncSessionCoupon($this->cartService->getCart(), auth('web')->user());
        $couponDiscount = $coupon['discount'];
        $couponCode = $coupon['code'];

        return view('storefront.cart.index', compact(
            'cartItems', 'subtotal', 'count', 'shipping', 'couponDiscount', 'couponCode', 'viewCartPayload'
        ));
    }

    public function drawer(): JsonResponse
    {
        $subtotal = $this->cartService->subtotal();
        $count = $this->cartService->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'subtotal' => $subtotal,
            'formatted_subtotal' => format_price($subtotal),
            'html' => $this->renderDrawerHtml(),
        ]);
    }

    protected function renderDrawerHtml(): string
    {
        $cartItems = $this->cartService->items();
        $subtotal = $this->cartService->subtotal();
        $count = $this->cartService->count();

        return view('storefront.partials.cart-drawer-content', compact(
            'cartItems', 'subtotal', 'count'
        ))->render();
    }

    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        // Guests may add to cart anonymously (the cart is keyed to the session
        // cookie) and reconcile into their account on login. Checkout still
        // requires an authenticated user.
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:5',
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

        // First-party analytics event (measurement only; never affects cart outcome).
        app(\App\Services\Analytics\AnalyticsEventRecorder::class)->addToCart($product, $variant, (int) $request->quantity);

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
                'cartItemId' => $cartItem->id,
                'quantity' => $cartItem->quantity,
                'analytics' => $analytics,
                'subtotal' => $this->cartService->subtotal(),
                'formatted_subtotal' => format_price($this->cartService->subtotal()),
                'drawerHtml' => $this->renderDrawerHtml(),
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

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:5',
        ]);

        try {
            $this->cartService->updateQuantity($cartItem->id, (int) $validated['quantity']);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart updated.',
                'cartCount' => $this->cartService->count(),
                'quantity' => (int) $validated['quantity'],
                'subtotal' => $this->cartService->subtotal(),
                'formatted_subtotal' => format_price($this->cartService->subtotal()),
                'drawerHtml' => $this->renderDrawerHtml(),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function remove(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        try {
            $this->cartService->remove($cartItem->id);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed.',
                'cartCount' => $this->cartService->count(),
                'subtotal' => $this->cartService->subtotal(),
                'formatted_subtotal' => format_price($this->cartService->subtotal()),
                'drawerHtml' => $this->renderDrawerHtml(),
            ]);
        }

        return back()->with('success', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clear();

        return back()->with('success', 'Cart cleared.');
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => 'nullable|string|max:50',
        ]);

        $code = strtoupper(trim((string) $request->code));

        if ($code === '') {
            session()->forget('cart_coupon');

            if ($request->expectsJson()) {
                $subtotal = $this->cartService->subtotal();
                $shipping = $this->shippingService->calculate($subtotal);
                $total = $subtotal + ($shipping['charge'] > 0 ? $shipping['charge'] : 0);

                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'Coupon removed.',
                    'coupon_code' => null,
                    'discount' => 0,
                    'formatted_discount' => format_price(0),
                    'subtotal' => $subtotal,
                    'formatted_subtotal' => format_price($subtotal),
                    'shipping_charge' => $shipping['charge'],
                    'formatted_shipping' => $shipping['charge'] <= 0 ? 'FREE' : format_price($shipping['charge']),
                    'total' => $total,
                    'formatted_total' => format_price($total),
                ]);
            }

            return back()->with('success', 'Coupon removed.');
        }

        $cart = $this->cartService->getCart(true);

        if (! $cart || ! $cart->items()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty.',
                ], 422);
            }

            return back()->with('error', 'Your cart is empty.');
        }

        $result = $this->couponService->validate($code, $cart, auth('web')->user());

        if (! $result['valid']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return back()->with('error', $result['message']);
        }

        session([
            'cart_coupon' => [
                'code' => strtoupper($result['coupon']->code),
                'discount' => $result['discount'],
            ],
        ]);

        $message = $result['message'];
        if ($result['discount'] > 0) {
            $message .= ' You saved '.format_price($result['discount']).'.';
        }

        if ($request->expectsJson()) {
            $subtotal = $this->cartService->subtotal();
            $discount = (float) $result['discount'];
            $subtotalAfterDiscount = max(0, $subtotal - $discount);
            $shipping = $this->shippingService->calculate($subtotalAfterDiscount);
            $total = $subtotalAfterDiscount + ($shipping['charge'] > 0 ? $shipping['charge'] : 0);

            return response()->json([
                'success' => true,
                'action' => 'applied',
                'message' => $message,
                'coupon_code' => strtoupper($result['coupon']->code),
                'discount' => $discount,
                'formatted_discount' => format_price($discount),
                'subtotal' => $subtotal,
                'formatted_subtotal' => format_price($subtotal),
                'subtotal_after_discount' => $subtotalAfterDiscount,
                'formatted_subtotal_after_discount' => format_price($subtotalAfterDiscount),
                'shipping_charge' => $shipping['charge'],
                'formatted_shipping' => $shipping['charge'] <= 0 ? 'FREE' : format_price($shipping['charge']),
                'total' => $total,
                'formatted_total' => format_price($total),
            ]);
        }

        return back()->with('success', $message);
    }
}
