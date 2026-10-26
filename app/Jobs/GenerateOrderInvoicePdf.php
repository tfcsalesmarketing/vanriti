<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateOrderInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Order $order, public bool $force = false) {}

    public function handle(InvoiceService $invoiceService): void
    {
        try {
            $url = $invoiceService->generateAndStorePdf($this->order, $this->force);
            Log::info("GenerateOrderInvoicePdf: Successfully generated and stored invoice for order #{$this->order->order_number}", [
                'order' => $this->order->order_number,
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            Log::error("GenerateOrderInvoicePdf: Failed generating invoice for order #{$this->order->order_number}: {$e->getMessage()}", [
                'order' => $this->order->order_number,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
