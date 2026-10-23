<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\WhatsAppOtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAbandonedCheckoutReminders extends Command
{
    protected $signature = 'checkout:send-recovery-reminders {--hours=1 : Minimum age in hours before sending recovery reminder}';

    protected $description = 'Send WhatsApp checkout recovery messages for unpaid/abandoned checkouts created in the last 24 hours';

    public function handle(WhatsAppOtpService $whatsapp): int
    {
        if (! $whatsapp->recoveryEnabled()) {
            $this->info('WhatsApp checkout recovery is disabled or not configured in settings.');

            return self::SUCCESS;
        }

        $minHours = max(0.5, (float) $this->option('hours'));
        $minCutoff = now()->subMinutes((int) ($minHours * 60));
        $maxCutoff = now()->subHours(24);

        // Fetch unpaid online orders created within the last 24h that have passed the minimum delay
        // and have not received a recovery reminder yet.
        $orders = Order::query()
            ->where('payment_method', 'razorpay')
            ->whereIn('payment_status', ['pending', 'processing', 'failed'])
            ->whereNull('whatsapp_recovery_sent_at')
            ->where('created_at', '<=', $minCutoff)
            ->where('created_at', '>=', $maxCutoff)
            ->with(['user', 'items'])
            ->latest('id')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No eligible abandoned checkouts found for recovery reminder.');

            return self::SUCCESS;
        }

        $sentCount = 0;

        // Part 1: Unpaid/abandoned checkouts (initiated checkout)
        foreach ($orders as $order) {
            $phone = trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: $order->user?->phone)));

            if ($phone === '') {
                continue;
            }

            $firstItem = $order->items->first();
            $product = $firstItem?->product;
            $productName = $firstItem?->product_name ?: ($product?->name ?: 'your item');
            if ($order->items->count() > 1) {
                $productName .= ' (+' . ($order->items->count() - 1) . ' more)';
            }
            $whatsappImage = $product?->getWhatsAppImage()?->image_path;
            $rawImage = $whatsappImage ?: ($firstItem?->image ?: $product?->getPrimaryImage()?->image_path);
            if ($product && (empty($rawImage) || str_contains(strtolower($rawImage), '.webp'))) {
                $imageUrl = route('media.whatsapp.product', $product);
            } else {
                $imageUrl = $rawImage ? image_url($rawImage) : null;
            }

            $name = trim((string) ($order->user?->name ?: $order->billing_name));
            $name = $name !== '' ? $name : 'Customer';
            $total = (string) number_format((float) $order->grand_total, 2, '.', '');
            $recoveryUrl = route('account.order', $order);

            $result = $whatsapp->sendAbandonedCheckoutRecovery(
                $phone,
                $name,
                $order->order_number,
                $total,
                $recoveryUrl,
                $productName,
                $imageUrl
            );

            if (($result['result'] ?? '0') === '1') {
                $order->update(['whatsapp_recovery_sent_at' => now()]);
                $sentCount++;

                $note = '[' . now()->format('d-M-Y H:i') . '] WhatsApp checkout recovery reminder sent to ' . $phone;
                $order->update([
                    'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
                ]);

                $this->info("Recovery WhatsApp sent for order #{$order->order_number} to {$phone}");
            } else {
                Log::warning('WhatsApp checkout recovery reminder skipped or failed.', [
                    'order' => $order->order_number,
                    'phone' => $phone,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
        }

        // Part 2: Abandoned carts (items added to cart but customer never clicked checkout)
        $carts = \App\Models\Cart::query()
            ->where('owner_type', \App\Models\User::class)
            ->whereHas('items')
            ->whereNull('whatsapp_recovery_sent_at')
            ->where('updated_at', '<=', $minCutoff)
            ->where('updated_at', '>=', $maxCutoff)
            ->with(['owner', 'items.product.images'])
            ->latest('updated_at')
            ->limit(50)
            ->get();

        foreach ($carts as $cart) {
            $user = $cart->owner;
            if (! $user instanceof \App\Models\User) {
                continue;
            }

            $phone = trim((string) $user->phone);
            if ($phone === '') {
                continue;
            }

            // If user has already placed an order in the last 24h, don't ping them about old cart items
            $hasRecentOrder = Order::where('user_id', $user->id)
                ->where('created_at', '>=', $maxCutoff)
                ->whereNotIn('order_status', ['cancelled', 'failed'])
                ->exists();

            if ($hasRecentOrder) {
                $cart->update(['whatsapp_recovery_sent_at' => now()]);
                continue;
            }

            $itemDescriptions = [];
            foreach ($cart->items as $ci) {
                $pName = $ci->product?->name ?: 'Item';
                if ($ci->variant) {
                    $pName .= ' (' . $ci->variant->name . ')';
                }
                $itemDescriptions[] = "{$pName} (x{$ci->quantity})";
            }
            $productName = \Illuminate\Support\Str::limit(implode(', ', $itemDescriptions), 80);

            $firstItem = $cart->items->first();
            $product = $firstItem?->product;

            $whatsappImage = $product?->getWhatsAppImage()?->image_path;
            $rawImage = $whatsappImage ?: $product?->getPrimaryImage()?->image_path;
            if ($product && (empty($rawImage) || str_contains(strtolower($rawImage), '.webp'))) {
                $imageUrl = route('media.whatsapp.product', $product);
            } else {
                $imageUrl = $rawImage ? image_url($rawImage) : null;
            }

            $name = trim((string) $user->name) ?: 'Customer';
            $total = (string) number_format((float) $cart->subtotal(), 2, '.', '');
            $cartRef = 'Cart (' . $cart->itemsCount() . ' item' . ($cart->itemsCount() > 1 ? 's' : '') . ')';
            $recoveryUrl = url('/cart');

            // Pre-initialize WhatsApp checkout session so customer can order directly from WhatsApp
            try {
                $checkoutService = app(\App\Services\WhatsAppCheckoutService::class);
                $session = $checkoutService->createSessionFromAbandonedCart($phone, $cart, $user);

                // Attempt direct interactive Order Review if within 24h window
                if (! empty($session->customer_name) && ! empty($session->pincode) && ! empty($session->address_data['address_line1'])) {
                    $session->transitionTo(\App\Models\WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION);
                    $checkoutService->sendOrderReviewMessage($session);
                }
            } catch (\Throwable $e) {
                // Ignore if outside 24h
            }

            $result = $whatsapp->sendAbandonedCheckoutRecovery(
                $phone,
                $name,
                $cartRef,
                $total,
                $recoveryUrl,
                $productName,
                $imageUrl
            );

            if (($result['result'] ?? '0') === '1') {
                $cart->update(['whatsapp_recovery_sent_at' => now()]);
                $sentCount++;
                $this->info("Cart recovery WhatsApp sent to {$phone} ({$name})");
            } else {
                Log::warning('WhatsApp cart recovery reminder skipped or failed.', [
                    'cart_id' => $cart->id,
                    'phone' => $phone,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
        }

        $this->info("Completed: Sent {$sentCount} recovery reminder(s).");

        return self::SUCCESS;
    }
}
