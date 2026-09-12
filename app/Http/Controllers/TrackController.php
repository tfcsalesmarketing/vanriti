<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackController extends Controller
{
    public function index(): View
    {
        $orders = null;

        return view('storefront.track.index', compact('orders'));
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $request->validate([
            'order_number' => 'required|string|max:30',
            'mobile' => 'required|string|max:15',
        ]);

        $orders = Order::with(['items', 'shipments.trackingEvents'])
            ->where('order_number', $request->order_number)
            ->where(function ($q) use ($request) {
                $q->where('shipping_mobile', $request->mobile)
                    ->orWhere('billing_mobile', $request->mobile);
            })
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No order found with the given details. Please check and try again.');
        }

        return view('storefront.track.index', compact('orders'));
    }
}
