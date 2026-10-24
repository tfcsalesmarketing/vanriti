<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WhatsAppCheckoutSession;
use App\Services\Payments\PaymentService;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppCheckoutService
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService,
        protected WhatsAppShippingService $shippingService,
        protected OrderService $orderService,
        protected PaymentService $paymentService,
    ) {}

    /**
     * Handle incoming cart submitted by customer from Meta Catalogue / multi-product message.
     *
     * @param  array<int, array{product_retailer_id: string, quantity: int|string, item_price?: mixed}>  $incomingItems
     */
    public function handleIncomingCart(string $phone, ?string $waId, array $incomingItems, string $messageId): array
    {
        $phone = $this->apiService->formatRecipientPhone($phone);

        if (empty($incomingItems)) {
            $msg = "Your cart is empty. Please select products from our catalogue to place an order. 🌿";
            $this->apiService->sendTextMessage($phone, $msg);
            return ['success' => false, 'message' => $msg];
        }

        // 1. Authoritative resolution of products and variants
        $resolvedItems = [];
        $errors = [];
        $merchandiseSubtotal = 0.0;

        foreach ($incomingItems as $idx => $item) {
            $retailerId = trim((string) ($item['product_retailer_id'] ?? ''));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            $resolution = $this->resolveProductItem($retailerId);

            if (! $resolution['found']) {
                $errors[] = "Product '{$retailerId}' was not found in our catalogue.";
                continue;
            }

            $product = $resolution['product'];
            $variant = $resolution['variant'];

            if ($product->status !== 'active') {
                $errors[] = "'{$product->name}' is currently unavailable.";
                continue;
            }

            if ($variant && $variant->status !== 'active') {
                $errors[] = "The selected option for '{$product->name}' is currently unavailable.";
                continue;
            }

            // Check stock authoritatively
            $availableStock = $variant ? (int) $variant->stock : (int) $product->stock;
            if ($availableStock < $quantity) {
                if ($availableStock <= 0) {
                    $errors[] = "'{$product->name}' is currently out of stock.";
                } else {
                    $errors[] = "Only {$availableStock} unit(s) of '{$product->name}' are available.";
                }
                continue;
            }

            // Authoritative price
            $unitPrice = (float) ($variant ? $variant->selling_price : $product->selling_price);
            $mrp = (float) ($variant ? ($variant->mrp ?? $variant->selling_price) : ($product->mrp ?? $product->selling_price));
            $lineTotal = round($unitPrice * $quantity, 2);
            $merchandiseSubtotal += $lineTotal;

            $resolvedItems[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'retailer_id' => $retailerId,
                'name' => $product->name . ($variant ? ' (' . $variant->name . ')' : ''),
                'sku' => $variant?->sku ?? $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'mrp' => $mrp,
                'gst_rate' => (float) ($variant?->gst_rate ?? $product->gst_rate ?? 0),
                'line_total' => $lineTotal,
            ];
        }

        if (! empty($errors) || empty($resolvedItems)) {
            $errorMsg = "We noticed an issue with your selected items:\n\n"
                . implode("\n", array_map(fn($e) => "• " . $e, $errors))
                . "\n\nPlease update your selection or browse our catalogue again. 🌿";

            $sendRes = $this->apiService->sendTextMessage($phone, $errorMsg);
            if (! ($sendRes['success'] ?? false)) {
                Log::error('Failed to send WhatsApp cart error message to customer', [
                    'phone' => $phone,
                    'error' => $sendRes['message'] ?? 'Unknown send error',
                    'raw_errors' => $errors,
                ]);
            }

            return ['success' => false, 'errors' => $errors];
        }

        // 2. Calculate WhatsApp isolated shipping for the incoming cart
        $shippingCalc = $this->shippingService->calculate($merchandiseSubtotal);
        $shippingCharge = $shippingCalc['charge'];
        $grandTotal = round($merchandiseSubtotal + $shippingCharge, 2);

        $cartData = [
            'items' => $resolvedItems,
            'subtotal' => $merchandiseSubtotal,
            'shipping_charge' => $shippingCharge,
            'grand_total' => $grandTotal,
        ];

        // Check if customer already has an active session we can update
        $existingSession = WhatsAppCheckoutSession::findActiveForPhone($phone);

        if ($existingSession && ! $existingSession->isTerminal()) {
            $existingSession->update([
                'cart_data' => $cartData,
                'subtotal' => $merchandiseSubtotal,
                'shipping_charge' => $shippingCharge,
                'grand_total' => $grandTotal,
                'last_message_id' => $messageId,
            ]);

            // If customer has already entered address/is reviewing order:
            if (! empty($existingSession->customer_name) && ! empty($existingSession->pincode) && ! empty($existingSession->address_data['address_line1'])) {
                $existingSession->transitionTo(WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION);

                $updatedMsg = "🌿 *Your order has been updated!*\nYour cart now contains only the new item(s).";
                $this->apiService->sendTextMessage($phone, $updatedMsg);

                $this->sendOrderReviewMessage($existingSession);

                return ['success' => true, 'session' => $existingSession, 'action' => 'cart_updated'];
            }

            if (empty($existingSession->customer_name)) {
                $prompt = "Welcome to VANRITI! 🌿\nPlease enter your full name to continue with your order.";
                $this->apiService->sendTextMessage($phone, $prompt);
                return ['success' => true, 'session' => $existingSession, 'action' => 'name_requested'];
            }

            if (empty($existingSession->pincode)) {
                $prompt = "Please enter your 6-digit delivery PIN code.";
                $this->apiService->sendTextMessage($phone, $prompt);
                return ['success' => true, 'session' => $existingSession, 'action' => 'pincode_requested'];
            }

            $prompt = "Please enter your complete delivery address.";
            $this->apiService->sendTextMessage($phone, $prompt);
            return ['success' => true, 'session' => $existingSession, 'action' => 'address_requested'];
        }

        // 3. Create fresh checkout session when no active session exists
        $session = WhatsAppCheckoutSession::createForCustomer($phone, $waId, $cartData);
        $session->update([
            'subtotal' => $merchandiseSubtotal,
            'shipping_charge' => $shippingCharge,
            'grand_total' => $grandTotal,
            'status' => WhatsAppCheckoutSession::STATUS_COLLECTING_NAME,
            'last_message_id' => $messageId,
        ]);

        // Prompt for customer name (Step 4)
        $prompt = "Welcome to VANRITI! 🌿\nPlease enter your full name to continue with your order.";
        $sendRes = $this->apiService->sendTextMessage($phone, $prompt);
        if (! ($sendRes['success'] ?? false)) {
            Log::error('Failed to send WhatsApp name prompt to customer', [
                'phone' => $phone,
                'session_id' => $session->id,
                'error' => $sendRes['message'] ?? 'Unknown send error',
            ]);
        }

        return ['success' => true, 'session' => $session];
    }

    /**
     * Process conversational customer input (text or button reply).
     */
    public function handleIncomingMessage(string $phone, string $text, string $messageId = ''): array
    {
        return $this->processCustomerInput($phone, $text, null, $messageId ?: ('gen_' . uniqid('', true)));
    }

    /**
     * Process conversational customer input (text or button reply).
     */
    public function processCustomerInput(string $phone, ?string $text, ?string $buttonId, string $messageId): array
    {
        $phone = $this->apiService->formatRecipientPhone($phone);
        $text = trim((string) $text);
        $action = strtoupper(trim((string) ($buttonId ?: $text)));

        $session = WhatsAppCheckoutSession::findActiveForPhone($phone);

        if (! $session) {
            // Check if customer pasted or shared a product link
            if (preg_match('#(?:products/|product/)([a-z0-9-]+)#i', $text, $urlMatch)) {
                $slug = $urlMatch[1];
                $res = $this->resolveProductItem($slug);
                if ($res['found']) {
                    return $this->handleIncomingCart($phone, $phone, [
                        ['product_retailer_id' => $res['variant']?->sku ?? $res['product']->sku, 'quantity' => 1],
                    ], $messageId);
                }
            }

            // Check if customer typed a product SKU directly
            $res = $this->resolveProductItem($text);
            if ($res['found']) {
                return $this->handleIncomingCart($phone, $phone, [
                    ['product_retailer_id' => $res['variant']?->sku ?? $res['product']->sku, 'quantity' => 1],
                ], $messageId);
            }

            // Away Auto-Responder (if explicitly enabled in settings)
            if (setting('whatsapp_away_message_enabled', false)) {
                $awayText = (string) setting('whatsapp_away_message_text', '');
                if ($awayText !== '') {
                    $this->apiService->sendTextMessage($phone, $awayText);
                    return ['success' => true, 'action' => 'away_message_sent'];
                }
            }

            // Catalogue on explicit keyword (if bot auto-replies are enabled in settings)
            if (setting('whatsapp_bot_auto_reply_enabled', false) && in_array($action, ['CATALOGUE', 'CATALOG', 'SHOP'], true)) {
                $this->apiService->sendCatalogueMessage($phone);
                return ['success' => true, 'action' => 'catalogue_sent'];
            }

            // Default: Do NOT auto-reply. Let message sit cleanly in admin inbox for human staff.
            return ['success' => true, 'action' => 'inbox_queued'];
        }

        // Global Cancel check
        if (in_array($action, ['CANCEL', 'CANCEL_ORDER', 'STOP', 'EXIT', 'QUIT'], true)) {
            $session->transitionTo(WhatsAppCheckoutSession::STATUS_CANCELLED);
            $msg = "Your VANRITI order has been cancelled. Feel free to browse our catalogue anytime! 🌿";
            $this->apiService->sendTextMessage($phone, $msg);
            return ['success' => true, 'action' => 'cancelled'];
        }

        // Process according to state machine
        switch ($session->status) {
            case WhatsAppCheckoutSession::STATUS_COLLECTING_NAME:
                return $this->handleCollectingName($session, $text, $messageId);

            case WhatsAppCheckoutSession::STATUS_COLLECTING_PINCODE:
                return $this->handleCollectingPincode($session, $text, $messageId);

            case WhatsAppCheckoutSession::STATUS_COLLECTING_ADDRESS:
                return $this->handleCollectingAddress($session, $text, $messageId);

            case WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION:
                return $this->handleOrderConfirmation($session, $action, $messageId);

            case WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD:
                return $this->handlePaymentMethodSelection($session, $action, $messageId);

            case WhatsAppCheckoutSession::STATUS_PAYMENT_PENDING:
                return $this->handlePaymentPendingInquiry($session, $action, $messageId);

            default:
                $this->apiService->sendTextMessage(
                    $phone,
                    "Your session has ended. Send *SHOP* to explore products and start a new order. 🌿"
                );
                return ['success' => true, 'action' => 'session_reset'];
        }
    }

    /**
     * Step 4: Validate name and ask for PIN code.
     */
    protected function handleCollectingName(WhatsAppCheckoutSession $session, string $text, string $messageId): array
    {
        $cleanName = preg_replace('/[^\p{L}\s\.\'-]/u', '', $text);
        if (mb_strlen(trim($cleanName)) < 2) {
            $this->apiService->sendTextMessage(
                $session->phone,
                "Please enter a valid full name to continue with your order."
            );
            return ['success' => false, 'error' => 'invalid_name'];
        }

        $session->update([
            'customer_name' => ucwords(strtolower(trim($cleanName))),
            'last_message_id' => $messageId,
        ]);

        $session->transitionTo(WhatsAppCheckoutSession::STATUS_COLLECTING_PINCODE);

        // Step 5: Ask for PIN code
        $sendRes = $this->apiService->sendTextMessage(
            $session->phone,
            "Please enter your 6-digit delivery PIN code."
        );

        if (! ($sendRes['success'] ?? false)) {
            Log::error('Failed to send WhatsApp pincode prompt', [
                'phone' => $session->phone,
                'session_id' => $session->id,
                'error' => $sendRes['message'] ?? 'Unknown error',
            ]);
        }

        return ['success' => true, 'action' => 'pincode_requested'];
    }

    /**
     * Step 5: Validate 6-digit PIN code and delivery serviceability.
     */
    protected function handleCollectingPincode(WhatsAppCheckoutSession $session, string $text, string $messageId): array
    {
        $pincode = preg_replace('/[^0-9]/', '', $text);

        if (! $this->shippingService->isValidPincodeFormat($pincode)) {
            $this->apiService->sendTextMessage(
                $session->phone,
                "Please enter a valid 6-digit Indian delivery PIN code."
            );
            return ['success' => false, 'error' => 'invalid_pincode_format'];
        }

        if (! $this->shippingService->isServiceable($pincode)) {
            $this->apiService->sendTextMessage(
                $session->phone,
                "Sorry, we currently do not deliver to PIN code {$pincode}. Please provide an alternative delivery PIN code."
            );
            return ['success' => false, 'error' => 'unserviceable_pincode'];
        }

        // Best effort postal lookup for city/state
        $postal = $this->shippingService->lookupPostalDetails($pincode);

        $addr = (array) ($session->address_data ?? []);
        $addr['pincode'] = $pincode;
        if (! empty($postal['city'])) $addr['city'] = $postal['city'];
        if (! empty($postal['state'])) $addr['state'] = $postal['state'];

        $session->update([
            'pincode' => $pincode,
            'address_data' => $addr,
            'last_message_id' => $messageId,
        ]);

        $session->transitionTo(WhatsAppCheckoutSession::STATUS_COLLECTING_ADDRESS);

        // Step 6: Collect complete delivery address
        $cityNote = ! empty($postal['city']) ? " (City: {$postal['city']}, State: {$postal['state']})" : '';
        $this->apiService->sendTextMessage(
            $session->phone,
            "Please enter your complete delivery address, including house/flat number, building, street, locality and landmark{$cityNote}."
        );

        return ['success' => true, 'action' => 'address_requested'];
    }

    /**
     * Step 6: Collect address & calculate order review summary.
     */
    protected function handleCollectingAddress(WhatsAppCheckoutSession $session, string $text, string $messageId): array
    {
        if (strlen($text) < 6) {
            $this->apiService->sendTextMessage(
                $session->phone,
                "Please provide a complete address with house/flat number and street name so we can deliver your order smoothly."
            );
            return ['success' => false, 'error' => 'incomplete_address'];
        }

        $addr = (array) ($session->address_data ?? []);
        $addr['address_line1'] = $text;
        $addr['city'] = $addr['city'] ?? 'City';
        $addr['state'] = $addr['state'] ?? 'State';
        $addr['pincode'] = $session->pincode ?: ($addr['pincode'] ?? '110001');

        // Recalculate order summary (Step 7)
        $subtotal = (float) $session->subtotal;
        $shippingCalc = $this->shippingService->calculate($subtotal);
        $shippingCharge = $shippingCalc['charge'];
        $grandTotal = round($subtotal + $shippingCharge, 2);

        $session->update([
            'address_data' => $addr,
            'shipping_charge' => $shippingCharge,
            'grand_total' => $grandTotal,
            'last_message_id' => $messageId,
        ]);

        $session->transitionTo(WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION);

        // Send Order Review message with buttons
        $this->sendOrderReviewMessage($session);

        return ['success' => true, 'action' => 'review_presented'];
    }

    /**
     * Present the customer with readable order summary and interactive buttons.
     */
    public function sendOrderReviewMessage(WhatsAppCheckoutSession $session): void
    {
        $addr = (array) ($session->address_data ?? []);
        $items = (array) ($session->cart_data['items'] ?? []);

        $itemsText = '';
        foreach ($items as $item) {
            $linePrice = format_price($item['line_total']);
            $itemsText .= "• {$item['name']} × {$item['quantity']} — {$linePrice}\n";
        }

        $subtotal = (float) $session->subtotal;
        $shippingCalc = $this->shippingService->calculate($subtotal);
        $freeThreshold = (float) $shippingCalc['free_threshold'];
        $hasDeliveryCharge = $session->shipping_charge > 0;

        $deliveryText = $hasDeliveryCharge ? format_price($session->shipping_charge) : 'FREE 🎉';

        $summaryBody = "VANRITI — ORDER REVIEW 🌿\n\n"
            . "Customer name: {$session->customer_name}\n"
            . "Delivery address:\n"
            . "{$addr['address_line1']}\n"
            . "{$addr['city']}, {$addr['state']} - {$session->pincode}\n\n"
            . "Items:\n" . $itemsText . "\n"
            . "Subtotal: " . format_price($session->subtotal) . "\n"
            . "Delivery: {$deliveryText}\n"
            . "Final total: " . format_price($session->grand_total) . "\n";

        if ($hasDeliveryCharge && $subtotal < $freeThreshold) {
            $diff = round($freeThreshold - $subtotal, 2);
            $summaryBody .= "\n💡 *Standard delivery of " . format_price($session->shipping_charge) . " applies.*"
                . "\nAdd items worth *" . format_price($diff) . "* more to get *FREE Delivery*! 🌿\n\n"
                . "Click *Add More Items* below to add products from our catalogue, or *Confirm Order* to proceed.";

            $buttons = [
                ['id' => 'CONFIRM_ORDER', 'title' => 'Confirm Order'],
                ['id' => 'ADD_ITEMS', 'title' => 'Add More Items'],
                ['id' => 'EDIT_ADDRESS', 'title' => 'Edit Address'],
            ];
            $footerText = 'Reply CONFIRM, ADD, or EDIT';
        } else {
            $summaryBody .= "\n🎉 *You unlocked FREE Delivery!*\n\n"
                . "Please confirm your order details to proceed to payment.";

            $buttons = [
                ['id' => 'CONFIRM_ORDER', 'title' => 'Confirm Order'],
                ['id' => 'EDIT_ADDRESS', 'title' => 'Edit Address'],
                ['id' => 'CANCEL_ORDER', 'title' => 'Cancel'],
            ];
            $footerText = 'Reply CONFIRM, EDIT, or CANCEL';
        }

        $res = $this->apiService->sendInteractiveButtonMessage(
            $session->phone,
            $summaryBody,
            $buttons,
            headerText: 'VANRITI — Order Review',
            footerText: $footerText
        );

        if (! $res['success']) {
            $textPrompt = $hasDeliveryCharge && $subtotal < $freeThreshold
                ? "\n\nReply *CONFIRM* to proceed, *ADD* to add more items, or *EDIT* to change address."
                : "\n\nReply *CONFIRM* to proceed, *EDIT* to change address, or *CANCEL*.";

            $this->apiService->sendTextMessage(
                $session->phone,
                $summaryBody . $textPrompt
            );
        }
    }

    /**
     * Handle Customer confirmation, item additions, or address edit.
     */
    protected function handleOrderConfirmation(WhatsAppCheckoutSession $session, string $action, string $messageId): array
    {
        if (in_array($action, ['ADD_ITEMS', 'ADD', 'ADD_MORE', 'ADD MORE', 'EDIT_ORDER', 'EDIT ORDER', 'MORE ITEMS', 'CATALOGUE', 'SHOP'], true)) {
            $subtotal = (float) $session->subtotal;
            $shippingCalc = $this->shippingService->calculate($subtotal);
            $threshold = (float) $shippingCalc['free_threshold'];
            $diff = max(0, round($threshold - $subtotal, 2));

            $prompt = "🌿 *Update Your Cart*\n\n"
                . ($diff > 0 ? "You need just *" . format_price($diff) . "* more to unlock *FREE Delivery*!\n\n" : "")
                . "Browse our catalogue below and send your cart with the items you'd like in your order. 🌿";

            $this->apiService->sendTextMessage($session->phone, $prompt);
            $this->apiService->sendCatalogueMessage($session->phone);

            return ['success' => true, 'action' => 'catalogue_sent_for_add_items'];
        }

        if (in_array($action, ['EDIT_ADDRESS', 'EDIT', 'CHANGE'], true)) {
            $session->transitionTo(WhatsAppCheckoutSession::STATUS_COLLECTING_PINCODE);
            $this->apiService->sendTextMessage(
                $session->phone,
                "Please enter your 6-digit delivery PIN code to update your address."
            );
            return ['success' => true, 'action' => 'edit_requested'];
        }

        if (in_array($action, ['CONFIRM_ORDER', 'CONFIRM', 'OK', 'YES', 'PROCEED', 'CONTINUE'], true)) {
            $session->transitionTo(WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD);

            $paymentBody = "How would you like to pay for your VANRITI order of " . format_price($session->grand_total) . "?";

            $buttons = [
                ['id' => 'PAY_COD', 'title' => 'Cash on Delivery'],
                ['id' => 'PAY_ONLINE', 'title' => 'Online Payment'],
            ];

            $res = $this->apiService->sendInteractiveButtonMessage(
                $session->phone,
                $paymentBody,
                $buttons,
                headerText: 'Payment Method',
                footerText: 'Reply COD or ONLINE'
            );

            if (! $res['success']) {
                $this->apiService->sendTextMessage(
                    $session->phone,
                    $paymentBody . "\n\nReply with *COD* for Cash on Delivery or *ONLINE* for Razorpay/UPI/Cards."
                );
            }

            return ['success' => true, 'action' => 'payment_method_prompted'];
        }

        $this->apiService->sendTextMessage(
            $session->phone,
            "Please confirm your order by tapping *Confirm Order* (or reply *CONFIRM*), reply *ADD* to add more items, or *EDIT* to update your address."
        );

        return ['success' => false, 'error' => 'invalid_confirmation_choice'];
    }

    /**
     * Handle payment selection: Option A: COD, Option B: Razorpay.
     */
    protected function handlePaymentMethodSelection(WhatsAppCheckoutSession $session, string $action, string $messageId): array
    {
        if (in_array($action, ['BACK', 'EDIT', 'EDIT_ORDER', 'ADD_ITEMS', 'ADD MORE', 'ADD'], true)) {
            $session->transitionTo(WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION);
            $this->sendOrderReviewMessage($session);
            return ['success' => true, 'action' => 'review_represented'];
        }

        if (in_array($action, ['PAY_COD', 'COD', 'CASH ON DELIVERY', 'CASH'], true)) {
            return $this->finalizeCodOrder($session);
        }

        if (in_array($action, ['PAY_ONLINE', 'ONLINE', 'ONLINE PAYMENT', 'ONLINE_PAYMENT', 'PAY ONLINE', 'RAZORPAY', 'UPI', 'PREPAID'], true)) {
            return $this->initializeRazorpayOrder($session);
        }

        $this->apiService->sendTextMessage(
            $session->phone,
            "Please choose a payment method:\nReply *COD* for Cash on Delivery or *ONLINE* for secure Razorpay payment."
        );

        return ['success' => false, 'error' => 'invalid_payment_method'];
    }

    /**
     * Option A: Cash on Delivery finalization.
     */
    public function finalizeCodOrder(WhatsAppCheckoutSession $session): array
    {
        if ($session->order_id && $session->status === WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED) {
            return ['success' => true, 'already_confirmed' => true];
        }

        try {
            $order = $this->createOrderFromSession($session, 'cod');

            $order->update([
                'payment_status' => 'pending',
                'order_status' => 'confirmed',
            ]);

            $session->transitionTo(WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED, [
                'order_id' => $order->id,
                'payment_method' => 'cod',
            ]);

            // Order confirmation message
            $confirmation = "🎉 Your VANRITI order has been placed successfully!\n\n"
                . "Order ID: {$order->order_number}\n"
                . "Order total: " . format_price($order->grand_total) . "\n"
                . "Payment method: Cash on Delivery\n\n"
                . "We'll keep you updated on your order.\n"
                . "Thank you for shopping with VANRITI! 🌿";

            $this->apiService->sendTextMessage($session->phone, $confirmation);

            return ['success' => true, 'order' => $order];
        } catch (\Throwable $e) {
            Log::error('WhatsApp COD order placement error: ' . $e->getMessage(), ['session_id' => $session->id]);

            $this->apiService->sendTextMessage(
                $session->phone,
                "Could not finalize your order: {$e->getMessage()}\nPlease try again or contact support."
            );

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Option B: Razorpay Online Payment initiation.
     */
    public function initializeRazorpayOrder(WhatsAppCheckoutSession $session): array
    {
        try {
            // Create pending order if not already created
            $order = $session->order;
            if (! $order) {
                $order = $this->createOrderFromSession($session, 'razorpay');
            }

            // Create payment record and Razorpay order
            $payment = $order->payments()->where('method', 'razorpay')->latest()->first();
            if (! $payment || $payment->status === 'failed') {
                $payment = $this->paymentService->createPayment($order, 'razorpay');
            }

            $gateway = new RazorpayGateway();
            $rpOrder = $gateway->createOrder($order, $payment);

            $session->transitionTo(WhatsAppCheckoutSession::STATUS_PAYMENT_PENDING, [
                'order_id' => $order->id,
                'razorpay_order_id' => $rpOrder['id'],
                'payment_method' => 'razorpay',
            ]);

            $paymentUrl = $this->resolveRazorpayPaymentUrl($session, $order, $gateway);

            $msg = "Please complete your payment of " . format_price($order->grand_total) . " for VANRITI order {$order->order_number}:\n\n"
                . "👉 {$paymentUrl}\n\n"
                . "Click the link above to open Razorpay and pay directly via UPI (GPay, PhonePe, Paytm), Cards, or NetBanking. Once paid, your order will be confirmed immediately. 🌿";

            $this->apiService->sendTextMessage($session->phone, $msg);

            return ['success' => true, 'order' => $order, 'payment_url' => $paymentUrl];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Razorpay initialization error: ' . $e->getMessage(), ['session_id' => $session->id]);

            $this->apiService->sendTextMessage(
                $session->phone,
                "Could not initialize online payment: {$e->getMessage()}\nYou can reply *COD* to place your order with Cash on Delivery instead."
            );

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resolve direct Razorpay payment link or fallback to secure web checkout URL.
     */
    protected function resolveRazorpayPaymentUrl(WhatsAppCheckoutSession $session, Order $order, RazorpayGateway $gateway): string
    {
        $fallbackUrl = route('checkout.whatsapp.pay', ['token' => $session->session_token]);

        $payment = $order->payments()->where('method', 'razorpay')->latest()->first();
        $cachedLink = $payment?->gateway_response['payment_link_url'] ?? null;
        if (! empty($cachedLink)) {
            return $cachedLink;
        }

        $directUrl = $gateway->createPaymentLink(
            $order,
            $session->customer_name ?: 'Customer',
            $session->phone,
            $fallbackUrl
        );

        if ($directUrl) {
            if ($payment) {
                $payment->forceFill([
                    'gateway_response' => array_merge((array) $payment->gateway_response, [
                        'payment_link_url' => $directUrl,
                    ]),
                ])->save();
            }

            return $directUrl;
        }

        return $fallbackUrl;
    }

    /**
     * Handle payment verification for online WhatsApp orders.
     */
    public function handlePaymentSuccess(WhatsAppCheckoutSession $session, Payment $payment, Order $order): void
    {
        $session->transitionTo(WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED, [
            'payment_method' => 'razorpay',
        ]);

        $safeRef = $payment->gateway_response['webhook_payment_id'] ?? ($payment->payment_reference ?: 'Razorpay');

        $msg = "✅ Payment successful!\n\n"
            . "Your VANRITI order {$order->order_number} is confirmed.\n"
            . "Amount paid: " . format_price($order->grand_total) . "\n"
            . "Payment reference: {$safeRef}\n\n"
            . "Thank you for choosing VANRITI! 🌿";

        $this->apiService->sendTextMessage($session->phone, $msg);
    }

    /**
     * Handle payment failure message and retry link.
     */
    public function handlePaymentFailure(WhatsAppCheckoutSession $session, Order $order, ?string $reason = null): void
    {
        $gateway = new RazorpayGateway();
        $retryUrl = $this->resolveRazorpayPaymentUrl($session, $order, $gateway);

        $msg = "We couldn't complete the payment for VANRITI order {$order->order_number}.\n"
            . "Your order has not been confirmed as paid.\n\n"
            . "Please try again using the secure payment link:\n"
            . "👉 {$retryUrl}\n\n"
            . "Or reply *COD* to switch your order to Cash on Delivery. 🌿";

        $this->apiService->sendTextMessage($session->phone, $msg);
    }

    /**
     * Respond to inquiry during PAYMENT_PENDING state.
     */
    protected function handlePaymentPendingInquiry(WhatsAppCheckoutSession $session, string $action, string $messageId): array
    {
        if (in_array($action, ['COD', 'SWITCH TO COD', 'PAY COD'], true)) {
            return $this->finalizeCodOrder($session);
        }

        $order = $session->order;
        if ($order && $order->payment_status === 'paid') {
            $this->apiService->sendTextMessage(
                $session->phone,
                "Your VANRITI order {$order->order_number} is already paid and confirmed! 🌿"
            );
            return ['success' => true, 'action' => 'already_paid'];
        }

        $gateway = new RazorpayGateway();
        $paymentUrl = $order ? $this->resolveRazorpayPaymentUrl($session, $order, $gateway) : route('checkout.whatsapp.pay', ['token' => $session->session_token]);
        $msg = "Your payment is currently pending for order " . ($order?->order_number ?? '') . ".\n\n"
            . "Complete your payment directly with Razorpay here:\n👉 {$paymentUrl}\n\n"
            . "Or reply *COD* if you'd like to switch to Cash on Delivery.";

        $this->apiService->sendTextMessage($session->phone, $msg);

        return ['success' => true, 'action' => 'pending_reminder_sent'];
    }

    /**
     * Authoritatively creates an internal Order from the session data using OrderService.
     */
    public function createOrderFromSession(WhatsAppCheckoutSession $session, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($session, $paymentMethod) {
            $phone = $session->phone;

            // 1. Map/create customer user by phone
            $user = User::query()->where('phone', $phone)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $session->customer_name ?: 'WhatsApp Customer',
                    'phone' => $phone,
                    'status' => 'active',
                    'phone_verified_at' => now(),
                    'password' => bcrypt(Str::random(32)),
                ]);
            }

            // 2. Build ephemeral cart for authoritative OrderService validation
            $cart = Cart::create([
                'owner_type' => User::class,
                'owner_id' => $user->id,
            ]);

            $cartItems = $session->cart_data['items'] ?? [];
            foreach ($cartItems as $item) {
                $cart->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'mrp' => $item['mrp'] ?? $item['unit_price'],
                    'gst_rate' => $item['gst_rate'] ?? 0,
                ]);
            }

            $addr = (array) ($session->address_data ?? []);
            $pincode = $session->pincode ?: ($addr['pincode'] ?? '110001');

            $addressData = [
                'full_name' => $session->customer_name ?: 'WhatsApp Customer',
                'mobile' => $session->phone,
                'address_line1' => $addr['address_line1'] ?? 'Delivery Address',
                'address_line2' => $addr['address_line2'] ?? null,
                'landmark' => $addr['landmark'] ?? null,
                'city' => $addr['city'] ?? 'City',
                'state' => $addr['state'] ?? 'State',
                'pincode' => $pincode,
                'country' => 'India',
            ];

            $orderData = [
                'billing' => $addressData,
                'shipping' => $addressData,
                'shipping_method' => 'standard',
                'shipping_charge' => (float) $session->shipping_charge,
                'payment_method' => $paymentMethod,
                'checkout_source' => 'whatsapp',
                'coupon_code' => null,
                'notes' => 'WhatsApp Commerce Checkout (Customer: ' . $session->phone . ')',
            ];

            $order = $this->orderService->placeOrder($user, $cart, $orderData, clearCart: true);

            $session->update([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
            ]);

            return $order;
        });
    }

    /**
     * Resolve product/variant from retailer identifier.
     * Matches variant SKU, product SKU, product ID, variant ID, barcode.
     *
     * @return array{found: bool, product: ?Product, variant: ?ProductVariant}
     */
    public function resolveProductItem(string $identifier): array
    {
        $id = trim($identifier);
        if ($id === '') {
            return ['found' => false, 'product' => null, 'variant' => null];
        }

        $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($id));

        // 1. Try matching Variant by SKU (exact, case-insensitive, normalized)
        $variant = ProductVariant::with('product')
            ->where('sku', $id)
            ->orWhereRaw('LOWER(sku) = ?', [strtolower($id)])
            ->first();

        if ($variant && $variant->product) {
            return ['found' => true, 'product' => $variant->product, 'variant' => $variant];
        }

        // 2. Try matching Product by SKU (exact, case-insensitive, normalized)
        $product = Product::where('sku', $id)
            ->orWhereRaw('LOWER(sku) = ?', [strtolower($id)])
            ->first();

        if (! $product && ! empty($cleanId)) {
            $product = Product::all()->first(function ($p) use ($cleanId) {
                return preg_replace('/[^a-zA-Z0-9]/', '', strtolower((string) $p->sku)) === $cleanId;
            });
        }

        if ($product) {
            $defaultVariant = $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->where('status', 'active')->first();
            return ['found' => true, 'product' => $product, 'variant' => $defaultVariant];
        }

        // 3. Try matching by barcode
        $productByBarcode = Product::where('barcode', $id)->first();
        if ($productByBarcode) {
            return ['found' => true, 'product' => $productByBarcode, 'variant' => null];
        }

        // 4. Try matching numeric ID directly
        if (is_numeric($id)) {
            $productById = Product::find((int) $id);
            if ($productById) {
                $defaultVariant = $productById->variants()->where('is_default', true)->first()
                    ?? $productById->variants()->where('status', 'active')->first();
                return ['found' => true, 'product' => $productById, 'variant' => $defaultVariant];
            }

            $variantById = ProductVariant::with('product')->find((int) $id);
            if ($variantById && $variantById->product) {
                return ['found' => true, 'product' => $variantById->product, 'variant' => $variantById];
            }
        }

        // 5. Try stripped retailer ID (e.g. "VANRITI-12", "VAN-12", "PROD_12")
        if (preg_match('/(?:VANRITI|VAN|PROD|SKU|FB|ITEM)[-_]?([0-9]+)/i', $id, $m)) {
            $p = Product::find((int) $m[1]);
            if ($p) {
                $defaultVariant = $p->variants()->where('is_default', true)->first()
                    ?? $p->variants()->where('status', 'active')->first();
                return ['found' => true, 'product' => $p, 'variant' => $defaultVariant];
            }
        }

        // 6. Try matching by slug
        $productBySlug = Product::where('slug', Str::slug($id))
            ->orWhere('slug', strtolower($id))
            ->first();
        if ($productBySlug) {
            $defaultVariant = $productBySlug->variants()->where('is_default', true)->first()
                ?? $productBySlug->variants()->where('status', 'active')->first();
            return ['found' => true, 'product' => $productBySlug, 'variant' => $defaultVariant];
        }

        // 7. Try matching by Product name (exact or case-insensitive)
        $productByName = Product::where('name', $id)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($id)])
            ->first();
        if ($productByName) {
            $defaultVariant = $productByName->variants()->where('is_default', true)->first()
                ?? $productByName->variants()->where('status', 'active')->first();
            return ['found' => true, 'product' => $productByName, 'variant' => $defaultVariant];
        }

        return ['found' => false, 'product' => null, 'variant' => null];
    }
}
