<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Analytics\MetaCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Asynchronous delivery of the Meta Purchase conversion for an already
 * committed 6G conversion ledger row. The order and ledger are committed
 * independently of Meta API availability, so a Meta outage never affects the
 * checkout result. Retries are bounded by MetaCapiService::maxAttempts() and
 * the ledger's meta_attempts/meta_state columns; delivery is idempotent by
 * deterministic event_id and by the sent-state guard.
 */
class SendMetaCapiPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 90];

    public function __construct(
        public Order $order,
        public string $eventSourceUrl,
        public ?string $fbp = null,
        public ?string $fbc = null,
        public ?string $clientIpAddress = null,
        public ?string $clientUserAgent = null,
    ) {
    }

    public function handle(MetaCapiService $meta): void
    {
        $context = [
            'event_source_url' => $this->eventSourceUrl,
            'fbp' => $this->fbp,
            'fbc' => $this->fbc,
            'client_ip_address' => $this->clientIpAddress,
            'client_user_agent' => $this->clientUserAgent,
        ];

        // While a test-event code is configured (admin > Settings > SEO), every
        // delivery is routed to Meta's Test Events view instead of production.
        $testEventCode = trim((string) setting('meta_test_event_code', ''));
        if ($testEventCode !== '') {
            $context['test_event_code'] = $testEventCode;
        }

        $meta->deliver($this->order, $context);
    }
}