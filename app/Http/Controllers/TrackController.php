<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ShipMojoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TrackController extends Controller
{
    public function __construct(
        protected ShipMojoService $shipMojoService,
    ) {}

    public function index(Request $request): View
    {
        $orders = null;
        $recentOrders = collect();

        if (auth('web')->check()) {
            $recentOrders = Order::with(['items', 'shipments.trackingEvents'])
                ->where('user_id', auth('web')->id())
                ->latest()
                ->take(3)
                ->get();
        }

        return view('storefront.track.index', compact('orders', 'recentOrders'));
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $request->validate([
            'order_number' => 'required|string|max:40',
            'mobile' => 'required|string|max:20',
        ]);

        $search = trim((string) $request->input('order_number'));
        $mobile = canonical_phone($request->string('mobile')->toString());

        if ($mobile === null) {
            return back()->with('error', 'Please enter a valid 10-digit mobile number.')->withInput();
        }

        $order = Order::with(['items', 'shipments.trackingEvents'])
            ->where(function ($q) use ($search) {
                $q->where('order_number', $search)
                    ->orWhereHas('shipments', function ($sq) use ($search) {
                        $sq->where('awb_number', $search)
                            ->orWhere('tracking_number', $search);
                    });
            })
            ->first();

        if (! $order || ! $this->mobileMatches($order, $mobile)) {
            return back()->with('error', 'No order found matching those details. Please check your Order Number or AWB Number and try again.')->withInput();
        }

        $this->maybeSyncTracking($order, (bool) $request->input('refresh', false));
        $order->load(['items', 'shipments.trackingEvents' => fn ($q) => $q->orderByDesc('event_date')]);

        $orders = collect([$order]);
        $recentOrders = collect();

        return view('storefront.track.index', compact('orders', 'recentOrders'));
    }

    public function show(Order $order, Request $request): View|RedirectResponse
    {
        $mobile = canonical_phone(trim((string) $request->query('mobile', '')));
        $isOwner = auth('web')->check() && auth('web')->id() === $order->user_id;

        if (! $isOwner) {
            if ($mobile === null || ! $this->mobileMatches($order, $mobile)) {
                abort(403, 'Unauthorized order tracking request.');
            }
        }

        $this->maybeSyncTracking($order, (bool) $request->query('refresh', false));
        $order->load(['items', 'shipments.trackingEvents' => fn ($q) => $q->orderByDesc('event_date')]);

        $orders = collect([$order]);
        $recentOrders = collect();

        return view('storefront.track.index', compact('orders', 'recentOrders'));
    }

    protected function maybeSyncTracking(Order $order, bool $force = false): void
    {
        $shipment = $order->shipments()->latest()->first();

        if (! $shipment?->awb_number || in_array($order->order_status, ['delivered', 'cancelled'], true)) {
            return;
        }

        $cacheKey = 'shipmojo_tracking_sync_' . $order->id;

        if ($force || ! Cache::has($cacheKey)) {
            try {
                if ($this->shipMojoService->isEnabled()) {
                    $this->shipMojoService->syncTracking($order);
                    Cache::put($cacheKey, true, now()->addMinutes(5));
                }
            } catch (\Throwable $e) {
                Log::warning('ShipMojo tracking sync skipped during track page view', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function mobileMatches(Order $order, string $canonical): bool
    {
        $shipping = canonical_phone($order->shipping_mobile);
        $billing = canonical_phone($order->billing_mobile);
        $guest = canonical_phone($order->guest_mobile);
        $userPhone = canonical_phone($order->user?->phone);

        return $shipping === $canonical
            || $billing === $canonical
            || $guest === $canonical
            || $userPhone === $canonical;
    }
}
