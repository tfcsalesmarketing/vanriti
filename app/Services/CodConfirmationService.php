<?php

namespace App\Services;

use App\Jobs\PushOrderToShipMojo;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class CodConfirmationService
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService,
        protected OrderService $orderService,
    ) {}

    /**
     * Dispatch interactive 1-Tap COD Confirmation prompt to customer.
     */
    public function dispatchPrompt(Order $order): array
    {
        if ($order->payment_method !== 'cod') {
            return ['success' => false, 'message' => 'Not a COD order.'];
        }

        $phone = $this->resolveOrderPhone($order);
        if (empty($phone)) {
            return ['success' => false, 'message' => 'No mobile number found on order.'];
        }

        $name = $order->shipping_name ?: 'Valued Customer';
        $amount = format_price($order->grand_total);
        $orderNumber = $order->order_number;

        $bodyText = "Hello {$name}!\n\nWe have received your Cash on Delivery order #{$orderNumber} for {$amount}.\n\nPlease tap below to confirm your order so we can dispatch your parcel immediately.";

        $buttons = [
            ['id' => 'COD_CONFIRM_' . $orderNumber, 'title' => 'Confirm Order'],
            ['id' => 'COD_CANCEL_' . $orderNumber, 'title' => 'Cancel Order'],
        ];

        $headerText = 'Vanriti Order Confirmation';
        $footerText = 'Tap an option above';

        $templateName = (string) setting('whatsapp_template_cod_confirmation', 'cod_order_confirmation');
        $useTemplate = (bool) setting('whatsapp_template_cod_enabled', true);

        $res = null;

        // Try approved Meta Template first (required for business-initiated outside 24h window)
        if ($useTemplate && $templateName !== '') {
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $name],
                        ['type' => 'text', 'text' => $orderNumber],
                        ['type' => 'text', 'text' => $amount],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'COD_CONFIRM_' . $orderNumber],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '1',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'COD_CANCEL_' . $orderNumber],
                    ],
                ],
            ];

            $res = $this->apiService->sendTemplateMessage($phone, $templateName, 'en', $components);
        }

        // If template message not enabled or failed, fallback to interactive button message
        if (empty($res['success'])) {
            $res = $this->apiService->sendInteractiveButtonMessage(
                $phone,
                $bodyText,
                $buttons,
                $headerText,
                $footerText
            );
        }

        if ($res['success'] ?? false) {
            $note = '[' . now()->format('d M Y H:i') . '] WhatsApp 1-Tap COD confirmation prompt dispatched to ' . $phone;
            $order->update([
                'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
            ]);
        }

        return $res;
    }

    /**
     * Customer tapped [Confirm Order] button in WhatsApp.
     */
    public function handleCustomerConfirm(string $orderNumber, string $phone): bool
    {
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            Log::warning('COD Confirmation: order not found', ['order_number' => $orderNumber]);
            return false;
        }

        if (in_array($order->order_status, ['shipped', 'delivered', 'cancelled'])) {
            $this->apiService->sendTextMessage(
                $phone,
                "Your Vanriti order #{$orderNumber} is already {$order->order_status}."
            );
            return true;
        }

        $name = $order->shipping_name ?: 'there';

        // Update status to processing / confirmed
        $order->update([
            'order_status' => 'processing',
            'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . '[' . now()->format('d M Y H:i') . '] COD confirmed by customer via WhatsApp 1-Tap button.'),
        ]);

        // Auto-push to ShipMojo if enabled
        if ((bool) setting('shipmojo_auto_push', false)) {
            PushOrderToShipMojo::dispatch($order);
        }

        $reply = "🎉 Thank you, {$name}!\n\nYour Cash on Delivery order #{$orderNumber} is now *CONFIRMED*. Our warehouse team is packing your luxury items. We will send you live tracking details as soon as it is dispatched! 🌿";
        $this->apiService->sendTextMessage($phone, $reply);

        Log::info('COD order confirmed via WhatsApp', ['order_number' => $orderNumber, 'phone' => $phone]);

        return true;
    }

    /**
     * Customer tapped [Cancel Order] button in WhatsApp.
     */
    public function handleCustomerCancel(string $orderNumber, string $phone): bool
    {
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return false;
        }

        if ($order->order_status === 'cancelled') {
            $this->apiService->sendTextMessage($phone, "Your order #{$orderNumber} is already cancelled.");
            return true;
        }

        if (! in_array($order->order_status, ['pending', 'on_hold', 'processing'])) {
            $this->apiService->sendTextMessage(
                $phone,
                "Your order #{$orderNumber} has already progressed to '{$order->order_status}' and cannot be cancelled automatically. Please contact our support team at care@vanriti.com."
            );
            return false;
        }

        try {
            $this->orderService->cancelOrder($order, 'Cancelled by customer via WhatsApp COD prompt');
        } catch (\Throwable $e) {
            Log::error('COD cancellation error', ['order' => $orderNumber, 'error' => $e->getMessage()]);
            return false;
        }

        $reply = "Your Vanriti order #{$orderNumber} has been *CANCELLED* as requested. No parcel will be dispatched. Thank you for letting us know! 🌿";
        $this->apiService->sendTextMessage($phone, $reply);

        Log::info('COD order cancelled via WhatsApp', ['order_number' => $orderNumber, 'phone' => $phone]);

        return true;
    }

    protected function resolveOrderPhone(Order $order): string
    {
        $candidates = [
            $order->shipping_mobile,
            $order->billing_mobile,
            $order->user?->phone,
            $order->guest_mobile,
        ];

        foreach ($candidates as $candidate) {
            $phone = trim((string) $candidate);
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }
}
