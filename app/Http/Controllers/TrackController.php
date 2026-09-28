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
            'mobile' => 'required|string|max:20',
        ]);

        $mobile = canonical_phone($request->string('mobile')->toString());

        if ($mobile === null) {
            return back()->with('error', 'Please enter a valid 10-digit mobile number.')->withInput();
        }

        $orders = Order::with(['items', 'shipments.trackingEvents'])
            ->where('order_number', $request->order_number)
            ->get()
            ->filter(fn (Order $order) => $this->mobileMatches($order, $mobile))
            ->values();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No order found with the given details. Please check and try again.');
        }

        return view('storefront.track.index', compact('orders'));
    }

    public function show(Order $order, Request $request): View|RedirectResponse
    {
        $mobile = canonical_phone(trim((string) $request->query('mobile', '')));

        if ($mobile === null || ! $this->mobileMatches($order, $mobile)) {
            abort(403);
        }

        $order->load(['items', 'shipments.trackingEvents']);
        $orders = collect([$order]);

        return view('storefront.track.index', compact('orders'));
    }

    protected function mobileMatches(Order $order, string $canonical): bool
    {
        return canonical_phone($order->shipping_mobile) === $canonical
            || canonical_phone($order->billing_mobile) === $canonical;
    }
}
