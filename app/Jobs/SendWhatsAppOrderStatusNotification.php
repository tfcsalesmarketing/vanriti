<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppOtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends automated WhatsApp notifications when an order status changes to
 * 'shipped', 'out_for_delivery', or 'delivered'.
 */
class SendWhatsAppOrderStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Order $order,
        public string $status
    ) {}

    public function handle(WhatsAppCloudApiService $apiService, WhatsAppOtpService $otpService): void
    {
        $status = strtolower($this->status);

        if (! in_array($status, ['shipped', 'out_for_delivery', 'delivered'], true)) {
            return;
        }

        // Global check: Meta API configured & orders notifications enabled
        if (! $otpService->ordersEnabled()) {
            Log::info("WhatsApp {$status} notification skipped (orders notifications disabled)", [
                'order' => $this->order->order_number,
            ]);
            return;
        }

        $shippingEnabled = (bool) setting('whatsapp_shipping_notifications_enabled', true);
        if (! $shippingEnabled) {
            Log::info("WhatsApp {$status} notification skipped (shipping notifications toggled off)", [
                'order' => $this->order->order_number,
            ]);
            return;
        }

        $phone = $this->resolvePhone();
        if ($phone === '') {
            Log::warning("WhatsApp {$status} notification skipped (no mobile number found)", [
                'order' => $this->order->order_number,
            ]);
            return;
        }

        // Atomic duplicate check
        if (! $this->claim($status)) {
            Log::info("WhatsApp {$status} notification already claimed or sent", [
                'order' => $this->order->order_number,
            ]);
            return;
        }

        $this->order->loadMissing(['shipments', 'user', 'items.product']);
        $shipment = $this->order->shipments()->latest()->first();

        $courier = $shipment?->courier ?: 'ShipMojo Express';
        $trackingNumber = $shipment?->awb_number ?: ($shipment?->tracking_number ?: $this->order->order_number);
        $trackingUrl = $this->order->trackingUrl();
        $customerName = $this->resolveName();

        // Identify highest-value item for targeted review link
        $highestItem = $this->order->items->sortByDesc(fn ($item) => (float) $item->total_price)->first();
        $productName = $highestItem?->product_name ?: ($highestItem?->product?->name ?: 'your item');
        $reviewUrl = $this->order->guestReviewUrl($highestItem);

        $actionUrl = match ($status) {
            'shipped' => $trackingUrl,
            'delivered' => $reviewUrl,
            default => $trackingUrl,
        };

        $conversation = WhatsAppConversation::findOrCreateForPhone($phone, $customerName);

        $templateName = match ($status) {
            'shipped' => (string) setting('whatsapp_dispatch_template_name', 'order_dispatched'),
            'out_for_delivery' => (string) setting('whatsapp_out_for_delivery_template_name', 'order_out_for_delivery'),
            'delivered' => (string) setting('whatsapp_delivered_template_name', 'order_delivered_review'),
            default => 'order_dispatched',
        };

        $params = match ($status) {
            'shipped' => [$customerName, $this->order->order_number, $courier, $trackingNumber],
            'out_for_delivery' => [$customerName, $this->order->order_number, $courier],
            'delivered' => [$customerName, $this->order->order_number, $productName],
        };

        $buttonSuffix = ltrim((string) parse_url($actionUrl, PHP_URL_PATH), '/');
        if ($query = parse_url($actionUrl, PHP_URL_QUERY)) {
            $buttonSuffix .= '?' . $query;
        }
        if ($buttonSuffix === '') {
            $buttonSuffix = $actionUrl;
        }

        $components = [
            [
                'type' => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params),
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $buttonSuffix],
                ],
            ],
        ];

        // 1. Try sending via Meta Approved Template
        $result = $apiService->sendTemplateMessage($phone, $templateName, 'en', $components);

        // 2. If template failed and customer's 24hr session window is currently active, fall back to rich text
        if (! ($result['success'] ?? false) && $conversation->isWindowOpen()) {
            $fallbackText = match ($status) {
                'shipped' => "📦 Great news, {$customerName}! Your order #{$this->order->order_number} has been dispatched via {$courier}. Tracking / AWB: {$trackingNumber}. Track here: {$trackingUrl}",
                'out_for_delivery' => "🚚 Hi {$customerName}, your order #{$this->order->order_number} is out for delivery today with {$courier}! Please keep your phone handy.",
                'delivered' => "🎉 Hooray {$customerName}! Your order #{$this->order->order_number} containing {$productName} has been delivered! We hope you love your Vanriti products. Please share your review here: {$reviewUrl}",
            };

            $result = $apiService->sendTextMessage($phone, $fallbackText);
            $previewSnippet = $fallbackText;
        } else {
            $previewSnippet = "📋 Template: {$templateName} (#{$this->order->order_number})";
        }

        if ($result['success'] ?? false) {
            // Record in conversation stream
            WhatsAppMessage::create([
                'whatsapp_conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'message_type' => 'text',
                'body' => $previewSnippet,
                'meta_message_id' => $result['message_id'] ?? null,
                'status' => 'sent',
                'metadata' => [
                    'order_number' => $this->order->order_number,
                    'event' => $status,
                    'courier' => $courier,
                    'awb' => $trackingNumber,
                ],
                'sent_at' => now(),
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'last_message_snippet' => $previewSnippet,
                'last_message_direction' => 'outbound',
            ]);

            // Add note to order internal notes
            $statusLabel = ucwords(str_replace('_', ' ', $status));
            $note = '[' . now()->format('d M Y H:i') . "] WhatsApp {$statusLabel} notification sent to {$phone} (AWB: {$trackingNumber})";
            $existing = $this->order->internal_notes ? $this->order->internal_notes . "\n" : '';
            $this->order->update(['internal_notes' => $existing . $note]);

            Log::info("WhatsApp {$status} notification sent successfully", [
                'order' => $this->order->order_number,
                'phone' => $phone,
            ]);
        } else {
            $this->releaseClaim($status);

            Log::warning("WhatsApp {$status} notification failed to deliver", [
                'order' => $this->order->order_number,
                'phone' => $phone,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }

    protected function resolvePhone(): string
    {
        $candidates = [
            $this->order->shipping_mobile,
            $this->order->billing_mobile,
            $this->order->user?->phone,
            $this->order->guest_mobile,
        ];

        foreach ($candidates as $c) {
            $phone = trim((string) $c);
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }

    protected function resolveName(): string
    {
        $name = trim((string) ($this->order->shipping_name ?: $this->order->billing_name));
        if ($name === '') {
            $name = trim((string) ($this->order->user?->name ?: $this->order->guest_name));
        }

        return explode(' ', $name ?: 'Customer')[0];
    }

    protected function claim(string $status): bool
    {
        if (in_array($status, ['shipped', 'out_for_delivery'], true)) {
            return Order::query()
                ->whereKey($this->order->id)
                ->whereNull('whatsapp_shipped_sent_at')
                ->update(['whatsapp_shipped_sent_at' => now()]) > 0;
        }

        if ($status === 'delivered') {
            return Order::query()
                ->whereKey($this->order->id)
                ->whereNull('whatsapp_delivered_sent_at')
                ->update(['whatsapp_delivered_sent_at' => now()]) > 0;
        }

        return true;
    }

    protected function releaseClaim(string $status): void
    {
        if (in_array($status, ['shipped', 'out_for_delivery'], true)) {
            Order::query()
                ->whereKey($this->order->id)
                ->update(['whatsapp_shipped_sent_at' => null]);
        } elseif ($status === 'delivered') {
            Order::query()
                ->whereKey($this->order->id)
                ->update(['whatsapp_delivered_sent_at' => null]);
        }
    }
}
