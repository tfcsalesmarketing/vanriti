<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShipMojoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncShipMojoTrackingCommand extends Command
{
    protected $signature = 'shipmojo:sync-tracking {--order= : Specific order number to synchronize}';

    protected $description = 'Synchronize tracking details and delivery statuses with ShipMojo';

    public function handle(ShipMojoService $shipmojo): int
    {
        if (! (bool) setting('shipmojo_enabled', false)) {
            $this->warn('ShipMojo integration is disabled in settings. Skipping sync.');

            return self::SUCCESS;
        }

        $orderNumber = $this->option('order');

        if (! empty($orderNumber)) {
            $order = Order::where('order_number', $orderNumber)->first();

            if (! $order) {
                $this->error("Order '{$orderNumber}' not found.");

                return self::FAILURE;
            }

            return $this->syncSingleOrder($shipmojo, $order);
        }

        // Find active (non-terminal) shipments created within the last 30 days
        $shipments = Shipment::query()
            ->whereNotNull('shipmojo_order_id')
            ->whereNotIn('status', ['delivered', 'cancelled', 'returned'])
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->with('order')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('No active ShipMojo shipments requiring tracking sync.');

            return self::SUCCESS;
        }

        $this->info("Found {$shipments->count()} active shipment(s) to synchronize with ShipMojo.");

        $synced = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            $order = $shipment->order;
            if (! $order) {
                continue;
            }

            try {
                $result = $shipmojo->syncTracking($order);
                $status = $result['data']['current_status'] ?? $result['data']['status'] ?? 'Updated';
                $this->line("  [OK] Order {$order->order_number}: Status -> {$status}");
                $synced++;
            } catch (\Throwable $e) {
                $this->line("  [SKIP] Order {$order->order_number}: " . $e->getMessage());
                Log::warning('ShipMojo scheduled tracking sync failed for order', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("ShipMojo tracking sync completed: {$synced} synced, {$failed} skipped/failed.");

        return self::SUCCESS;
    }

    protected function syncSingleOrder(ShipMojoService $shipmojo, Order $order): int
    {
        $this->info("Synchronizing Order {$order->order_number} with ShipMojo...");

        try {
            $result = $shipmojo->syncTracking($order);
            $status = $result['data']['current_status'] ?? $result['data']['status'] ?? 'Updated';
            $awb = $order->shipments()->latest()->first()?->awb_number ?? 'N/A';
            $trackingUrl = $order->tracking_url ?? 'N/A';

            $this->info("Successfully synced Order {$order->order_number}!");
            $this->line("  Status: {$status}");
            $this->line("  AWB: {$awb}");
            $this->line("  Tracking URL: {$trackingUrl}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to sync Order {$order->order_number}: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}
