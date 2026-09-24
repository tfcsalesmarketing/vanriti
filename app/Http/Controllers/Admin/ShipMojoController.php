<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\ShipMojoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShipMojoController extends Controller
{
    public function __construct(protected ShipMojoService $shipmojo) {}

    // ── Push Order ─────────────────────────────────────────────────────────────
    public function pushOrder(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->pushOrder($order);

            if (($response['result'] ?? '0') === '1') {
                return back()->with('success', 'Order pushed to ShipMojo successfully! Reference: '.($response['data']['reference_id'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Auto-Assign Courier ────────────────────────────────────────────────────
    public function autoAssign(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->autoAssign($order);

            if (($response['result'] ?? '0') === '1') {
                $data = $response['data'] ?? [];

                return back()->with('success', 'Courier assigned! AWB: '.($data['awb_number'] ?? 'N/A').' via '.($data['courier_company'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Schedule Pickup ────────────────────────────────────────────────────────
    public function schedulePickup(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->schedulePickup($order);

            if (($response['result'] ?? '0') === '1') {
                $data = $response['data'] ?? [];

                // The service syncs the order to "shipped" and notifies the
                // customer; no per-request notification here.

                return back()->with('success', 'Pickup scheduled! AWB: '.($data['awb_number'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Cancel Shipment ────────────────────────────────────────────────────────
    public function cancelShipment(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->cancelOrder($order);

            if (($response['result'] ?? '0') === '1') {
                return back()->with('success', 'Shipment cancelled in ShipMojo.');
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Download Label ─────────────────────────────────────────────────────────
    public function getLabel(Order $order): Response|RedirectResponse
    {
        try {
            $shipment = $order->shipments()->latest()->first();

            if (! $shipment?->awb_number) {
                return back()->with('error', 'No AWB number found. Please assign a courier first.');
            }

            $response = $this->shipmojo->getLabel($shipment->awb_number);

            if (($response['result'] ?? '0') !== '1' || empty($response['data'][0]['label'])) {
                return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
            }

            // Labels generated means the order is packed and ready to dispatch.
            // The courier-assignment flow already moves the order to "packed",
            // so only alert if it somehow had not yet reached that state.
            if (in_array($order->order_status, ['pending', 'confirmed', 'processing'], true)) {
                $order->refresh()->load('user');

                if ($order->user) {
                    app(\App\Services\NotificationService::class)->orderStatusChanged($order, 'packed');
                }
            }

            // Decode base64 PNG and return as download
            $base64 = $response['data'][0]['label'];
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $imageData = base64_decode($base64);

            return response($imageData, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="label-'.$shipment->awb_number.'.png"');
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Sync Tracking ──────────────────────────────────────────────────────────
    public function syncTracking(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->syncTracking($order);

            if (($response['result'] ?? '0') === '1') {
                $status = $response['data']['current_status'] ?? $response['data']['status'] ?? 'Unknown';

                // The service syncs the order status (shipped / out_for_delivery /
                // delivered / cancelled) and notifies the customer on change.

                return back()->with('success', "Tracking synced! Current status: {$status}");
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Bulk Push to ShipMojo ─────────────────────────────────────────────────
    public function bulkPush(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::whereKey($data['order_ids'])->get();

        [$succeeded, $failed, $errors] = $this->runBulk(
            $orders,
            fn (Order $order) => $this->shipmojo->pushOrder($order)
        );

        return $this->bulkRedirect($succeeded, $failed, $errors, 'pushed to ShipMojo');
    }

    // ── Bulk Auto-Assign Courier ──────────────────────────────────────────────
    public function bulkAutoAssign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('shipments')->whereKey($data['order_ids'])->get();

        [$succeeded, $failed, $errors] = $this->runBulk(
            $orders,
            function (Order $order) {
                $shipment = $order->shipments()->latest()->first();

                if (! $shipment?->isPushedToShipMojo()) {
                    throw new \RuntimeException('Order has not been pushed to ShipMojo.');
                }
                if ($shipment->hasAwb()) {
                    throw new \RuntimeException('Courier already assigned (AWB exists).');
                }

                return $this->shipmojo->autoAssign($order);
            }
        );

        return $this->bulkRedirect($succeeded, $failed, $errors, 'courier assigned');
    }

    // ── Bulk Schedule Pickup ──────────────────────────────────────────────────
    public function bulkSchedulePickup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('shipments')->whereKey($data['order_ids'])->get();

        [$succeeded, $failed, $errors] = $this->runBulk(
            $orders,
            function (Order $order) {
                $shipment = $order->shipments()->latest()->first();

                if (! $shipment?->isPushedToShipMojo()) {
                    throw new \RuntimeException('Order has not been pushed to ShipMojo.');
                }
                if (! $shipment->hasAwb()) {
                    throw new \RuntimeException('No AWB assigned; assign a courier first.');
                }

                return $this->shipmojo->schedulePickup($order);
            }
        );

        return $this->bulkRedirect($succeeded, $failed, $errors, 'pickup scheduled');
    }

    // ── Bulk Download Labels (ZIP) ────────────────────────────────────────────
    public function bulkLabels(Request $request): Response|JsonResponse|BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('shipments')->whereKey($data['order_ids'])->get();

        $labels = [];

        foreach ($orders as $order) {
            $shipment = $order->shipments()->latest()->first();

            if (! $shipment?->awb_number) {
                continue;
            }

            try {
                $response = $this->shipmojo->getLabel($shipment->awb_number);

                if (($response['result'] ?? '0') !== '1' || empty($response['data'][0]['label'])) {
                    continue;
                }

                $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $response['data'][0]['label']);
                $imageData = base64_decode((string) $base64, true);

                if ($imageData !== false) {
                    $labels[$order->order_number.'-'.$shipment->awb_number.'.png'] = $imageData;
                }
            } catch (\Throwable $e) {
                //
            }
        }

        if ($labels === []) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No labels were available for the selected orders.'], 422);
            }

            return back()->with('error', 'No labels were available for the selected orders.');
        }

        if (! class_exists(\ZipArchive::class)) {
            $first = array_key_first($labels);

            return response($labels[$first], 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="label-'.$first.'"');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'sm_labels_').'.zip';

        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($labels as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return response()->download($tmp, 'shipmojo-labels-'.now()->format('Y-m-d-Hi').'.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Run a ShipMojo action against each order, collecting per-order results.
     *
     * @return array{0: int, 1: int, 2: array<int, string>}
     */
    protected function runBulk(iterable $orders, callable $action): array
    {
        $succeeded = 0;
        $failed = 0;
        $errors = [];

        foreach ($orders as $order) {
            try {
                $response = $action($order);

                if (($response['result'] ?? '0') === '1') {
                    $succeeded++;
                } else {
                    $failed++;
                    $errors[] = $order->order_number.': '.$this->shipmojo->errorMessage($response);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $order->order_number.': '.$e->getMessage();
            }
        }

        return [$succeeded, $failed, $errors];
    }

    protected function bulkRedirect(int $succeeded, int $failed, array $errors, string $action): RedirectResponse
    {
        if ($failed === 0) {
            return back()->with('success', "{$succeeded} order(s) {$action} successfully.");
        }

        $details = $errors !== [] ? ' '.implode(' | ', array_slice($errors, 0, 5)) : '';

        return back()->with(
            $succeeded === 0 ? 'error' : 'warning',
            "{$succeeded} order(s) {$action}, {$failed} failed.{$details}"
        );
    }

    // ── Push Return Order ──────────────────────────────────────────────────────
    public function pushReturnOrder(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $data = $request->validate([
            'return_reason_id' => 'required|integer',
            'customer_request' => 'required|in:REFUND,REPLACEMENT',
        ]);

        try {
            $response = $this->shipmojo->pushReturnOrder(
                $returnRequest,
                (int) $data['return_reason_id'],
                $data['customer_request']
            );

            if (($response['result'] ?? '0') === '1') {
                return back()->with('success', 'Return order pushed to ShipMojo! Reference: '.($response['data']['reference_id'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: '.$this->shipmojo->errorMessage($response));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: '.$e->getMessage());
        }
    }

    // ── Get Warehouses ─────────────────────────────────────────────────────────
    public function warehouses(): JsonResponse
    {
        try {
            $response = $this->shipmojo->getWarehouses();

            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json(['result' => '0', 'message' => $e->getMessage()]);
        }
    }

    // ── Rate Calculator ────────────────────────────────────────────────────────
    public function rates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pickup_pincode' => 'required|digits:6',
            'delivery_pincode' => 'required|digits:6',
            'payment_type' => 'required|in:PREPAID,COD',
            'weight' => 'required|numeric',
            'order_amount' => 'required|numeric',
        ]);

        try {
            $response = $this->shipmojo->getRates([
                'pickup_pincode' => (int) $data['pickup_pincode'],
                'delivery_pincode' => (int) $data['delivery_pincode'],
                'payment_type' => $data['payment_type'],
                'shipment_type' => 'FORWARD',
                'order_amount' => (float) $data['order_amount'],
                'type_of_package' => 'SPS',
                'rov_type' => 'ROV_OWNER',
                'cod_amount' => '',
                'weight' => (float) $data['weight'],
                'dimensions' => [[
                    'no_of_box' => '1',
                    'length' => setting('shipmojo_default_length', 20),
                    'width' => setting('shipmojo_default_width', 15),
                    'height' => setting('shipmojo_default_height', 10),
                ]],
            ]);

            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json(['result' => '0', 'message' => $e->getMessage()]);
        }
    }

    // ── Ping / Test Connection ─────────────────────────────────────────────────
    public function ping(): JsonResponse
    {
        try {
            $response = $this->shipmojo->ping();

            return response()->json($response);
        } catch (\Throwable $e) {
            return response()->json(['result' => '0', 'message' => $e->getMessage()]);
        }
    }
}
