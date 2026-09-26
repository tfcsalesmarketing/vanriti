<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Jobs\PushOrderToShipMojo;
use App\Jobs\SendMetaCapiPurchase;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Analytics\ConversionService;
use App\Services\Analytics\EcommerceDataService;
use App\Services\Analytics\MetaCapiService;
use App\Services\CartService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\RazorpayGateway;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService,
        protected PaymentService $paymentService,
        protected ShippingService $shippingService,
        protected EcommerceDataService $ecommerceDataService,
        protected ConversionService $conversionService,
        protected MetaCapiService $metaCapiService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = auth('web')->user();

        if (! $user) {
            return view('storefront.checkout.guest');
        }

        $cart = $this->cartService->getCart(true);

        if (! $cart || ! $cart->items()->exists()) {
            return redirect()->route('cart.index');
        }

        $addresses = $user->addresses()->get();

        $selectedAddress = null;
        if ($request->has('address_id')) {
            $selectedAddress = $addresses->where('id', $request->address_id)->first();
        }
        if (! $selectedAddress && $request->input('address') !== 'new') {
            $selectedAddress = $addresses->where('is_default', true)->first() ?? $addresses->first();
        }

        $subtotal = $this->cartService->subtotal();
        $availableMethods = $this->paymentService->availableMethods();

        $shipping = ['charge' => 0, 'eligible_for_free' => false, 'estimated_days' => '3-7', 'method' => 'standard'];
        try {
            $shipping = $this->shippingService->calculate($subtotal, $selectedAddress, 'standard');
        } catch (\Throwable $e) {
            //
        }

        $couponDiscount = 0;
        $couponCode = session('cart_coupon.code');
        if ($couponCode) {
            $couponDiscount = (float) session('cart_coupon.discount', 0);
        }

        $beginCheckoutPayload = $this->ecommerceDataService->beginCheckout($cart);
        $checkoutEcommerce = $this->ecommerceDataService->checkoutEcommerce($cart);

        return view('storefront.checkout.index', compact(
            'cart', 'addresses', 'selectedAddress', 'subtotal',
            'availableMethods', 'shipping', 'couponDiscount', 'couponCode', 'user',
            'beginCheckoutPayload', 'checkoutEcommerce'
        ));
    }

    /**
     * Live server-side validation for individual checkout fields.
     */
    public function validateFields(Request $request): JsonResponse
    {
        $rules = (new CheckoutRequest)->rules();

        $subset = [];
        foreach ($rules as $field => $rule) {
            if ($request->has($field)) {
                $subset[$field] = $rule;
            }
        }

        $validator = Validator::make($request->only(array_keys($subset)), $subset);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function store(CheckoutRequest $request): JsonResponse|RedirectResponse
    {
        $user = auth('web')->user();

        $validated = $request->validated();

        $paymentMethods = $this->paymentService->availableMethods();
        $paymentMethodKey = $validated['payment_method'];
        if (! isset($paymentMethods[$paymentMethodKey]) || ! $paymentMethods[$paymentMethodKey]['enabled']) {
            return back()->withErrors(['payment_method' => 'Selected payment method is not available.'])->withInput();
        }

        $shipping = [
            'full_name' => $validated['shipping_name'],
            'mobile' => $validated['shipping_mobile'],
            'address_line1' => $validated['shipping_address_line1'],
            'address_line2' => $validated['shipping_address_line2'] ?? null,
            'landmark' => $validated['shipping_landmark'] ?? null,
            'city' => $validated['shipping_city'],
            'state' => $validated['shipping_state'],
            'pincode' => $validated['shipping_pincode'],
            'country' => $validated['shipping_country'] ?? 'India',
        ];

        if ($request->boolean('billing_same')) {
            $billing = $shipping;
        } else {
            $billing = [
                'full_name' => $validated['billing_name'],
                'mobile' => $validated['billing_mobile'],
                'address_line1' => $validated['billing_address_line1'],
                'address_line2' => $validated['billing_address_line2'] ?? null,
                'landmark' => $validated['billing_landmark'] ?? null,
                'city' => $validated['billing_city'],
                'state' => $validated['billing_state'],
                'pincode' => $validated['billing_pincode'],
                'country' => $validated['billing_country'] ?? 'India',
            ];
        }

        $orderData = [
            'billing' => $billing,
            'shipping' => $shipping,
            'shipping_method' => $validated['shipping_method'],
            'payment_method' => $validated['payment_method'],
            'coupon_code' => session('cart_coupon.code'),
            'notes' => $validated['notes'] ?? null,
        ];

        $cart = $this->cartService->getCart();

        if (! $cart || ! $cart->items()->exists()) {
            return $this->emptyCartResponse($user);
        }

        $resumed = false;

        // Resume an abandoned Razorpay checkout before creating a new order so
        // a re-submission of the same cart never produces a duplicate pending
        // order (the cart is kept intact until payment is verified).
        if ($validated['payment_method'] === 'razorpay') {
            if ($existing = $this->resumableRazorpayOrder($user, $cart)) {
                $resumed = true;

                return $this->razorpayJsonResponse($existing, $cart);
            }
        }

        try {
            $order = DB::transaction(function () use ($user, $cart, $orderData, $validated, &$resumed) {
                // Serialise concurrent checkout submissions on this cart row so a
                // double-submit cannot turn one cart into two orders.
                $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->first() ?? $cart;

                if (! $cart->items()->exists()) {
                    throw new \RuntimeException('Your cart is empty.');
                }

                // A concurrent request that already committed during the lock wait
                // has produced a pending Razorpay order for this exact cart:
                // resume it (stock was decremented once) instead of duplicating it.
                if ($validated['payment_method'] === 'razorpay') {
                    if ($existing = $this->resumableRazorpayOrderForCart($user, $cart)) {
                        $resumed = true;

                        session(['razorpay_inflight' => [
                            'order_id' => $existing->id,
                            'cart_id' => $cart->id,
                            'cart_hash' => $this->razorpayCartHash($existing),
                        ]]);

                        return $existing;
                    }
                }

                // For online payment the cart is intentionally NOT cleared here:
                // it stays intact until the payment is server-verified, so an
                // abandoned/failed Razorpay dialog does not leave the customer
                // with an empty cart.
                $clearCart = $validated['payment_method'] !== 'razorpay';

                return $this->orderService->placeOrder($user, $cart, $orderData, $clearCart);
            });
        } catch (\RuntimeException $e) {
            return $this->orderFailureResponse($user, $e);
        }

        if ($user) {
            $isNew = $request->input('selected_address') === 'new'
                || ! $request->has('selected_address');

            if ($isNew && $this->isNewAddressForUser($user, $shipping)) {
                $user->addresses()->create([
                    'full_name' => $shipping['full_name'],
                    'mobile' => $shipping['mobile'],
                    'address_line1' => $shipping['address_line1'],
                    'address_line2' => $shipping['address_line2'] ?? null,
                    'landmark' => $shipping['landmark'] ?? null,
                    'city' => $shipping['city'],
                    'state' => $shipping['state'],
                    'pincode' => $shipping['pincode'],
                    'country' => $shipping['country'] ?? 'India',
                    'type' => 'other',
                    'is_default' => $user->addresses()->count() === 0,
                ]);
            }
        }

        if ($validated['payment_method'] === 'cod') {
            try {
                $payment = $this->paymentService->createPayment($order, 'cod');
                $this->paymentService->initialize($order, $payment, 'cod');
            } catch (\Throwable $e) {
                return back()->withErrors(['checkout' => 'Payment initialisation failed: '.$e->getMessage()])->withInput();
            }

            session()->forget('cart_coupon');

            // Auto-push to ShipMojo for COD orders
            if ((bool) setting('shipmojo_auto_push', false)) {
                PushOrderToShipMojo::dispatch($order);
            }

            // COD conversion is recorded when the order is successfully placed.
            $this->conversionService->recordPurchase($order);

            $this->dispatchMetaCapiPurchase($order, $request);

            $this->notifyCustomer($order, 'orderPlaced');

            return redirect()->route('checkout.success', $order)->with('success', 'Order placed successfully!');
        }

        if ($validated['payment_method'] === 'razorpay') {
            if (! $resumed) {
                $this->notifyCustomer($order, 'orderPlaced');
            }

            return $this->razorpayJsonResponse($order, $cart);
        }

        return back()->withErrors(['checkout' => 'Unknown payment method.'])->withInput();
    }

    /**
     * Return a fresh Razorpay checkout token for the given order, remembering
     * the order as the session's in-flight Razorpay checkout so a later
     * submission of the same cart resumes it instead of duplicating it.
     */
    protected function razorpayJsonResponse(Order $order, Cart $cart): JsonResponse|RedirectResponse
    {
        $payment = null;
        $init = null;

        try {
            DB::transaction(function () use ($order, $cart, &$payment, &$init) {
                // Serialise Razorpay token creation on this cart row so that
                // concurrent submissions — already collapsed onto a single order
                // in store() — also share one Razorpay order id instead of
                // creating multiple payment rows per checkout.
                Cart::whereKey($cart->id)->lockForUpdate()->first();

                $payment = $order->payments()
                    ->where('method', 'razorpay')
                    ->where('status', 'pending')
                    ->whereNotNull('payment_reference')
                    ->latest()
                    ->first();

                if ($payment) {
                    $gateway = new RazorpayGateway;

                    $init = [
                        'id' => $payment->payment_reference,
                        'gateway' => 'razorpay',
                        'redirect_url' => null,
                        'amount' => (float) $order->amount_due,
                        'config' => $gateway->publicConfig(),
                    ];

                    return;
                }

                $payment = $this->paymentService->createPayment($order, 'razorpay');
                $init = $this->paymentService->initialize($order, $payment, 'razorpay');
            });

            session()->forget('cart_coupon');
            session(['razorpay_inflight' => [
                'order_id' => $order->id,
                'cart_id' => $cart->id,
                'cart_hash' => $this->razorpayCartHash($order),
            ]]);

            return response()->json([
                'success' => true,
                'razorpay_order_id' => $init['id'],
                'amount_paisa' => (int) round($init['amount'] * 100),
                'key_id' => $init['config']['key_id'],
                'order_id' => $order->id,
                'currency' => 'INR',
            ]);
        } catch (\Throwable $e) {
            if ($payment) {
                $this->paymentService->markFailed($payment, $e->getMessage());
            }

            return back()->withErrors(['checkout' => 'Razorpay initialisation failed: '.$e->getMessage()])->withInput();
        }
    }

    /**
     * If the session has an in-flight Razorpay order that still matches the
     * current cart, return it so the interrupted checkout can be resumed.
     */
    protected function resumableRazorpayOrder(User $user, Cart $cart): ?Order
    {
        $stored = session('razorpay_inflight');

        if (! $stored || ! isset($stored['order_id'], $stored['cart_hash'])) {
            return null;
        }

        $order = Order::find($stored['order_id']);

        if (! $order || $order->user_id !== $user->id) {
            session()->forget('razorpay_inflight');

            return null;
        }

        if (! in_array($order->payment_status, ['pending', 'processing'], true) || $order->order_status !== 'pending') {
            session()->forget('razorpay_inflight');

            return null;
        }

        if ($this->razorpayCartHash($order) !== $stored['cart_hash']) {
            session()->forget('razorpay_inflight');

            // The cart changed before the customer returned: the old pending
            // order can never be paid for as-is, so cancel it and give the
            // stock back (prevents abandoned Razorpay orders from leaking stock).
            $this->cancelAbandonedRazorpayCheckout($order);

            return null;
        }

        return $order;
    }

    /**
     * Latest pending Razorpay order for a user that still matches the given cart
     * exactly. Called after the cart row lock so a concurrent first submission
     * (which committed while we waited) is resumed rather than duplicated.
     */
    protected function resumableRazorpayOrderForCart(User $user, Cart $cart): ?Order
    {
        $order = Order::where('user_id', $user->id)
            ->where('payment_method', 'razorpay')
            ->whereIn('payment_status', ['pending', 'processing'])
            ->where('order_status', 'pending')
            ->latest()
            ->first();

        if (! $order) {
            return null;
        }

        if ($this->razorpayCartHash($order) !== $this->cartLineHash($cart)) {
            $this->cancelAbandonedRazorpayCheckout($order);

            return null;
        }

        return $order;
    }

    /**
     * Cancel a Razorpay checkout whose payment was never confirmed, restoring
     * the reserved stock. Best-effort: never throws into the checkout flow.
     */
    protected function cancelAbandonedRazorpayCheckout(Order $order): void
    {
        try {
            if ($order->payment_method === 'razorpay'
                && in_array($order->payment_status, ['pending', 'processing'], true)
                && $order->order_status === 'pending') {
                $this->orderService->cancelOrder($order, 'Checkout was not completed; payment cancelled and stock restored.');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not cancel an abandoned Razorpay checkout.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark a Razorpay payment as failed and immediately release the order's
     * reserved stock by cancelling the still-open order. Without this a failed
     * callback would leave the customer's stock reserved until the expiry sweep.
     */
    protected function failRazorpayCheckout(Order $order, Payment $payment, string $reason): void
    {
        $this->paymentService->markFailed($payment, $reason);

        try {
            if ($order->fresh()->isCancellable()) {
                $this->orderService->cancelOrder($order, 'Payment was not completed; stock restored.');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not cancel a failed Razorpay checkout.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        $this->forgetInFlightRazorpayOrder($order);

        $this->notifyCustomer($order, 'paymentFailed');
    }

    /**
     * Stable fingerprint of an order's line items so a later checkout can be
     * told apart from a genuinely new cart.
     */
    protected function razorpayCartHash(Order $order): string
    {
        $rows = $order->items()
            ->orderBy('product_id')
            ->get(['product_id', 'product_variant_id', 'quantity'])
            ->map(fn ($item) => [$item->product_id, $item->product_variant_id, $item->quantity])
            ->all();

        return $this->lineRowsHash($rows);
    }

    protected function cartLineHash(Cart $cart): string
    {
        $rows = $cart->items()
            ->orderBy('product_id')
            ->get(['product_id', 'product_variant_id', 'quantity'])
            ->map(fn ($item) => [$item->product_id, $item->product_variant_id, $item->quantity])
            ->all();

        return $this->lineRowsHash($rows);
    }

    protected function lineRowsHash(array $rows): string
    {
        return hash('sha256', json_encode($rows));
    }

    /**
     * When the cart is empty at submission time the checkout is stale: either a
     * previous submission already committed the order (and cleared the cart) or
     * the payment is still pending. Surface the pending order instead of a
     * confusing "Your cart is empty." failure.
     */
    protected function emptyCartResponse(User $user): RedirectResponse
    {
        $recent = Order::where('user_id', $user->id)
            ->whereIn('payment_status', ['pending', 'processing'])
            ->where('order_status', 'pending')
            ->latest()
            ->first();

        return $recent
            ? redirect()->route('checkout.pending', $recent)
            : back()->withErrors(['checkout' => 'Your cart is empty.'])->withInput();
    }

    protected function orderFailureResponse(User $user, \RuntimeException $e): RedirectResponse
    {
        if ($e->getMessage() === 'Your cart is empty.') {
            return $this->emptyCartResponse($user);
        }

        return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
    }

    public function verify(Request $request): RedirectResponse
    {
        $orderId = $request->input('order_id');

        $order = $orderId ? Order::find($orderId) : null;

        // Only the owner of an order may confirm its payment: without this a
        // third party could flip another user's order to failed via the
        // (CSRF-exempt) callback route.
        if (! $order || $order->user_id !== auth('web')->id()) {
            abort(403);
        }

        // The callback is Razorpay-specific; never let it touch COD orders.
        if ($order->payment_method !== 'razorpay') {
            abort(422);
        }

        try {
            $request->validate([
                'razorpay_order_id' => 'required|string',
                'razorpay_payment_id' => 'required|string',
                'razorpay_signature' => 'required|string',
            ]);

            $payment = $order->payments()->latest()->first();

            if (! $payment) {
                return redirect()->route('checkout.failed', $order);
            }

            $gateway = new RazorpayGateway;

            if ($gateway->verify($payment, $request->all())) {
                // Defence-in-depth: when the payment can be fetched from Razorpay,
                // confirm it was actually captured for the order total. Only
                // applies when the gateway returns the very payment we verified
                // (id matches) — otherwise a best-effort fetch of a foreign/order
                // object is ignored and the verified signature stands. A failed
                // fetch also falls back to the signature so a transient gateway
                // issue never blocks a legitimate payment.
                $captured = $gateway->fetchPayment($request->input('razorpay_payment_id'));

                if ($captured !== null && ($captured['id'] ?? null) === $request->input('razorpay_payment_id')) {
                    if (($captured['status'] ?? '') !== 'captured') {
                        $this->failRazorpayCheckout($order, $payment, 'Payment was not captured by the gateway.');

                        return redirect()->route('checkout.failed', $order);
                    }

                    $expectedPaisa = (int) round((float) $order->amount_due * 100);
                    if ((int) ($captured['amount'] ?? 0) !== $expectedPaisa) {
                        $this->failRazorpayCheckout($order, $payment, 'Captured amount does not match the order total.');

                        return redirect()->route('checkout.failed', $order);
                    }
                }

                $this->paymentService->markPaid($payment);

                // Record the captured Razorpay payment id alongside the order
                // id held in payment_reference: the gateway refund endpoint
                // requires the pay_* id, and both fulfilment paths must be able
                // to refund after the browser never returns to the callback.
                $payment->forceFill([
                    'gateway_response' => array_merge((array) $payment->gateway_response, [
                        'webhook_payment_id' => $request->input('razorpay_payment_id'),
                    ]),
                ])->save();

                // Payment is now confirmed: the customer's cart is released and
                // the in-flight Razorpay checkout (if any) is settled so a later
                // submission starts a fresh order.
                $this->cartService->clear();
                $this->forgetInFlightRazorpayOrder($order);

                // Auto-push to ShipMojo after confirmed online payment
                if ((bool) setting('shipmojo_auto_push', false)) {
                    PushOrderToShipMojo::dispatch($order);
                }

                // Server-verified online payment is the authoritative purchase
                // conversion point. Repeated/concurrent verify calls resolve
                // to the same ledger row.
                $this->conversionService->recordPurchase($order->refresh());

                $this->dispatchMetaCapiPurchase($order, $request);

                $this->notifyCustomer($order, 'paymentSuccessful');

                return redirect()->route('checkout.success', $order);
            }

            $this->failRazorpayCheckout($order, $payment, 'Signature verification failed.');

            return redirect()->route('checkout.failed', $order);
        } catch (\Throwable $e) {
            Log::error('Payment verification error', ['error' => $e->getMessage()]);

            $order = $orderId ? Order::find($orderId) : null;

            return $order
                ? redirect()->route('checkout.pending', $order)
                : redirect()->route('home');
        }
    }

    /**
     * Clear the session's in-flight Razorpay checkout when it points at the
     * given order (i.e. it has been paid or definitively failed).
     */
    protected function forgetInFlightRazorpayOrder(Order $order): void
    {
        if (session('razorpay_inflight.order_id') === $order->id) {
            session()->forget('razorpay_inflight');
        }
    }

    public function success(Order $order): View
    {
        abort_if($order->user_id !== auth('web')->id(), 403);

        $order->load(['items', 'payments', 'shipments.trackingEvents']);

        $shippingInfoPayload = null;
        $paymentInfoPayload = null;

        if ($order->payment_method === 'cod') {
            $shippingInfoPayload = $this->ecommerceDataService->addShippingInfo($order);
            $paymentInfoPayload = $this->ecommerceDataService->addPaymentInfo($order, 'cod');
        }

        $purchasePayload = $this->ecommerceDataService->purchaseEligible($order)
            ? $this->ecommerceDataService->purchase($order)
            : null;

        // Browser Pixel Purchase receives the same deterministic event_id the
        // server CAPI uses, so Meta deduplicates the two representations. It is
        // gated on the same authoritative purchaseEligible() rule as CAPI, so
        // unverified/pending online orders never fire Purchase while COD
        // remains eligible at order creation.
        $metaEventId = (setting('meta_pixel_id') && $this->ecommerceDataService->purchaseEligible($order))
            ? $this->metaCapiService->eventId($order)
            : null;

        return view('storefront.checkout.success', compact(
            'order', 'shippingInfoPayload', 'paymentInfoPayload', 'purchasePayload', 'metaEventId'
        ));
    }

    /**
     * Queue server-side Meta Purchase delivery for a newly committed purchase
     * conversion. Runs only when Meta CAPI is configured; never blocks,
     * fails, or rolls back the order when Meta is unavailable.
     */
    protected function dispatchMetaCapiPurchase(Order $order, Request $request): void
    {
        if (! $this->metaCapiService->isConfigured()) {
            return;
        }

        SendMetaCapiPurchase::dispatch(
            $order,
            route('checkout.success', $order),
            $request->cookies->get('_fbp'),
            $request->cookies->get('_fbc'),
            $request->ip(),
            $request->userAgent(),
        );
    }

    public function failed(Order $order): View
    {
        abort_if($order->user_id !== auth('web')->id(), 403);

        $order->load(['items', 'payments']);

        return view('storefront.checkout.failed', compact('order'));
    }

    public function pending(Order $order): View
    {
        abort_if($order->user_id !== auth('web')->id(), 403);

        $order->load(['items', 'payments']);

        return view('storefront.checkout.pending', compact('order'));
    }

    /**
     * Dispatch a customer-facing order notification, swallowing and logging any
     * delivery failure so it never blocks or rolls back checkout.
     */
    protected function notifyCustomer(Order $order, string $method): void
    {
        try {
            if ($order->user) {
                app(NotificationService::class)->{$method}($order);
            }
        } catch (\Throwable $e) {
            Log::warning('Order notification could not be delivered.', [
                'order' => $order->order_number,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function isNewAddressForUser(User $user, array $shipping): bool
    {
        return ! Address::where('user_id', $user->id)
            ->where('full_name', $shipping['full_name'])
            ->where('address_line1', $shipping['address_line1'])
            ->where('pincode', $shipping['pincode'])
            ->exists();
    }
}
