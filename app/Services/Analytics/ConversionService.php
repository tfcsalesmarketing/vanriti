<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsConversion;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Records analytics conversion events in the durable analytics_conversions
 * ledger. The database (UNIQUE(event_type, order_number)) is the source of
 * truth for conversion deduplication; session, cache, localStorage and
 * cookies are never authoritative. Repeated and concurrent calls resolve to
 * the existing ledger row rather than creating duplicates.
 */
class ConversionService
{
    public function __construct(
        protected EcommerceDataService $ecommerce,
    ) {
    }

    /**
     * Record the canonical purchase conversion for an order if (and only if)
     * the order is purchase-eligible. Returns the existing row when the
     * conversion was already recorded, or null when the order is not eligible.
     */
    public function recordPurchase(Order $order): ?AnalyticsConversion
    {
        if (! $this->ecommerce->purchaseEligible($order)) {
            return null;
        }

        return $this->record(
            'purchase',
            $order->order_number,
            $this->ecommerce->purchase($order),
        );
    }

    /**
     * Record (or resolve to an existing) conversion ledger row.
     */
    public function record(string $eventType, string $orderNumber, ?array $payload = null, ?string $channel = null): AnalyticsConversion
    {
        try {
            return AnalyticsConversion::create([
                'event_type' => $eventType,
                'order_number' => $orderNumber,
                'channel' => $channel,
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request already committed this conversion; the
            // UNIQUE(event_type, order_number) constraint is the arbiter.
            return AnalyticsConversion::query()
                ->where('event_type', $eventType)
                ->where('order_number', $orderNumber)
                ->firstOrFail();
        }
    }
}