<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

    protected array $tabs = [
        'pending' => 'Pending Orders',
        'assign_courier' => 'Assign Courier',
        'ready_to_ship' => 'Ready to Ship',
        'shipped' => 'Shipped',
        'cancelled' => 'Cancelled',
        'delivered' => 'Delivered',
    ];

    public function __construct(
        protected OrderService $orderService,
        protected ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $activeTab = (string) $request->input('tab', 'pending');

        if (! array_key_exists($activeTab, $this->tabs)) {
            $activeTab = 'pending';
        }

        // Per-tab counts (independent of the active filter query).
        $tabCounts = [];
        foreach ($this->tabs as $key => $label) {
            $countQuery = Order::query();
            $this->applyTabScope($countQuery, $key);
            $tabCounts[$key] = $countQuery->count();
        }

        $orders = Order::query()
            ->with(['user', 'items', 'payments', 'shipments'])
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
            });

        $this->applyTabScope($orders, $activeTab);

        $orders = $orders
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
            'tabs' => $this->tabs,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Scope the orders query to one pipeline tab. Uses order status (now kept in
     * sync by ShipMojo webhooks) plus shipment state so existing rows without a
     * synced order status still land on the right tab.
     */
    protected function applyTabScope(Builder $query, string $tab): Builder
    {
        switch ($tab) {
            case 'pending':
                $query->whereNotIn('order_status', ['cancelled', 'failed', 'delivered'])
                    ->whereDoesntHave('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at');
                    });
                break;

            case 'ready_to_ship':
                $query->whereNotIn('order_status', ['shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'])
                    ->whereHas('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at')
                            ->whereNotNull('awb_number')
                            ->whereNotIn('status', ['shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled']);
                    });
                break;

            case 'assign_courier':
                $query->whereNotIn('order_status', ['cancelled', 'failed', 'delivered'])
                    ->whereHas('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at')
                            ->whereNotIn('status', ['delivered', 'returning', 'returned', 'failed', 'cancelled']);
                    })
                    ->whereDoesntHave('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at')->whereNotNull('awb_number');
                    });
                break;

            case 'shipped':
                $query->where(function (Builder $q) {
                    $q->whereIn('order_status', ['shipped', 'out_for_delivery'])
                        ->orWhereHas('shipments', function (Builder $sq) {
                            $sq->whereIn('status', ['shipped', 'out_for_delivery']);
                        });
                });
                break;

            case 'cancelled':
                $query->where(function (Builder $q) {
                    $q->whereIn('order_status', ['cancelled', 'failed'])
                        ->orWhereHas('shipments', function (Builder $sq) {
                            $sq->whereIn('status', ['returning', 'returned', 'failed', 'cancelled']);
                        });
                });
                break;

            case 'delivered':
                $query->where(function (Builder $q) {
                    $q->where('order_status', 'delivered')
                        ->orWhereHas('shipments', function (Builder $sq) {
                            $sq->whereIn('status', ['delivered']);
                        });
                });
                break;
        }

        return $query;
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

    public function bulkCancel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::with('items')->whereKey($data['order_ids'])->get();

        $succeeded = 0;
        $errors = [];

        foreach ($orders as $order) {
            try {
                $oldStatus = $order->order_status;

                $this->orderService->cancelOrder($order, 'Cancelled via admin bulk action');

                $this->logger->orderStatusChanged(
                    auth('admin')->user(),
                    $order,
                    $oldStatus,
                    'cancelled',
                    "Order cancelled via admin bulk action."
                );

                if ($order->user) {
                    app(NotificationService::class)->orderStatusChanged($order, 'cancelled');
                }

                $succeeded++;
            } catch (\Throwable $e) {
                $errors[] = $order->order_number.': '.$e->getMessage();
            }
        }

        if ($errors === []) {
            return redirect()->back()->with('success', "{$succeeded} order(s) cancelled successfully.");
        }

        return redirect()->back()->with(
            $succeeded === 0 ? 'error' : 'warning',
            "{$succeeded} order(s) cancelled, ".count($errors).' failed. '.implode(' | ', array_slice($errors, 0, 5))
        );
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
