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

        $result = $whatsapp->sendOrderConfirmation(
            $phone,
            $this->resolveName(),
            $order->order_number,
            now()->addDays($etaDays)->toDateString(),
            route('account.order', $order),
        );

        if (($result['result'] ?? '0') !== '1') {
            // Release the claim so the other trigger point (Razorpay browser
            // callback vs webhook) gets a second attempt.
            $this->releaseClaim();

            Log::warning('WhatsApp order confirmation could not be delivered.', [
                'order' => $order->order_number,
                'message' => $result['message'] ?? 'Unknown error',
            ]);
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
