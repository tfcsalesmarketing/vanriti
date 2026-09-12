<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ShipMojoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushOrderToShipMojo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(public Order $order)
    {
    }

    public function handle(ShipMojoService $shipmojo): void
    {
        if (! (bool) setting('shipmojo_enabled', false)) {
            Log::info('ShipMojo: Auto-push skipped (disabled)', ['order' => $this->order->order_number]);
            return;
        }

        try {
            $response = $shipmojo->pushOrder($this->order);

            if (($response['result'] ?? '0') !== '1') {
                Log::warning('ShipMojo: Auto-push returned failure', [
                    'order'    => $this->order->order_number,
                    'message'  => $response['message'] ?? 'Unknown error',
                ]);
            }

            // If auto_assign is also enabled, assign courier immediately
            if ((bool) setting('shipmojo_auto_assign', true) && ($response['result'] ?? '0') === '1') {
                $assignResponse = $shipmojo->autoAssign($this->order);
                Log::info('ShipMojo: Auto-assign result', [
                    'order'    => $this->order->order_number,
                    'response' => $assignResponse,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('ShipMojo: Auto-push job failed', [
                'order' => $this->order->order_number,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Allow queue retry
        }
    }
}
