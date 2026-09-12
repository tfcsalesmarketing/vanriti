<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\ShipMojoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ShipMojoController extends Controller
{
    public function __construct(protected ShipMojoService $shipmojo)
    {
    }

    // ── Push Order ─────────────────────────────────────────────────────────────
    public function pushOrder(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->pushOrder($order);

            if (($response['result'] ?? '0') === '1') {
                return back()->with('success', 'Order pushed to ShipMojo successfully! Reference: ' . ($response['data']['reference_id'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
        }
    }

    // ── Auto-Assign Courier ────────────────────────────────────────────────────
    public function autoAssign(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->autoAssign($order);

            if (($response['result'] ?? '0') === '1') {
                $data = $response['data'] ?? [];
                return back()->with('success', 'Courier assigned! AWB: ' . ($data['awb_number'] ?? 'N/A') . ' via ' . ($data['courier_company'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
        }
    }

    // ── Schedule Pickup ────────────────────────────────────────────────────────
    public function schedulePickup(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->schedulePickup($order);

            if (($response['result'] ?? '0') === '1') {
                $data = $response['data'] ?? [];
                return back()->with('success', 'Pickup scheduled! AWB: ' . ($data['awb_number'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
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

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
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
                return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Label not available'));
            }

            // Decode base64 PNG and return as download
            $base64 = $response['data'][0]['label'];
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $imageData = base64_decode($base64);

            return response($imageData, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="label-' . $shipment->awb_number . '.png"');
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
        }
    }

    // ── Sync Tracking ──────────────────────────────────────────────────────────
    public function syncTracking(Order $order): RedirectResponse
    {
        try {
            $response = $this->shipmojo->syncTracking($order);

            if (($response['result'] ?? '0') === '1') {
                $status = $response['data']['current_status'] ?? 'Unknown';
                return back()->with('success', "Tracking synced! Current status: {$status}");
            }

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
        }
    }

    // ── Push Return Order ──────────────────────────────────────────────────────
    public function pushReturnOrder(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $data = $request->validate([
            'return_reason_id'  => 'required|integer',
            'customer_request'  => 'required|in:REFUND,REPLACEMENT',
        ]);

        try {
            $response = $this->shipmojo->pushReturnOrder(
                $returnRequest,
                (int) $data['return_reason_id'],
                $data['customer_request']
            );

            if (($response['result'] ?? '0') === '1') {
                return back()->with('success', 'Return order pushed to ShipMojo! Reference: ' . ($response['data']['reference_id'] ?? 'N/A'));
            }

            return back()->with('error', 'ShipMojo Error: ' . ($response['message'] ?? 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', 'ShipMojo: ' . $e->getMessage());
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
            'pickup_pincode'    => 'required|digits:6',
            'delivery_pincode'  => 'required|digits:6',
            'payment_type'      => 'required|in:PREPAID,COD',
            'weight'            => 'required|numeric',
            'order_amount'      => 'required|numeric',
        ]);

        try {
            $response = $this->shipmojo->getRates([
                'pickup_pincode'    => (int) $data['pickup_pincode'],
                'delivery_pincode'  => (int) $data['delivery_pincode'],
                'payment_type'      => $data['payment_type'],
                'shipment_type'     => 'FORWARD',
                'order_amount'      => (float) $data['order_amount'],
                'type_of_package'   => 'SPS',
                'rov_type'          => 'ROV_OWNER',
                'cod_amount'        => '',
                'weight'            => (float) $data['weight'],
                'dimensions'        => [[
                    'no_of_box' => '1',
                    'length'    => setting('shipmojo_default_length', 20),
                    'width'     => setting('shipmojo_default_width', 15),
                    'height'    => setting('shipmojo_default_height', 10),
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
