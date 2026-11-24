<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\GstService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\PincodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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
        'ready_to_ship' => 'Ready to Ship',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'hold' => 'On Hold / Unpaid',
        'cancelled' => 'Cancelled',
    ];

    public function __construct(
        protected OrderService $orderService,
        protected ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $activeTab = (string) $request->input('tab', 'pending');
        if ($activeTab === 'assign_courier') {
            $activeTab = 'ready_to_ship';
        }

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

        $couriers = \App\Models\Shipment::whereNotNull('courier')
            ->distinct()
            ->pluck('courier')
            ->filter()
            ->values();

        $orders = Order::query()
            ->with(['user', 'items', 'payments', 'shipments'])
            ->withCount('items')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->input('q'));
                $cleanPhone = preg_replace('/\D/', '', $q);

                $query->where(function ($query) use ($q, $cleanPhone) {
                    $query->where('order_number', 'like', "%{$q}%")
                        ->orWhere('shipping_name', 'like', "%{$q}%")
                        ->orWhere('billing_name', 'like', "%{$q}%")
                        ->orWhere('guest_email', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($query) use ($q) {
                            $query->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        })
                        ->orWhereHas('shipments', function ($query) use ($q) {
                            $query->where('awb_number', 'like', "%{$q}%")
                                ->orWhere('tracking_number', 'like', "%{$q}%")
                                ->orWhere('shipmojo_order_id', 'like', "%{$q}%");
                        });

                    if ($cleanPhone !== '') {
                        $query->orWhere('shipping_mobile', 'like', "%{$cleanPhone}%")
                            ->orWhere('billing_mobile', 'like', "%{$cleanPhone}%")
                            ->orWhere('guest_mobile', 'like', "%{$cleanPhone}%")
                            ->orWhereHas('user', function ($query) use ($cleanPhone) {
                                $query->where('phone', 'like', "%{$cleanPhone}%");
                            });
                    }
                });
            })
            ->when($request->filled('payment_method'), function ($query) use ($request) {
                $query->where('payment_method', $request->input('payment_method'));
            })
            ->when($request->filled('courier'), function ($query) use ($request) {
                $courier = $request->input('courier');
                $query->whereHas('shipments', function ($sq) use ($courier) {
                    $sq->where('courier', 'like', "%{$courier}%");
                });
            })
            ->when($request->filled('order_status'), function ($query) use ($request) {
                $query->where('order_status', $request->input('order_status'));
            })
            ->when($request->filled('payment_status'), function ($query) use ($request) {
                $query->where('payment_status', $request->input('payment_status'));
            })
            ->when($request->filled('date_preset'), function ($query) use ($request) {
                $preset = $request->input('date_preset');
                match ($preset) {
                    'today' => $query->whereDate('created_at', today()),
                    'yesterday' => $query->whereDate('created_at', today()->subDay()),
                    'last_7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                    'this_month' => $query->where('created_at', '>=', now()->startOfMonth()),
                    default => null,
                };
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
            'couriers' => $couriers,
        ]);
    }

    /**
     * Scope the orders query to one pipeline tab.
     */
    protected function applyTabScope(Builder $query, string $tab): Builder
    {
        switch ($tab) {
            case 'pending':
                // Successfully placed orders waiting to be pushed to ShipMojo
                $query->whereNotIn('order_status', ['cancelled', 'failed', 'delivered'])
                    ->where(function (Builder $q) {
                        $q->where('payment_status', 'paid')
                            ->orWhere(function (Builder $sub) {
                                $sub->where('payment_method', 'cod')
                                    ->whereNotIn('payment_status', ['failed', 'cancelled']);
                            });
                    })
                    ->whereDoesntHave('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at');
                    });
                break;

            case 'hold':
                // Failed, dropped, or unconfirmed online payments
                $query->whereNotIn('order_status', ['cancelled', 'delivered'])
                    ->where(function (Builder $q) {
                        $q->whereIn('payment_status', ['failed', 'cancelled'])
                            ->orWhere('order_status', 'failed')
                            ->orWhere(function (Builder $sub) {
                                $sub->where('payment_method', '!=', 'cod')
                                    ->whereIn('payment_status', ['pending', 'processing']);
                            });
                    })
                    ->whereDoesntHave('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at');
                    });
                break;

            case 'ready_to_ship':
                // All orders pushed to ShipMojo, awaiting courier assignment / pickup / label in ShipMojo
                $query->whereNotIn('order_status', ['shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled'])
                    ->whereHas('shipments', function (Builder $q) {
                        $q->whereNotNull('shipmojo_pushed_at')
                            ->whereNotIn('status', ['shipped', 'out_for_delivery', 'delivered', 'returning', 'returned', 'failed', 'cancelled']);
                    });
                break;

            case 'shipped':
                // In transit or out for delivery
                $query->where(function (Builder $q) {
                    $q->whereIn('order_status', ['shipped', 'out_for_delivery'])
                        ->orWhereHas('shipments', function (Builder $sq) {
                            $sq->whereIn('status', ['shipped', 'out_for_delivery']);
                        });
                })->whereNotIn('order_status', ['cancelled', 'delivered']);
                break;

            case 'delivered':
                $query->where(function (Builder $q) {
                    $q->where('order_status', 'delivered')
                        ->orWhereHas('shipments', function (Builder $sq) {
                            $sq->whereIn('status', ['delivered']);
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
        }

        return $query;
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'user', 'payments', 'statusHistories', 'shipments', 'refunds']);

        $phone = $this->resolveOrderPhone($order);
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $conversation = $cleanPhone
            ? \App\Models\WhatsAppConversation::where('phone', $cleanPhone)->first()
            : null;
        $approvedTemplates = \App\Models\WhatsAppTemplate::where('status', 'APPROVED')->orderBy('name')->get();

        return view('admin.orders.show', compact('order', 'conversation', 'approvedTemplates', 'phone', 'cleanPhone'));
    }

    public function edit(Order $order): View
    {
        $order->load(['items', 'user', 'payments', 'statusHistories', 'shipments', 'refunds']);

        return view('admin.orders.edit', [
            'order' => $order,
            'orderStatuses' => $this->orderStatuses,
            'paymentStatuses' => $this->paymentStatuses,
            'paymentMethods' => [
                'cod' => 'Cash on Delivery (COD)',
                'prepaid' => 'Prepaid / UPI',
                'bank_transfer' => 'Bank Transfer / NEFT / IMPS',
                'razorpay' => 'Razorpay / Online',
            ],
        ]);
    }

    /**
     * Update payment method, payment status, and transaction reference for an existing order.
     */
    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $admin = auth('admin')->user();
        if (! $admin?->hasPermission('manage-orders') && ! $admin?->hasPermission('manage-payments')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cod,prepaid,upi,bank_transfer,razorpay',
            'payment_status' => 'required|in:paid,pending,failed,cancelled',
            'payment_reference' => 'nullable|string|max:100',
            'amount_paid' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->orderService->updatePaymentDetails($order, $validated, $admin);

            $this->logger->log(
                action: 'payment_updated',
                entity: $order,
                description: "Payment details updated for order #{$order->order_number} by {$admin->name}.",
                newValues: $validated,
                actor: $admin
            );

            return redirect()->back()->with('success', "Payment details for order #{$order->order_number} updated successfully!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to update payment details: '.$e->getMessage());
        }
    }

    /**
     * Update items in an existing order (add new, remove, change quantities or unit prices).
     */
    public function updateItems(Request $request, Order $order): RedirectResponse
    {
        $admin = auth('admin')->user();
        if (! $admin?->hasPermission('manage-orders')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.mrp' => 'nullable|numeric|min:0',
            'adjust_stock' => 'nullable|boolean',
        ]);

        try {
            $adjustStock = $request->boolean('adjust_stock', true);
            $this->orderService->updateOrderItems($order, $validated['items'], $adjustStock, $admin);

            $this->logger->log(
                action: 'order_items_updated',
                entity: $order,
                description: "Order items modified for order #{$order->order_number} by {$admin->name}.",
                actor: $admin
            );

            return redirect()->back()->with('success', "Order items for #{$order->order_number} updated successfully! Financials and stock have been recalculated.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to update order items: '.$e->getMessage());
        }
    }

    /**
     * Remove a single item from an existing order.
     */
    public function removeItem(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $admin = auth('admin')->user();
        if (! $admin?->hasPermission('manage-orders')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        if ($item->order_id !== $order->id) {
            abort(404, 'Item does not belong to this order.');
        }

        if ($order->items()->count() <= 1) {
            return redirect()->back()->with('error', 'Cannot remove the last item from an order. If you want to cancel the entire order, please use Cancel Order instead.');
        }

        try {
            $adjustStock = $request->boolean('adjust_stock', true);
            $remainingItems = $order->items()
                ->where('id', '!=', $item->id)
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'product_variant_id' => $i->product_variant_id,
                    'quantity' => (int) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'mrp' => (float) $i->mrp,
                ])
                ->all();

            $this->orderService->updateOrderItems($order, $remainingItems, $adjustStock, $admin);

            return redirect()->back()->with('success', "Item {$item->product_name} removed from order #{$order->order_number}.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to remove item: '.$e->getMessage());
        }
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
            if ($newStatus === 'cancelled') {
                $this->orderService->cancelOrder($order, $data['description'] ?? 'Cancelled by admin');
            } else {
                $this->orderService->updateOrderStatus($order, $newStatus, $data['description'] ?? null);
            }

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
        $admin = auth('admin')->user();

        if (! $admin?->hasPermission('manage-payments')) {
            abort(403, 'You do not have permission to perform this action.');
        }

        $data = $request->validate([
            'payment_status' => 'required|in:paid,failed,cancelled',
            'reason' => 'required|string|max:1000',
        ]);

        $newStatus = $data['payment_status'];
        $oldStatus = $order->payment_status;
        $reason = trim($data['reason']);

        $order->update([
            'payment_status' => $newStatus,
            'internal_notes' => ($order->internal_notes ? $order->internal_notes."\n" : '')
                .'['.now()->format('d M Y H:i').'] Payment '.$oldStatus.' → '.$newStatus
                .' — '.$reason,
        ]);

        $payment = $order->payments()->latest()->first();
        $amount = (float) $order->amount_due;

        if ($newStatus === 'paid') {
            if ($payment) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? now(),
                    'amount' => $amount > 0 ? $amount : $order->grand_total,
                ]);
            } else {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => 'manual',
                    'amount' => $amount > 0 ? $amount : $order->grand_total,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            $totalPaid = (float) $order->payments()->where('status', 'paid')->sum('amount');
            $outstanding = (float) $order->grand_total - $totalPaid;

            $order->update([
                'amount_paid' => $totalPaid,
                'amount_due' => max(0, $outstanding),
            ]);
        } elseif ($payment) {
            $payment->update([
                'status' => $newStatus,
                'failed_at' => now(),
            ]);
        }

        $this->logger->log(
            'payment_status_changed',
            $order,
            'Payment status changed from '.$oldStatus.' to '.$newStatus.'.',
            ['payment_status' => $oldStatus],
            ['payment_status' => $newStatus, 'reason' => $data['reason'] ?? null],
            $admin,
        );

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

    public function sendRecoveryWhatsApp(Order $order, \App\Services\WhatsAppOtpService $whatsapp): RedirectResponse
    {
        $phone = trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: $order->user?->phone)));

        if ($phone === '') {
            return back()->with('error', 'No mobile number found for this customer.');
        }

        $order->loadMissing('items.product.images');
        $firstItem = $order->items->first();
        $product = $firstItem?->product;
        $productName = $firstItem?->product_name ?: ($product?->name ?: 'your item');
        if ($order->items->count() > 1) {
            $productName .= ' (+' . ($order->items->count() - 1) . ' more)';
        }

        $whatsappImage = $product?->getWhatsAppImage()?->image_path;
        $rawImage = $whatsappImage ?: ($firstItem?->image ?: $product?->getPrimaryImage()?->image_path);
        if ($product && (empty($rawImage) || str_contains(strtolower($rawImage), '.webp'))) {
            $imageUrl = route('media.whatsapp.product', $product);
        } else {
            $imageUrl = $rawImage ? image_url($rawImage) : null;
        }

        $name = trim((string) ($order->user?->name ?: $order->billing_name)) ?: 'Customer';
        $total = (string) number_format((float) $order->grand_total, 2, '.', '');
        $recoveryUrl = route('account.order', $order);

        $result = $whatsapp->sendAbandonedCheckoutRecovery(
            $phone,
            $name,
            $order->order_number,
            $total,
            $recoveryUrl,
            $productName,
            $imageUrl
        );

        if (($result['result'] ?? '0') === '1') {
            $order->update(['whatsapp_recovery_sent_at' => now()]);
            $adminName = auth('admin')->user()?->name ?? 'Admin';
            $note = '[' . now()->format('d-M-Y H:i') . "] WhatsApp checkout recovery template sent via Meta Cloud API by {$adminName}";
            $order->update([
                'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
            ]);

            return back()->with('success', "WhatsApp recovery template sent successfully via Meta Cloud API to {$phone}!");
        }

        return back()->with('error', 'Meta WhatsApp API delivery failed: ' . ($result['message'] ?? 'Unknown error'));
    }

    public function sendWhatsAppAlert(
        Request $request,
        Order $order,
        \App\Services\WhatsAppChatService $chatService
    ): RedirectResponse {
        $phone = $this->resolveOrderPhone($order);
        if ($phone === '') {
            return back()->with('error', 'No customer mobile number associated with this order.');
        }

        $data = $request->validate([
            'send_type' => 'required|in:template,custom_text',
            'template_name' => 'nullable|string|max:120',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $name = trim((string) ($order->shipping_name ?: $order->billing_name)) ?: 'Customer';
        $firstName = explode(' ', $name)[0];
        $order->loadMissing('shipments');
        $shipment = $order->shipments()->latest()->first();
        $courier = $shipment?->courier ?: 'ShipMojo Express';
        $trackingNumber = $shipment?->awb_number ?: ($shipment?->tracking_number ?: $order->order_number);
        $orderUrl = route('account.order', $order);

        $conversation = $chatService->getOrCreateConversation($phone, $name, null, 'vanriti');
        $admin = auth('admin')->user();

        if ($data['send_type'] === 'custom_text') {
            $text = trim((string) ($data['custom_message'] ?? ''));
            if ($text === '') {
                return back()->with('error', 'Please enter a message to send.');
            }

            if (! $conversation->isWindowOpen()) {
                return back()->with('error', 'The 24-hour customer session window is closed. Please select an approved Meta Template to initiate or contact the customer.');
            }

            $res = $chatService->sendTextMessage($conversation, $text, $admin);

            if ($res['success']) {
                $note = '[' . now()->format('d M Y H:i') . "] WhatsApp direct message sent by {$admin?->name}: \"{$text}\"";
                $order->update([
                    'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
                ]);

                return back()->with('success', "WhatsApp message sent successfully to {$phone}!");
            }

            return back()->with('error', 'Failed to send WhatsApp message: ' . ($res['error'] ?? 'API error'));
        }

        // Template Send
        $tpl = $data['template_name'] ?? '';
        if (empty($tpl)) {
            return back()->with('error', 'Please select a Meta Template.');
        }

        $params = match ($tpl) {
            'order_confirmation' => [$firstName, $order->order_number, now()->addDays(5)->toDateString()],
            'order_dispatched' => [$firstName, $order->order_number, $courier, $trackingNumber],
            'order_out_for_delivery' => [$firstName, $order->order_number, $courier],
            'order_delivered' => [$firstName, $order->order_number],
            'checkout_recovery' => [$firstName, (string) number_format((float) $order->grand_total, 2, '.', ''), $order->order_number],
            default => [$firstName, $order->order_number],
        };

        $buttonSuffix = match ($tpl) {
            'order_dispatched', 'order_out_for_delivery' => 'track?order=' . rawurlencode($order->order_number),
            'order_confirmation' => 'account/orders/' . rawurlencode($order->order_number),
            'order_delivered' => 'track?order=' . rawurlencode($order->order_number),
            default => null,
        };

        $res = $chatService->sendTemplateMessage($conversation, $tpl, 'en', $params, null, $admin, $buttonSuffix);

        if ($res['success']) {
            $note = '[' . now()->format('d M Y H:i') . "] WhatsApp template '{$tpl}' sent via Meta Cloud API by {$admin?->name}";
            $order->update([
                'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . $note),
            ]);

            return back()->with('success', "WhatsApp template '{$tpl}' sent successfully to {$phone}!");
        }

        return back()->with('error', 'Meta template send failed: ' . ($res['error'] ?? 'API error'));
    }

    protected function resolveOrderPhone(Order $order): string
    {
        $candidates = [
            $order->shipping_mobile,
            $order->billing_mobile,
            $order->user?->phone,
            $order->guest_mobile,
        ];

        foreach ($candidates as $candidate) {
            $phone = trim((string) $candidate);
            if ($phone !== '') {
                return $phone;
            }
        }

        return '';
    }

    public function invoice(Request $request, Order $order, InvoiceService $invoiceService): View|Response
    {
        $order->loadMissing(['items.product', 'items.variant', 'user']);

        if ($request->query('format') === 'html') {
            return view('invoices.gst-tax-invoice', compact('order'));
        }

        return $invoiceService->getPdfResponse(
            $order,
            $request->boolean('download'),
            $request->boolean('force') || $request->boolean('refresh')
        );
    }

    public function manifest(Request $request): View
    {
        $orderIds = $request->input('order_ids');
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }

        $query = Order::query()->with(['items.product', 'shipments', 'user']);

        if (! empty($orderIds) && is_array($orderIds)) {
            $query->whereIn('id', array_filter($orderIds));
        } else {
            $query->whereHas('shipments', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNotNull('awb_number')
                        ->orWhereNotNull('tracking_number');
                });
            })->latest()->take(50);
        }

        $orders = $query->get();

        return view('admin.orders.manifest', compact('orders'));
    }

    public function confirmCod(Order $order): RedirectResponse
    {
        if ($order->payment_method !== 'cod') {
            return back()->with('error', 'This order is not a Cash on Delivery order.');
        }

        $oldStatus = $order->order_status;
        $order->update([
            'order_status' => 'processing',
            'internal_notes' => trim(($order->internal_notes ? $order->internal_notes . "\n" : '') . '[' . now()->format('d M Y H:i') . '] COD confirmed manually by admin ' . auth('admin')->user()?->name),
        ]);

        $this->logger->orderStatusChanged(
            auth('admin')->user(),
            $order,
            $oldStatus,
            'processing',
            'COD confirmed manually by admin.'
        );

        if ((bool) setting('shipmojo_auto_push', false)) {
            \App\Jobs\PushOrderToShipMojo::dispatch($order);
        }

        return back()->with('success', "Order #{$order->order_number} COD confirmed and moved to processing!");
    }

    public function resendCodPrompt(Order $order): RedirectResponse
    {
        if ($order->payment_method !== 'cod') {
            return back()->with('error', 'This order is not a Cash on Delivery order.');
        }

        $res = app(\App\Services\CodConfirmationService::class)->dispatchPrompt($order);

        if ($res['success'] ?? false) {
            return back()->with('success', 'WhatsApp 1-Tap COD confirmation prompt resent successfully to customer!');
        }

        return back()->with('error', 'Failed to dispatch WhatsApp prompt: ' . ($res['message'] ?? ($res['error'] ?? 'API error')));
    }

    /**
     * Show the form to create a new manual order.
     */
    public function create(Request $request): View
    {
        $selectedUser = null;
        if ($request->filled('user_id')) {
            $selectedUser = User::with('addresses')->find($request->input('user_id'));
        }

        $states = GstService::STATES;
        $coupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('code')
            ->get();

        return view('admin.orders.create', [
            'selectedUser' => $selectedUser,
            'states' => $states,
            'coupons' => $coupons,
            'orderStatuses' => ['confirmed' => 'Confirmed', 'pending' => 'Pending', 'processing' => 'Processing'],
            'paymentStatuses' => ['paid' => 'Paid (Payment Collected)', 'pending' => 'Pending (Collect on Delivery / Unsettled)'],
            'paymentMethods' => [
                'cod' => 'Cash on Delivery (COD)',
                'prepaid' => 'Prepaid / UPI',
                'bank_transfer' => 'Bank Transfer / NEFT / IMPS',
                'razorpay' => 'Razorpay / Online',
            ],
        ]);
    }

    /**
     * Search customers for autocomplete in manual order creation.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q'));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $cleanPhone = preg_replace('/\D/', '', $q);

        $users = User::query()
            ->where(function ($query) use ($q, $cleanPhone) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
                if (strlen($cleanPhone) >= 3) {
                    $query->orWhere('phone', 'like', "%{$cleanPhone}%");
                }
            })
            ->with(['addresses' => function ($sq) {
                $sq->orderByDesc('is_default')->orderByDesc('id');
            }])
            ->limit(15)
            ->get();

        return response()->json($users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'orders_count' => $user->orders()->count(),
            'addresses' => $user->addresses->map(fn ($addr) => [
                'id' => $addr->id,
                'full_name' => $addr->full_name,
                'mobile' => $addr->mobile,
                'address_line1' => $addr->address_line1,
                'address_line2' => $addr->address_line2,
                'landmark' => $addr->landmark,
                'city' => $addr->city,
                'state' => $addr->state,
                'pincode' => $addr->pincode,
                'country' => $addr->country,
                'is_default' => $addr->is_default,
                'full_address' => $addr->full_address,
            ]),
        ]));
    }

    /**
     * Search products & variants for manual order item addition.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q'));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhereHas('variants', function ($vq) use ($q) {
                        $vq->where('name', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%");
                    });
            })
            ->with([
                'variants' => fn ($vq) => $vq->where('status', 'active')->orderBy('sort_order'),
                'images',
            ])
            ->limit(20)
            ->get();

        return response()->json($products->map(function (Product $product) {
            $primaryImage = $product->getPrimaryImage()?->image_path;
            $imageUrl = $primaryImage ? image_url($primaryImage) : asset('images/placeholder.svg');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'mrp' => (float) $product->mrp,
                'selling_price' => (float) $product->selling_price,
                'stock' => (int) $product->stock,
                'gst_rate' => (float) $product->gst_rate,
                'image_url' => $imageUrl,
                'has_variants' => $product->variants->isNotEmpty(),
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'mrp' => (float) $variant->mrp,
                    'selling_price' => (float) $variant->selling_price,
                    'stock' => (int) $variant->stock,
                    'gst_rate' => (float) ($variant->gst_rate ?: $product->gst_rate),
                ]),
            ];
        }));
    }

    /**
     * Fast pincode lookup to auto-populate City and State.
     */
    public function lookupPincode(Request $request): JsonResponse
    {
        $pincode = trim((string) $request->input('pincode'));
        if (strlen($pincode) !== 6 || ! ctype_digit($pincode)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid 6-digit postal pincode.',
            ], 422);
        }

        $pincodeService = app(PincodeService::class);
        $location = $pincodeService->resolveLocation($pincode);

        return response()->json([
            'success' => true,
            'pincode' => $pincode,
            'city' => $location['city'] ?? '',
            'state' => $location['state'] ?? '',
        ]);
    }

    /**
     * Store a manually created order in the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:new,existing',
            'user_id' => 'nullable|required_if:customer_type,existing|exists:users,id',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+ -]{10,15}$/'],
            'customer_email' => 'nullable|email|max:150',
            'save_customer_address' => 'nullable|boolean',

            'shipping_name' => 'required|string|max:150',
            'shipping_mobile' => ['required', 'string', 'regex:/^[0-9+ -]{10,15}$/'],
            'shipping_address_line1' => 'required|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_landmark' => 'nullable|string|max:150',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_pincode' => ['required', 'string', 'size:6', 'regex:/^[1-9][0-9]{5}$/'],
            'shipping_country' => 'nullable|string|max:100',

            'is_billing_same' => 'nullable|boolean',
            'billing_name' => 'nullable|required_if:is_billing_same,0|string|max:150',
            'billing_mobile' => 'nullable|required_if:is_billing_same,0|string|max:15',
            'billing_address_line1' => 'nullable|required_if:is_billing_same,0|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_landmark' => 'nullable|string|max:150',
            'billing_city' => 'nullable|required_if:is_billing_same,0|string|max:100',
            'billing_state' => 'nullable|required_if:is_billing_same,0|string|max:100',
            'billing_pincode' => 'nullable|required_if:is_billing_same,0|string|size:6',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.mrp' => 'nullable|numeric|min:0',

            'discount_type' => 'nullable|in:none,fixed,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:50',
            'shipping_charge' => 'nullable|numeric|min:0',

            'payment_method' => 'required|in:cod,prepaid,upi,bank_transfer,razorpay',
            'payment_status' => 'required|in:pending,paid',
            'payment_reference' => 'nullable|string|max:100',
            'order_status' => 'required|in:pending,confirmed,processing,packed',
            'notes' => 'nullable|string|max:1000',

            'reduce_stock' => 'nullable|boolean',
            'send_whatsapp' => 'nullable|boolean',
            'push_shipmojo' => 'nullable|boolean',
        ]);

        $admin = auth('admin')->user();

        try {
            $order = $this->orderService->createManualOrder($validated, $admin);

            $this->logger->log(
                action: 'order_created',
                entity: $order,
                description: "Manual order #{$order->order_number} created by admin ".($admin?->name ?? 'Admin').'.',
                newValues: [
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                ],
                actor: $admin
            );

            // Trigger WhatsApp confirmation if requested
            if ($request->boolean('send_whatsapp', true)) {
                try {
                    \App\Jobs\SendWhatsAppOrderConfirmation::dispatch($order);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to dispatch WhatsApp confirmation for manual order #{$order->order_number}: ".$e->getMessage());
                }
            }

            // Push to ShipMojo if requested
            if ($request->boolean('push_shipmojo', false)) {
                try {
                    \App\Jobs\PushOrderToShipMojo::dispatch($order);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to push manual order #{$order->order_number} to ShipMojo: ".$e->getMessage());
                }
            }

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Manual Order #{$order->order_number} created successfully!");
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error creating manual order: '.$e->getMessage());
        }
    }
}
