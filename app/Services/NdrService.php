<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class NdrService
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService,
    ) {}

    /**
     * Dispatch interactive NDR prompt to customer when delivery attempt fails.
     */
    public function handleNdrAlert(Order $order, ?string $reason = null): array
    {
        $phone = $this->resolveOrderPhone($order);
        if (empty($phone)) {
            return ['success' => false, 'message' => 'No customer phone found'];
        }

        // Avoid spamming if alert was sent in the last 12 hours
        if ($order->internal_notes && str_contains($order->internal_notes, 'NDR alert sent [' . now()->format('d M Y'))) {
            return ['success' => false, 'message' => 'NDR alert already sent today'];
        }

        $name = $order->shipping_name ?: 'Valued Customer';
        $orderNumber = $order->order_number;
        $reasonText = $reason ? " (Reason: {$reason})" : '';

        $bodyText = "Hello {$name}!\n\nOur courier partner attempted to deliver your Vanriti parcel for order #{$orderNumber} today, but was unable to complete the delivery{$reasonText}.\n\nTo avoid Return to Origin (RTO), please tap below to confirm your preferred action.";

        $buttons = [
            ['id' => 'NDR_REATTEMPT_' . $orderNumber, 'title' => 'Reattempt Tomorrow'],
            ['id' => 'NDR_UPDATE_' . $orderNumber, 'title' => 'Send Landmark Info'],
        ];

        $headerText = 'Delivery Attempt Update';
        $footerText = 'Vanriti Order # ' . $orderNumber;

        $templateName = (string) setting('whatsapp_template_ndr', 'order_delivery_reattempt');
        $useTemplate = (bool) setting('whatsapp_template_ndr_enabled', false);

        $res = null;

        // Try approved Meta Template first (required for business-initiated outside 24h window)
        if ($useTemplate && $templateName !== '') {
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $name],
                        ['type' => 'text', 'text' => $orderNumber],
                        ['type' => 'text', 'text' => $reason ?: 'Delivery attempt was unsuccessful'],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'NDR_REATTEMPT_' . $orderNumber],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => '1',
                    'parameters' => [
                        ['type' => 'payload', 'payload' => 'NDR_UPDATE_' . $orderNumber],
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
            $note = '[' . now()->format('d M Y H:i') . '] NDR alert sent to ' . $phone . ($reason ? ' with reason: ' . $reason : '');
            $order->update([
                'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
            ]);
        }

        return $res;
    }

    /**
     * Handle customer reattempt request.
     */
    public function handleCustomerReattempt(string $orderNumber, string $phone): bool
    {
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return false;
        }

        $note = '[' . now()->format('d M Y H:i') . '] Customer requested delivery reattempt via WhatsApp NDR button.';
        $order->update([
            'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
        ]);

        $reply = "✅ Thank you! We have requested our delivery partner to reattempt delivery of order #{$orderNumber} tomorrow. The rider will contact you prior to arrival. 🌿";
        $this->apiService->sendTextMessage($phone, $reply);

        Log::info('NDR reattempt requested via WhatsApp', ['order' => $orderNumber, 'phone' => $phone]);

        return true;
    }

    /**
     * Handle customer request to provide updated landmark / address details.
     */
    public function handleCustomerLandmarkPrompt(string $orderNumber, string $phone): bool
    {
        $reply = "📍 Please type your updated landmark or delivery instructions for order #{$orderNumber}. Our logistics support team will forward it directly to the delivery rider.";
        $this->apiService->sendTextMessage($phone, $reply);

        return true;
    }

    protected function resolveOrderPhone(Order $order): string
    {
        $candidates = [
            $order->shipping_mobile,
            $order->billing_mobile,
            $order->user?->phone,
        ];

        foreach ($candidates as $candidate) {
            $phone = preg_replace('/[^0-9]/', '', (string) $candidate);
            if ($phone !== '') {
                return strlen($phone) === 10 ? '91' . $phone : $phone;
            }
        }

        return '';
    }
}
