<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingPayments extends Command
{
    protected $signature = 'orders:expire-pending-payments {--hours=24 : Abandoned Razorpay orders older than this many hours are cancelled}';

    protected $description = 'Cancel Razorpay orders whose payment was never completed and restore their stock';

    public function handle(OrderService $orderService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $orders = Order::where('payment_method', 'razorpay')
            ->whereIn('payment_status', ['pending', 'processing', 'failed'])
            ->where('order_status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->with('items')
            ->get();

        $cancelled = 0;

        foreach ($orders as $order) {
            try {
                $orderService->cancelOrder($order, 'Payment was not completed within the allowed time; order cancelled and stock restored.');
                $cancelled++;
            } catch (\Throwable $e) {
                Log::warning('Could not expire a pending Razorpay order.', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Cancelled {$cancelled} abandoned Razorpay order(s).");

        return self::SUCCESS;
    }
}
