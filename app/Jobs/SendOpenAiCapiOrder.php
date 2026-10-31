<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Analytics\OpenAiCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Asynchronous delivery of the OpenAI order_created conversion.
 *
 * Runs only after the order/payment transaction commits. Idempotent: protected by
 * the analytics_conversions unique ledger row and openai_state guard.
 */
class SendOpenAiCapiOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 90];

    public function __construct(
        public Order $order,
        public ?string $oppref = null,
        public ?string $obref = null,
        public ?bool $consentGranted = null,
    ) {}

    public function handle(OpenAiCapiService $openai): void
    {
        $openai->deliver($this->order, [
            'oppref' => $this->oppref,
            'obref' => $this->obref,
            'consent_granted' => $this->consentGranted,
        ]);
    }
}

