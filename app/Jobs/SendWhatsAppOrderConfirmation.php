<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsAppOtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Order-confirmation WhatsApp message.
 *
 * The storefront runs this synchronously via dispatchSync() so no queue worker
 * is required. $tries/$backoff only come into play if the site later switches
 * the dispatch calls to dispatch() and runs queue:work.
 */
class SendWhatsAppOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30; // seconds between retries

    public function __construct(public Order $order) {}

    public function handle(WhatsAppOtpService $whatsapp): void
    {
        $order = $this->order;

        if (! $whatsapp->ordersEnabled()) {
            Log::info('WhatsApp order confirmation skipped (disabled)', [
                'order' => $order->order_number,
            ]);

            return;
        }

        $phone = $this->resolvePhone();

        if ($phone === '') {
            Log::warning('WhatsApp order confirmation skipped (no mobile number)', [
                'order' => $order->order_number,
            ]);

            return;
        }

        // Atomic claim: concurrent callers both evaluate the same WHERE clause
        // in a single UPDATE, so only the first one can flip the column.
        if (! $this->claim()) {
            Log::info('WhatsApp order confirmation already sent', [
                'order' => $order->order_number,
            ]);

            return;
        }

        $etaDays = max(0, (int) setting('whatsapp_delivery_eta_days', 5));
        $deliveryDate = now()->addDays($etaDays)->toDateString();
        $orderUrl = route('account.order', $order);
        $customerName = $this->resolveName();

        $result = $whatsapp->sendOrderConfirmation(
            $phone,
            $customerName,
            $order->order_number,
            $deliveryDate,
            $orderUrl,
        );

        if (($result['result'] ?? '0') !== '1') {
            // Release the claim so the other trigger point (Razorpay browser
            // callback vs webhook) gets a second attempt.
            $this->releaseClaim();

            Log::warning('WhatsApp order confirmation could not be delivered.', [
                'order' => $order->order_number,
                'message' => $result['message'] ?? 'Unknown error',
            ]);
        } else {
            // Guarantee conversation stream has the order confirmation recorded
            try {
                $alreadyLogged = \App\Models\WhatsAppMessage::where('metadata->order_number', $order->order_number)
                    ->where('metadata->is_order_confirmation', true)
                    ->exists();

                if (! $alreadyLogged) {
                    $amountFormatted = format_price($order->grand_total);
                    $renderedBody = "🛍️ Order Confirmation (#{$order->order_number})\nDear {$customerName}, thank you for ordering with VANRITI! Your order of {$amountFormatted} is confirmed. Estimated delivery by {$deliveryDate}.\nTrack your order: {$orderUrl}";

                    app(\App\Services\WhatsAppChatService::class)->recordOutboundSystemMessage(
                        phone: $phone,
                        body: $renderedBody,
                        messageType: 'template',
                        metaMessageId: $result['message_id'] ?? null,
                        metadata: [
                            'order_number' => $order->order_number,
                            'delivery_date' => $deliveryDate,
                            'order_url' => $orderUrl,
                            'amount' => $order->grand_total,
                            'is_order_confirmation' => true,
                        ],
                        channel: 'vanriti',
                        contactName: $customerName
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('SendWhatsAppOrderConfirmation: Failed to log message in chat', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Deliver to the number on the order, not the account: a guest checkout
     * number or a different delivery contact still receives the message.
     */
    protected function resolvePhone(): string
    {
        $candidates = [
            $this->order->shipping_mobile,
            $this->order->billing_mobile,
            $this->order->user?->phone,
        ];

        foreach ($candidates as $candidate) {
            $phone = trim((string) $candidate);

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
            $name = trim((string) $this->order->user?->name);
        }

        return explode(' ', $name)[0] ?? '';
    }

    protected function claim(): bool
    {
        return Order::query()
            ->whereKey($this->order->id)
            ->whereNull('whatsapp_confirmation_sent_at')
            ->update(['whatsapp_confirmation_sent_at' => now()]) > 0;
    }

    protected function releaseClaim(): void
    {
        Order::query()
            ->whereKey($this->order->id)
            ->update(['whatsapp_confirmation_sent_at' => null]);
    }
}
