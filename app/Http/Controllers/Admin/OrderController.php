<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    protected array $orderStatuses = [
        'pending', 'confirmed', 'processing', 'packed', 'shipped',
        'out_for_delivery', 'delivered', 'cancelled', 'failed',
    ];

    protected array $paymentStatuses = [
        'pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded', 'partially_refunded',
    ];

    public function __construct(
        protected OrderService $orderService,
        protected ActivityLogger $logger,
    ) {
    }

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['user', 'items', 'payments'])
            ->withCount('items')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where(function ($query) use ($q) {
                    $query->where('order_number', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($query) use ($q) {
                            $query->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->when($request->filled('order_status'), function ($query) use ($request) {
                $query->where('order_status', $request->input('order_status'));
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->input('payment_status'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'user', 'payments', 'statusHistories', 'shipments', 'refunds']);

        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        $order->load(['items', 'user', 'payments', 'statusHistories', 'shipments', 'refunds']);

        return view('admin.orders.edit', [
            'order' => $order,
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        return redirect()->back()->with('success', 'Order updated.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'order_status' => 'required|in:confirmed,processing,packed,shipped,out_for_delivery,delivered,cancelled',
            'description' => 'nullable|string|max:500',
        ]);

        $newStatus = $data['order_status'];
        $oldStatus = $order->order_status;

        try {
            $this->orderService->updateOrderStatus($order, $newStatus, $data['description'] ?? null);

            $this->logger->orderStatusChanged(
                auth('admin')->user(),
                $order,
                $oldStatus,
                $newStatus,
                $data['description'] ?? "Order status changed to {$newStatus}."
            );

            app(NotificationService::class)->orderStatusChanged($order, $newStatus);

            return redirect()->back()->with('success', "Order status updated to {$newStatus}.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => 'required|in:paid,failed,cancelled',
        ]);

        $newStatus = $data['payment_status'];

        $order->update(['payment_status' => $newStatus]);

        if ($newStatus === 'paid') {
            Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'status' => 'paid',
                    'paid_at' => now(),
                    'amount' => $order->amount_due,
                ]
            );
        }

        return redirect()->back()->with('success', "Payment status updated to {$newStatus}.");
    }

    public function addNote(Request $request, Order $order)
    {
        $data = $request->validate([
            'internal_notes' => 'nullable|string|max:2000',
        ]);

        $note = $data['internal_notes'] ?? null;

        $existing = $order->internal_notes ? $order->internal_notes."\n" : '';
        $order->update([
            'internal_notes' => $existing.'['.now()->format('d M Y H:i').'] '.$note,
        ]);

        return redirect()->back()->with('success', 'Note added.');
    }
}
