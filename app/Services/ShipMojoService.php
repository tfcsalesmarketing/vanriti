<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShipMojoService
{
    protected string $baseUrl = 'https://shipping-api.com/app/api/v1';

    protected function headers(): array
    {
        return [
            'public-key'  => (string) setting('shipmojo_public_key', ''),
            'private-key' => (string) secret_setting('shipmojo_private_key', ''),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    protected function isEnabled(): bool
    {
        return (bool) setting('shipmojo_enabled', false)
            && ! empty(setting('shipmojo_public_key', ''))
            && ! empty(secret_setting('shipmojo_private_key', ''));
    }

    protected function get(string $endpoint): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(20)
            ->get($this->baseUrl . $endpoint);

        return $response->json() ?? ['result' => '0', 'message' => 'Empty response'];
    }

    protected function post(string $endpoint, array $body): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(20)
            ->post($this->baseUrl . $endpoint, $body);

        return $response->json() ?? ['result' => '0', 'message' => 'Empty response'];
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
            'pickup_pincode'   => (int) $pickupPincode,
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

        $warehouseId = (string) setting('shipmojo_warehouse_id', '');

        $items = $order->items->map(function ($item) {
            return [
                'name'             => $item->product_name,
                'sku_number'       => $item->sku ?? (string) $item->product_id,
                'quantity'         => $item->quantity,
                'discount'         => '',
                'hsn'              => '',
                'unit_price'       => (float) $item->unit_price,
                'product_category' => 'Other',
            ];
        })->toArray();

        $weight = (int) setting('shipmojo_default_weight_grams', 500);
        $length = (int) setting('shipmojo_default_length', 20);
        $width  = (int) setting('shipmojo_default_width', 15);
        $height = (int) setting('shipmojo_default_height', 10);

        $payload = [
            'order_id'                   => $order->order_number,
            'order_date'                 => $order->created_at->format('Y-m-d'),
            'order_type'                 => 'ESSENTIALS',
            'consignee_name'             => $order->shipping_name,
            'consignee_phone'            => (int) preg_replace('/\D/', '', $order->shipping_mobile),
            'consignee_email'            => $order->user?->email ?? '',
            'consignee_address_line_one' => $order->shipping_address_line1,
            'consignee_address_line_two' => $order->shipping_address_line2 ?? '',
            'consignee_pin_code'         => (int) $order->shipping_pincode,
            'consignee_city'             => $order->shipping_city,
            'consignee_state'            => $order->shipping_state,
            'product_detail'             => $items,
            'payment_type'               => $order->payment_method === 'cod' ? 'COD' : 'PREPAID',
            'cod_amount'                 => $order->payment_method === 'cod' ? (string) $order->grand_total : '',
            'weight'                     => $weight,
            'length'                     => $length,
            'width'                      => $width,
            'height'                     => $height,
            'warehouse_id'               => $warehouseId,
            'gst_ewaybill_number'        => '',
            'gstin_number'               => '',
        ];

        $response = $this->post('/push-order', $payload);

        if (($response['result'] ?? '0') === '1') {
            // Create/update shipment record
            $shipment = $order->shipments()->firstOrNew([]);
            $shipment->fill([
                'order_id'            => $order->id,
                'shipping_method'     => $order->shipping_method ?? 'standard',
                'status'              => 'pending',
                'weight'              => $weight,
                'shipmojo_order_id'   => $response['data']['order_id'] ?? $order->order_number,
                'shipmojo_reference_id' => $response['data']['reference_id'] ?? $order->order_number,
                'shipmojo_pushed_at'  => now(),
            ])->save();

            Log::info('ShipMojo: Order pushed', ['order' => $order->order_number, 'response' => $response]);
        } else {
            Log::warning('ShipMojo: Push order failed', ['order' => $order->order_number, 'response' => $response]);
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
        ]);

        if (($response['result'] ?? '0') === '1') {
            $data = $response['data'] ?? [];
            $shipment?->update([
                'awb_number'      => $data['awb_number'] ?? null,
                'courier'         => $data['courier_company'] ?? null,
                'courier_service' => $data['courier_company_service'] ?? null,
                'status'          => 'packed',
            ]);

            Log::info('ShipMojo: Auto-assign success', ['order' => $order->order_number, 'awb' => $data['awb_number'] ?? null]);
        } else {
            Log::warning('ShipMojo: Auto-assign failed', ['order' => $order->order_number, 'response' => $response]);
        }

        return $response;
    }

    // ── Assign Courier Manually ───────────────────────────────────────────────
    public function assignCourier(Order $order, int $courierId): array
    {
        $shipment = $order->shipments()->latest()->first();
        $shipmojoOrderId = $shipment?->shipmojo_order_id ?? $order->order_number;

        $response = $this->post('/assign-courier', [
            'order_id'   => $shipmojoOrderId,
            'courier_id' => $courierId,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $shipment?->update([
                'courier' => $response['data']['courier'] ?? null,
                'status'  => 'packed',
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
                'awb_number'      => $data['awb_number'] ?? $shipment?->awb_number,
                'courier'         => $data['courier'] ?? $shipment?->courier,
                'lr_number'       => $data['lr_number'] ?? null,
                'tracking_number' => $data['awb_number'] ?? $shipment?->tracking_number,
                'status'          => 'shipped',
                'shipped_at'      => now(),
            ]);
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
            'order_id'   => $shipment->shipmojo_order_id ?? $order->order_number,
            'awb_number' => (int) $shipment->awb_number,
        ]);

        if (($response['result'] ?? '0') === '1') {
            $shipment->update(['status' => 'failed']);
        }

        return $response;
    }

    // ── Get Label (base64 PNG) ────────────────────────────────────────────────
    public function getLabel(string $awbNumber): array
    {
        return $this->get('/get-order-label/' . $awbNumber);
    }

    // ── Track Order ───────────────────────────────────────────────────────────
    public function trackOrder(string $awbNumber): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(20)
            ->get($this->baseUrl . '/track-order', ['awb_number' => $awbNumber]);

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
            $currentStatus = $data['current_status'] ?? null;
            if ($currentStatus) {
                $mappedStatus = $this->mapTrackingStatus($currentStatus);
                $updates = ['status' => $mappedStatus];

                if ($mappedStatus === 'delivered') {
                    $updates['delivered_at'] = now();
                }
                if (! empty($data['expected_delivery_date'])) {
                    $updates['estimated_delivery'] = $data['expected_delivery_date'];
                }

                $shipment->update($updates);
            }

            // Sync scan events
            if (! empty($data['scan_detail']) && is_array($data['scan_detail'])) {
                foreach ($data['scan_detail'] as $scan) {
                    ShipmentTrackingEvent::updateOrCreate(
                        [
                            'shipment_id' => $shipment->id,
                            'status'      => $scan['status'] ?? 'update',
                            'event_date'  => $scan['date'] ?? now()->toDateString(),
                        ],
                        [
                            'description' => $scan['activity'] ?? ($scan['description'] ?? null),
                            'location'    => $scan['location'] ?? null,
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
                'name'             => $item->product_name ?? $item->product?->name ?? 'Product',
                'sku_number'       => $item->sku ?? (string) $item->product_id,
                'quantity'         => $item->quantity,
                'discount'         => '',
                'hsn'              => '',
                'unit_price'       => (float) ($item->unit_price ?? 0),
                'product_category' => 'Other',
            ];
        })->toArray();

        $weight = (int) setting('shipmojo_default_weight_grams', 500);

        $payload = [
            'order_id'                => $returnRequest->return_number,
            'order_date'              => $returnRequest->created_at->format('Y-m-d'),
            'order_type'              => 'ESSENTIALS',
            'pickup_name'             => $order->shipping_name,
            'pickup_phone'            => (int) preg_replace('/\D/', '', $order->shipping_mobile),
            'pickup_email'            => $order->user?->email ?? '',
            'pickup_address_line_one' => $order->shipping_address_line1,
            'pickup_address_line_two' => $order->shipping_address_line2 ?? '',
            'pickup_pin_code'         => (int) $order->shipping_pincode,
            'pickup_city'             => $order->shipping_city,
            'pickup_state'            => $order->shipping_state,
            'product_detail'          => $items,
            'payment_type'            => 'PREPAID',
            'weight'                  => $weight,
            'length'                  => (int) setting('shipmojo_default_length', 20),
            'width'                   => (int) setting('shipmojo_default_width', 15),
            'height'                  => (int) setting('shipmojo_default_height', 10),
            'warehouse_id'            => (string) setting('shipmojo_warehouse_id', ''),
            'return_reason_id'        => $returnReasonId,
            'customer_request'        => $customerRequest,
            'reason_comment'          => $returnRequest->description ?? '',
        ];

        $response = $this->post('/push-return-order', $payload);

        if (($response['result'] ?? '0') === '1') {
            $returnRequest->update([
                'status'       => 'approved',
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
        return $this->get('/get-order-detail/' . $shipmojoOrderId);
    }

    // ── Status Mapping ────────────────────────────────────────────────────────
    protected function mapTrackingStatus(string $shipmojoStatus): string
    {
        $map = [
            'pickup pending'      => 'pending',
            'pickup scheduled'    => 'pending',
            'picked up'           => 'shipped',
            'in transit'          => 'shipped',
            'out for delivery'    => 'out_for_delivery',
            'delivered'           => 'delivered',
            'delivery failed'     => 'failed',
            'returning'           => 'returning',
            'returned'            => 'returned',
            'rto initiated'       => 'returning',
            'rto delivered'       => 'returned',
        ];

        return $map[strtolower($shipmojoStatus)] ?? 'shipped';
    }
}
