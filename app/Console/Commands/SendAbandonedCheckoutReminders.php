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

        foreach ($orders as $order) {
            $phone = trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: $order->user?->phone)));

            if ($phone === '') {
                continue;
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
                $recoveryUrl
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

        $this->info("Completed: Sent {$sentCount} checkout recovery reminder(s).");

        return self::SUCCESS;
    }
}
