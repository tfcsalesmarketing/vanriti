<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShipMojoService
{
    protected string $baseUrl = 'https://shipping-api.com/app/api/v1';

    protected function headers(): array
    {
        return [
            'public-key' => (string) setting('shipmojo_public_key', ''),
            'private-key' => (string) secret_setting('shipmojo_private_key', ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function isEnabled(): bool
    {
        return (bool) setting('shipmojo_enabled', false)
            && ! empty(setting('shipmojo_public_key', ''))
            && ! empty(secret_setting('shipmojo_private_key', ''));
    }

    /**
     * Allowed shipment status transitions. A shipment only ever moves forward:
     * a late or duplicated carrier callback (e.g. an `rto` that arrives after
     * the parcel was already delivered) must never walk a delivered shipment
     * back to returning/cancelled and trigger stock releases and customer
     * notifications. `delivered` and `cancelled` are terminal.
     *
     * @var array<string, list<string>>
     */
    protected const SHIPMENT_TRANSITIONS = [
        'pending' => ['pending', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'],
        'packed' => ['packed', 'shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'],
        'shipped' => ['shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'],
        'out_for_delivery' => ['out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'],
        'delivered' => ['delivered'],
        'returning' => ['returning', 'returned', 'delivered', 'failed', 'cancelled'],
        'returned' => ['returned', 'returning', 'delivered'],
        'failed' => ['failed', 'returning', 'returned', 'cancelled'],
        'cancelled' => ['cancelled'],
    ];

    /**
     * Translate a mapped carrier status into the vocabulary the
     * `shipments.status` enum actually accepts. The enum has no `cancelled`
     * member, so a carrier-side cancellation is stored as a failed shipment
     * (which is also what an explicit cancel call records).
     */
    protected function shipmentStatusFor(string $mappedStatus): string
    {
        $allowed = ['pending', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed'];

        if (in_array($mappedStatus, $allowed, true)) {
            return $mappedStatus;
        }

        return $mappedStatus === 'cancelled' ? 'failed' : 'pending';
    }

    /**
     * Whether moving a shipment from $from to $to is a forward transition.
     * Unknown current states are treated as forward-eligible so a newly
     * introduced status cannot silently freeze tracking.
     */
    protected function canTransitionShipment(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = self::SHIPMENT_TRANSITIONS[$from] ?? null;

        return $allowed === null || in_array($to, $allowed, true);
    }

    /**
     * Best human-readable error from a ShipMojo response, surfacing nested
     * provider detail (e.g. "Pickup pincode not serviceable") instead of the
     * generic top-level "Error".
     *
     * @param  array<string, mixed>  $response
     */
    public function errorMessage(array $response): string
    {
        $generic = ['error', 'error occurred', 'something went wrong', 'failed', 'request failed'];

        foreach (['data', 'errors'] as $envelope) {
            $nested = $response[$envelope] ?? null;

            if (! is_array($nested)) {
                continue;
            }

            foreach (['error_message', 'message', 'error', 'msg'] as $key) {
                if (isset($nested[$key]) && is_string($nested[$key]) && trim($nested[$key]) !== '') {
                    return trim(strip_tags($nested[$key]));
                }
            }
        }

        foreach (['message', 'error', 'error_message', 'msg'] as $key) {
            if (isset($response[$key]) && is_string($response[$key]) && trim($response[$key]) !== '') {
                $value = trim(strip_tags($response[$key]));

                if (! in_array(strtolower($value), $generic, true)) {
                    return $value;
                }
            }
        }

        if (! empty($response['http_status'])) {
            return 'ShipMojo error (HTTP '.$response['http_status'].')';
        }

        return 'Unknown ShipMojo error';
    }

    protected function get(string $endpoint): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->get($this->baseUrl.$endpoint);

            return $this->hydrate($response->json(), $response->status());
        } catch (\Throwable $e) {
            return ['result' => '0', 'message' => 'ShipMojo request failed: '.$e->getMessage()];
        }
    }

    protected function post(string $endpoint, array $body): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->post($this->baseUrl.$endpoint, $body);

            return $this->hydrate($response->json(), $response->status());
        } catch (\Throwable $e) {
            return ['result' => '0', 'message' => 'ShipMojo request failed: '.$e->getMessage()];
        }
    }

    protected function hydrate(mixed $json, int $status): array
    {
        $body = is_array($json) ? $json : ['result' => '0', 'message' => 'Empty response'];

        return array_merge($body, ['http_status' => $status]);
    }

    // ── Health Check ──────────────────────────────────────────────────────────
    public function ping(): array
    {
        return $this->get('/info');
    }

    // ── Warehouse ─────────────────────────────────────────────────────────────
    public function getWarehouses(): array
    {
        return $this->get('/get-warehouses');
    }

    public function createWarehouse(array $data): array
    {
        return $this->post('/create-warehouse', $data);
    }

    // ── Serviceability & Rates ────────────────────────────────────────────────
    public function checkServiceability(string $pickupPincode, string $deliveryPincode): array
    {
        return $this->post('/pincode-serviceability', [
            'pickup_pincode' => (int) $pickupPincode,
            'delivery_pincode' => (int) $deliveryPincode,
        ]);
    }

    public function getRates(array $params): array
    {
        return $this->post('/rate-calculator', $params);
    }

    // ── Return Reasons ────────────────────────────────────────────────────────
    public function getReturnReasons(): array
    {
        return $this->get('/get-return-reason');
    }

    // ── Push Forward Order ────────────────────────────────────────────────────
    public function pushOrder(Order $order): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('ShipMojo is not enabled or configured.');
        }

        // Eager-load required relations
        $order->loadMissing(['items', 'user']);

        // Idempotency: a re-push (queue retry, or an admin clicking push twice)
        // must not create a second carrier order. Once the carrier has accepted
        // the order and its id is stored, the push is a no-op.
        $pushed = $order->shipments()
            ->whereNotNull('shipmojo_order_id')
            ->whereNotNull('shipmojo_pushed_at')
            ->latest('id')
            ->first();

        if ($pushed) {
            Log::info('ShipMojo: Push skipped, order already pushed', [
                'order' => $order->order_number,
                'shipmojo_order_id' => $pushed->shipmojo_order_id,
            ]);

            return [
                'result' => '1',
                'message' => 'Order was already pushed to ShipMojo.',
                'idempotent' => true,
                'data' => ['order_id' => $pushed->shipmojo_order_id, 'reference_id' => $pushed->shipmojo_reference_id],
            ];
        }

        $warehouseId = (string) setting('shipmojo_warehouse_id', '');

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->product_name,
                'sku_number' => $item->sku ?? (string) $item->product_id,
                'quantity' => $item->quantity,
                'discount' => '',
                'hsn' => '',
                'unit_price' => (float) $item->unit_price,
                'product_category' => 'Other',
            ];
        })->toArray();

        $weight = (int) setting('shipmojo_default_weight_grams', 500);
        $length = (int) setting('shipmojo_default_length', 20);
        $width = (int) setting('shipmojo_default_width', 15);
        $height = (int) setting('shipmojo_default_height', 10);

        $payload = [
            'order_id' => $order->order_number,
            'order_date' => $order->created_at->format('Y-m-d'),
            'order_type' => 'ESSENTIALS',
            'consignee_name' => $order->shipping_name,
            'consignee_phone' => (int) preg_replace('/\D/', '', $order->shipping_mobile),
            'consignee_email' => $order->user?->email ?? '',
            'consignee_address_line_one' => $order->shipping_address_line1,
            'consignee_address_line_two' => $order->shipping_address_line2 ?? '',
            'consignee_pin_code' => (int) $order->shipping_pincode,
            'consignee_city' => $order->shipping_city,
            'consignee_state' => $order->shipping_state,
            'product_detail' => $items,
            'payment_type' => $order->payment_method === 'cod' ? 'COD' : 'PREPAID',
            'cod_amount' => $order->payment_method === 'cod' ? (string) $order->grand_total : '',
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'warehouse_id' => $warehouseId,
            'gst_ewaybill_number' => '',
            'gstin_number' => '',
        ];

        $response = $this->post('/push-order', $payload);

        if (($response['result'] ?? '0') === '1') {
            // Reuse this order's existing shipment row (there is exactly one per
            // order) instead of `firstOrNew([])`, which inserted a new row on
            // every push and piled up duplicate shipments.
            $shipment = $order->shipments()->latest('id')->first() ?? $order->shipments()->make();

            $data = $response['data'] ?? [];
            $shipmojoId = $data['order_id'] ?? $data['orderId'] ?? $data['order_uuid'] ?? $data['id'] ?? null;
            $referenceId = $data['reference_id'] ?? $data['referenceId'] ?? $data['ref_id'] ?? null;

            $shipment->fill([
                'order_id' => $order->id,
                'shipping_method' => $order->shipping_method ?? 'standard',
                'status' => 'pending',
                'weight' => $weight,
                'shipmojo_order_id' => $shipmojoId !== null ? (string) $shipmojoId : $order->order_number,
                'shipmojo_reference_id' => $referenceId !== null ? (string) $referenceId : $order->order_number,
                'shipmojo_pushed_at' => now(),
            ])->save();

            Log::info('ShipMojo: Order pushed', ['order' => $order->order_number, 'response' => $response]);
        } else {
            Log::warning('ShipMojo: Push order failed', [
                'order' => $order->order_number,
                'http_status' => $response['http_status'] ?? null,
                'response' => $response,
            ]);
        }

        return $response;
    }

    // ── Auto-Assign Courier ───────────────────────────────────────────────────
    public function autoAssign(Order $order): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('ShipMojo is not enabled or configured.');
        }

        $shipment = $order->shipments()->latest()->first();
        $shipmojoOrderId = $shipment?->shipmojo_order_id ?? $order->order_number;

        $response = $this->post('/auto-assign-order', [
            'order_id' => $shipmojoOrderId,
            'reference_id' => $shipment?->shipmojo_reference_id ?? $order->order_number,
            'merchant_order_id' => $order->order_number,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $data = $response['data'] ?? [];
            $shipment?->update([
                'awb_number' => $data['awb_number'] ?? null,
                'courier' => $data['courier_company'] ?? null,
                'courier_service' => $data['courier_company_service'] ?? null,
                'status' => 'packed',
            ]);

            if ($this->syncOrderStatus($order, 'packed')) {
                $this->notifyOrderStatus($order, 'packed');
            }

            Log::info('ShipMojo: Auto-assign success', ['order' => $order->order_number, 'awb' => $data['awb_number'] ?? null]);
        } else {
            Log::warning('ShipMojo: Auto-assign failed', [
                'order' => $order->order_number,
                'http_status' => $response['http_status'] ?? null,
                'response' => $response,
            ]);
        }

        return $response;
    }

    // ── Assign Courier Manually ───────────────────────────────────────────────
    public function assignCourier(Order $order, int $courierId): array
    {
        $shipment = $order->shipments()->latest()->first();
        $shipmojoOrderId = $shipment?->shipmojo_order_id ?? $order->order_number;

        $response = $this->post('/assign-courier', [
            'order_id' => $shipmojoOrderId,
            'courier_id' => $courierId,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $shipment?->update([
                'courier' => $response['data']['courier'] ?? null,
                'status' => 'packed',
            ]);
        }

        return $response;
    }

    // ── Schedule Pickup ───────────────────────────────────────────────────────
    public function schedulePickup(Order $order): array
    {
        $shipment = $order->shipments()->latest()->first();
        $shipmojoOrderId = $shipment?->shipmojo_order_id ?? $order->order_number;

        $response = $this->post('/schedule-pickup', [
            'order_id' => $shipmojoOrderId,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $data = $response['data'] ?? [];
            $shipment?->update([
                'awb_number' => $data['awb_number'] ?? $shipment?->awb_number,
                'courier' => $data['courier'] ?? $shipment?->courier,
                'lr_number' => $data['lr_number'] ?? null,
                'tracking_number' => $data['awb_number'] ?? $shipment?->tracking_number,
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            if ($this->syncOrderStatus($order, 'shipped')) {
                $this->notifyOrderStatus($order, 'shipped');
            }
        }

        return $response;
    }

    // ── Cancel Order ──────────────────────────────────────────────────────────
    public function cancelOrder(Order $order): array
    {
        $shipment = $order->shipments()->latest()->first();

        if (! $shipment?->awb_number) {
            throw new \RuntimeException('No AWB number found. Cannot cancel.');
        }

        $response = $this->post('/cancel-order', [
            'order_id' => $shipment->shipmojo_order_id ?? $order->order_number,
            'awb_number' => (int) $shipment->awb_number,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $shipment->update(['status' => 'failed']);

            if ($this->syncOrderStatus($order, 'cancelled')) {
                $this->notifyOrderStatus($order, 'cancelled');
            }
        }

        return $response;
    }

    // ── Get Label (base64 PNG) ────────────────────────────────────────────────
    public function getLabel(string $awbNumber): array
    {
        return $this->get('/get-order-label/'.$awbNumber);
    }

    // ── Track Order ───────────────────────────────────────────────────────────
    public function trackOrder(string $awbNumber): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(20)
            ->get($this->baseUrl.'/track-order', ['awb_number' => $awbNumber]);

        return $response->json() ?? ['result' => '0', 'message' => 'Empty response'];
    }

    // ── Sync Tracking to DB ───────────────────────────────────────────────────
    public function syncTracking(Order $order): array
    {
        $shipment = $order->shipments()->latest()->first();

        if (! $shipment?->awb_number) {
            throw new \RuntimeException('No AWB number found. Cannot track.');
        }

        $response = $this->trackOrder($shipment->awb_number);

        if (($response['result'] ?? '0') === '1') {
            $data = $response['data'] ?? [];

            // Update shipment current status
            $currentStatus = $data['current_status'] ?? $data['status'] ?? null;
            if ($currentStatus) {
                $mappedStatus = $this->shipmentStatusFor($this->mapTrackingStatus((string) $currentStatus));
                $forward = $this->canTransitionShipment((string) $shipment->status, $mappedStatus);

                if (! $forward) {
                    // A stale/duplicate carrier callback must not walk the
                    // shipment backwards; keep the record we already have.
                    Log::info('ShipMojo: Ignored non-forward tracking status', [
                        'order' => $order->order_number,
                        'shipment_status' => $shipment->status,
                        'incoming' => $mappedStatus,
                    ]);

                    $mappedStatus = null;
                }

                $updates = [];

                if ($mappedStatus !== null) {
                    $updates['status'] = $mappedStatus;

                    if ($mappedStatus === 'delivered') {
                        $updates['delivered_at'] = now();
                    }
                    if ($mappedStatus === 'shipped') {
                        $updates['shipped_at'] = now();
                    }
                }

                if (! empty($data['expected_delivery_date'])) {
                    $updates['estimated_delivery'] = $data['expected_delivery_date'];
                }

                if ($updates !== []) {
                    $shipment->update($updates);
                }

                $orderStatus = $mappedStatus !== null ? $this->orderStatusForMapped($mappedStatus) : null;
                if ($orderStatus !== null && $this->syncOrderStatus($order, $orderStatus)) {
                    $this->notifyOrderStatus($order, $orderStatus);
                }
            }

            // Sync scan events
            $scans = $data['scan_detail'] ?? $data['scanDetail'] ?? $data['history'] ?? [];
            if (is_array($scans) && count($scans) > 0) {
                foreach ($scans as $scan) {
                    if (! is_array($scan)) {
                        continue;
                    }

                    $scanStatus = $this->extractFirst($scan, ['status', 'current_status', 'activity', 'event']);
                    $eventDate = $this->parseEventDate($this->extractFirst($scan, ['date', 'created_at', 'timestamp', 'datetime']));

                    ShipmentTrackingEvent::updateOrCreate(
                        [
                            'shipment_id' => $shipment->id,
                            'status' => $scanStatus !== null ? strtolower((string) $scanStatus) : 'update',
                            'event_date' => $eventDate,
                            'location' => $this->extractFirst($scan, ['location', 'place', 'city']),
                        ],
                        [
                            'description' => $this->extractFirst($scan, ['activity', 'description', 'remarks', 'message']),
                            'event_date' => $eventDate,
                        ]
                    );
                }
            }

            Log::info('ShipMojo: Tracking synced', ['order' => $order->order_number, 'status' => $currentStatus]);
        }

        return $response;
    }

    // ── Push Return Order ─────────────────────────────────────────────────────
    public function pushReturnOrder(ReturnRequest $returnRequest, int $returnReasonId, string $customerRequest = 'REFUND'): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('ShipMojo is not enabled or configured.');
        }

        $order = $returnRequest->order()->with(['items', 'user'])->first();

        if (! $order) {
            throw new \RuntimeException('Return request has no associated order.');
        }

        $returnRequest->loadMissing('items');

        $items = $returnRequest->items->map(function ($item) {
            return [
                'name' => $item->product_name ?? $item->product?->name ?? 'Product',
                'sku_number' => $item->sku ?? (string) $item->product_id,
                'quantity' => $item->quantity,
                'discount' => '',
                'hsn' => '',
                'unit_price' => (float) ($item->unit_price ?? 0),
                'product_category' => 'Other',
            ];
        })->toArray();

        $weight = (int) setting('shipmojo_default_weight_grams', 500);

        $payload = [
            'order_id' => $returnRequest->return_number,
            'order_date' => $returnRequest->created_at->format('Y-m-d'),
            'order_type' => 'ESSENTIALS',
            'pickup_name' => $order->shipping_name,
            'pickup_phone' => (int) preg_replace('/\D/', '', $order->shipping_mobile),
            'pickup_email' => $order->user?->email ?? '',
            'pickup_address_line_one' => $order->shipping_address_line1,
            'pickup_address_line_two' => $order->shipping_address_line2 ?? '',
            'pickup_pin_code' => (int) $order->shipping_pincode,
            'pickup_city' => $order->shipping_city,
            'pickup_state' => $order->shipping_state,
            'product_detail' => $items,
            'payment_type' => 'PREPAID',
            'weight' => $weight,
            'length' => (int) setting('shipmojo_default_length', 20),
            'width' => (int) setting('shipmojo_default_width', 15),
            'height' => (int) setting('shipmojo_default_height', 10),
            'warehouse_id' => (string) setting('shipmojo_warehouse_id', ''),
            'return_reason_id' => $returnReasonId,
            'customer_request' => $customerRequest,
            'reason_comment' => $returnRequest->description ?? '',
        ];

        $response = $this->post('/push-return-order', $payload);

        if (($response['result'] ?? '0') === '1') {
            $returnRequest->update([
                'status' => 'approved',
                'processed_at' => now(),
            ]);
            Log::info('ShipMojo: Return order pushed', ['return' => $returnRequest->return_number]);
        } else {
            Log::warning('ShipMojo: Push return failed', ['return' => $returnRequest->return_number, 'response' => $response]);
        }

        return $response;
    }

    // ── Get Order Detail ──────────────────────────────────────────────────────
    public function getOrderDetail(string $shipmojoOrderId): array
    {
        return $this->get('/get-order-detail/'.$shipmojoOrderId);
    }

    // ── Status Mapping ────────────────────────────────────────────────────────
    public function mapTrackingStatus(string $shipmojoStatus): string
    {
        $map = [
            'pickup pending' => 'pending',
            'pickup scheduled' => 'pending',
            'picked up' => 'shipped',
            'pickedup' => 'shipped',
            'in transit' => 'shipped',
            'in_transit' => 'shipped',
            'manifested' => 'shipped',
            'manifest' => 'shipped',
            'out for delivery' => 'out_for_delivery',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'delivery failed' => 'failed',
            'delivery_failed' => 'failed',
            'unable to deliver' => 'failed',
            'returning' => 'returning',
            'returned' => 'returned',
            'rto initiated' => 'returning',
            'rto_initiated' => 'returning',
            'rto delivered' => 'returned',
            'rto_delivered' => 'returned',
            'rto' => 'returning',
            'cancelled' => 'cancelled',
            'cancel' => 'cancelled',
            'ready to ship' => 'packed',
            'ready_to_ship' => 'packed',
            'assigned' => 'packed',
            'courier assigned' => 'packed',
            'courier_assigned' => 'packed',
            'label generated' => 'packed',
            'label_generated' => 'packed',
            'packed' => 'packed',
            'pending' => 'pending',
            'processing' => 'pending',
            'confirmed' => 'pending',
        ];

        $mapped = $map[strtolower(trim((string) $shipmojoStatus))] ?? null;

        if ($mapped === null) {
            Log::warning('ShipMojo: Unknown tracking status, defaulting to shipped.', [
                'status' => $shipmojoStatus,
            ]);

            return 'shipped';
        }

        return $mapped;
    }

    /**
     * The order status that should be applied for a mapped shipment status, or
     * null when the shipment status has no order-level equivalent.
     */
    protected function orderStatusForMapped(string $mappedStatus): ?string
    {
        return match ($mappedStatus) {
            'packed' => 'packed',
            'shipped' => 'shipped',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'cancelled', 'returning', 'returned', 'failed' => 'cancelled',
            default => null,
        };
    }

    /**
     * Apply an inbound ShipMojo webhook payload. Flexible parser: the exact field
     * names ShipMojo uses vary, so orders are matched by order id / reference /
     * AWB and statuses by common aliases.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyWebhook(array $payload): array
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('ShipMojo is not enabled or configured.');
        }

        $order = $this->resolveOrderFromWebhook($payload);

        if (! $order) {
            return ['result' => '0', 'message' => 'Order not found', 'matched' => false];
        }

        $shipment = $order->shipments()->latest()->first();

        if (! $shipment) {
            return ['result' => '0', 'message' => 'No shipment found for order.', 'matched' => true];
        }

        $rawStatus = $this->extractStatus($payload);
        $carrierStatus = $rawStatus !== null ? $this->mapTrackingStatus((string) $rawStatus) : null;

        // Forward-only: a late `rto`/`failed`/`returning` callback must never
        // downgrade a shipment (and therefore the order) that already advanced.
        $mappedStatus = null;
        if ($carrierStatus !== null) {
            $target = $this->shipmentStatusFor($carrierStatus);
            if ($this->canTransitionShipment((string) $shipment->status, $target)) {
                $mappedStatus = $carrierStatus;
            } else {
                Log::info('ShipMojo: Webhook status ignored (not a forward transition)', [
                    'order' => $order->order_number,
                    'shipment_status' => $shipment->status,
                    'incoming' => $carrierStatus,
                ]);
            }
        }

        $updates = [];

        $awb = $this->extractFirst($payload, ['awb_number', 'awb', 'awb_no', 'tracking_number', 'tracking_id', 'consignment_number']);
        if ($awb !== null) {
            $updates['awb_number'] = $awb;
            $updates['tracking_number'] = $updates['tracking_number'] ?? $awb;
        }

        $courier = $this->extractFirst($payload, ['courier', 'courier_name', 'courier_partner', 'courier_company', 'carrier', 'logistics_partner', 'operator']);
        if ($courier !== null) {
            $updates['courier'] = $courier;
        }

        $courierService = $this->extractFirst($payload, ['courier_service', 'courier_company_service', 'service', 'service_type', 'courier_type']);
        if ($courierService !== null) {
            $updates['courier_service'] = $courierService;
        }

        $lr = $this->extractFirst($payload, ['lr_number', 'lr_no', 'l_r_number', 'manifest_number']);
        if ($lr !== null) {
            $updates['lr_number'] = $lr;
        }

        $tracking = $this->extractFirst($payload, ['tracking_number', 'tracking_id', 'tracking']);
        if ($tracking !== null) {
            $updates['tracking_number'] = $tracking;
        }

        $expected = $this->extractFirst($payload, ['expected_delivery_date', 'estimated_delivery', 'etd', 'expected_delivery']);
        if ($expected !== null) {
            $updates['estimated_delivery'] = $this->parseEventDate($expected);
        }

        if ($mappedStatus !== null) {
            $updates['status'] = $this->shipmentStatusFor($mappedStatus);

            if ($mappedStatus === 'delivered') {
                $updates['delivered_at'] = now();
            }
            if ($mappedStatus === 'shipped') {
                $updates['shipped_at'] = now();
            }
        }

        if ($updates !== []) {
            $shipment->update($updates);
        }

        // Persist any scan / history events in the payload.
        $scans = $this->extractScans($payload);
        foreach ($scans as $index => $scan) {
            $scanStatus = $this->extractFirst($scan, ['status', 'current_status', 'activity', 'event', 'state']);
            $eventDate = $this->parseEventDate($this->extractFirst($scan, ['date', 'created_at', 'timestamp', 'datetime', 'scan_date']));

            ShipmentTrackingEvent::updateOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'status' => $scanStatus !== null ? strtolower((string) $scanStatus) : 'update',
                    'event_date' => $eventDate,
                    'location' => $this->extractFirst($scan, ['location', 'place', 'city']),
                ],
                [
                    'description' => $this->extractFirst($scan, ['activity', 'description', 'remarks', 'message', 'comment']),
                    'event_date' => $eventDate,
                ]
            );
        }

        $orderStatus = $mappedStatus !== null ? $this->orderStatusForMapped($mappedStatus) : null;
        $statusChanged = $orderStatus !== null && $this->syncOrderStatus($order, $orderStatus);

        if ($statusChanged) {
            $this->notifyOrderStatus($order, $orderStatus);
        }

        Log::info('ShipMojo: Webhook applied', [
            'order' => $order->order_number,
            'shipment_status' => $mappedStatus,
            'order_status_changed' => $statusChanged,
            'courier' => $courier,
            'awb' => $awb,
        ]);

        return [
            'result' => '1',
            'matched' => true,
            'order' => $order->order_number,
            'status' => $mappedStatus,
            'order_status_changed' => $statusChanged,
        ];
    }

    // ── Webhook helpers ──────────────────────────────────────────────────────

    protected function resolveOrderFromWebhook(array $payload): ?Order
    {
        $orderId = $this->extractFirst($payload, [
            'order_id', 'orderId', 'order_uuid', 'external_id', 'order_number', 'order_no', 'reference_id', 'merchant_order_id',
        ]);

        if ($orderId !== null && (string) $orderId !== '') {
            $order = Order::where('order_number', (string) $orderId)->first();

            if ($order) {
                return $order;
            }

            $shipment = Shipment::query()
                ->where('shipmojo_order_id', (string) $orderId)
                ->orWhere('shipmojo_reference_id', (string) $orderId)
                ->latest('id')
                ->first();

            if ($shipment) {
                return $shipment->order;
            }

            // Numeric ShipMojo ids are stored as plain strings.
            $numeric = preg_replace('/\D/', '', (string) $orderId);
            if ($numeric !== '') {
                $shipment = Shipment::query()
                    ->where('shipmojo_order_id', $numeric)
                    ->orWhere('shipmojo_reference_id', $numeric)
                    ->latest('id')
                    ->first();

                if ($shipment) {
                    return $shipment->order;
                }
            }
        }

        $awb = $this->extractFirst($payload, ['awb_number', 'awb', 'awb_no', 'tracking_number', 'tracking_id', 'consignment_number']);
        if ($awb !== null && (string) $awb !== '') {
            $shipment = Shipment::query()
                ->where('awb_number', (string) $awb)
                ->orWhere('tracking_number', (string) $awb)
                ->latest('id')
                ->first();

            if ($shipment) {
                return $shipment->order;
            }
        }

        return null;
    }

    protected function extractStatus(array $payload): ?string
    {
        $status = $this->extractFirst($payload, [
            'status', 'current_status', 'delivery_status', 'order_status', 'shipment_status', 'tracking_status', 'event_status',
        ]);

        if ($status !== null) {
            return $status;
        }

        // Some providers send the event as a dotted name (e.g. "shipment.delivered").
        $event = $this->extractFirst($payload, ['event', 'event_type', 'type', 'webhook_type']);
        if ($event !== null) {
            $parts = explode('.', (string) $event);

            return end($parts);
        }

        return null;
    }

    protected function extractScans(array $payload): array
    {
        foreach (['scan_detail', 'scanDetail', 'history', 'tracking_history', 'events', 'activities', 'data'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_array($value)) {
                if (isset($value['scan_detail']) && is_array($value['scan_detail'])) {
                    return $value['scan_detail'];
                }

                if ($this->isScanList($value)) {
                    return $value;
                }
            }
        }

        return [];
    }

    protected function isScanList(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        foreach ($value as $entry) {
            if (! is_array($entry) || $entry === []) {
                return false;
            }

            // Require at least one scan-like key on the entry.
            if ($this->extractFirst($entry, ['status', 'activity', 'description', 'date', 'location', 'event']) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Search a dotted key path (or common aliases) across the top level and the
     * nested "data" object of a webhook payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractFirst(array $payload, array $keys): mixed
    {
        $candidates = [$payload];

        // Deep search the first nested object that looks like the carrier envelope.
        foreach ($payload as $key => $value) {
            if (is_array($value) && $key === 'data') {
                $candidates[] = $value;
            }
        }

        foreach ($candidates as $candidate) {
            foreach ($keys as $key) {
                $value = $candidate[$key] ?? null;

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function parseEventDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return now()->toDateTimeString();
        }

        if (is_numeric($value) && (int) $value > 1000000000) {
            return Carbon::createFromTimestamp((int) $value)->toDateTimeString();
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return now()->toDateTimeString();
        }
    }

    /**
     * Sync the order status to the given value through OrderService (records
     * status history). Returns whether the order status actually changed.
     */
    protected function syncOrderStatus(Order $order, string $status): bool
    {
        if ($order->order_status === $status) {
            return false;
        }

        // A delivered order is never walked back to cancelled by a carrier
        // callback, even if the shipment record somehow drifted.
        if ($order->order_status === 'delivered' && $status === 'cancelled') {
            Log::warning('ShipMojo: Refusing to cancel an already delivered order.', [
                'order' => $order->order_number,
                'incoming' => $status,
            ]);

            return false;
        }

        try {
            app(OrderService::class)->updateOrderStatus($order, $status, 'Updated via ShipMojo.');

            return true;
        } catch (\Throwable $e) {
            Log::warning('ShipMojo: Could not sync order status.', [
                'order' => $order->order_number,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function notifyOrderStatus(Order $order, string $status): void
    {
        if (! $order->user) {
            return;
        }

        try {
            app(NotificationService::class)->orderStatusChanged($order, $status);
        } catch (\Throwable $e) {
            Log::warning('ShipMojo: Order status notification failed.', [
                'order' => $order->order_number,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
