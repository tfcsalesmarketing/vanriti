<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentTrackingEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    protected array $statuses = [
        'pending', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed',
    ];

    public function index(Request $request): View
    {
        $shipments = Shipment::query()
            ->with(['order.user'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.shipments.index', [
            'shipments' => $shipments,
            'statuses' => $this->statuses,
        ]);
    }

    public function show(Shipment $shipment): View
    {
        $shipment->load(['trackingEvents', 'order.user']);

        return view('admin.shipments.show', [
            'shipment' => $shipment,
            'statuses' => $this->statuses,
        ]);
    }

    public function addTrackingEvent(Request $request, Shipment $shipment)
    {
        $data = $request->validate([
            'status' => 'required|in:packed,shipped,out_for_delivery,delivered,failed,returning',
            'description' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'estimated_delivery' => 'nullable|date',
        ]);

        ShipmentTrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'event_date' => $data['event_date'],
        ]);

        $shipmentUpdates = ['status' => $data['status']];

        if ($data['status'] === 'shipped') {
            $shipmentUpdates['shipped_at'] = now();
        }

        if ($data['status'] === 'delivered') {
            $shipmentUpdates['delivered_at'] = now();
        }

        if (! empty($data['estimated_delivery'])) {
            $shipmentUpdates['estimated_delivery'] = $data['estimated_delivery'];
        }

        $shipment->update($shipmentUpdates);

        return redirect()->back()->with('success', 'Tracking event added.');
    }
}
