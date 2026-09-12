<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\ReturnItem;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(protected OrderService $orderService, protected RefundService $refundService)
    {
    }

    public function dashboard(): View
    {
        $user = auth('web')->user();
        $stats = [
            'orders' => $user->orders()->count(),
            'spent' => (float) $user->orders()->whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total'),
            'delivered' => $user->orders()->where('order_status', 'delivered')->count(),
            'pending_returns' => $user->returnRequests()->whereIn('status', ['requested', 'under_review', 'approved', 'pickup_scheduled'])->count(),
        ];
        $recentOrders = $user->orders()->with('items')->latest()->limit(5)->get();

        return view('storefront.account.dashboard', compact('stats', 'recentOrders'));
    }

    public function orders(): View
    {
        $orders = auth('web')->user()
            ->orders()
            ->with(['items', 'payments'])
            ->latest()
            ->paginate(10);

        return view('storefront.account.orders', compact('orders'));
    }

    public function orderShow(Order $order): View
    {
        abort_unless($order->user_id === auth('web')->id(), 403);

        $order->load(['items', 'payments', 'statusHistories', 'shipments.trackingEvents', 'returnRequests.items']);

        return view('storefront.account.order-detail', compact('order'));
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth('web')->id(), 403);

        try {
            $this->orderService->cancelOrder($order, $request->input('reason'));
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Your order has been cancelled and the amount will be refunded if already paid.');
    }

    public function createReturn(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth('web')->id(), 403);

        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'exists:order_items,id'],
        ]);

        if (! $order->isReturnable()) {
            return back()->with('error', 'This order is not eligible for a return.');
        }

        $user = auth('web')->user();

        $orderItems = $order->items()->whereIn('id', $request->items)->get();
        if ($orderItems->isEmpty()) {
            return back()->with('error', 'Please select at least one item to return.');
        }

        try {
            $returnRequest = DB::transaction(function () use ($user, $order, $orderItems, $request) {
                $return = ReturnRequest::create([
                    'return_number' => 'RTR-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'reason' => $request->input('reason'),
                    'description' => $request->input('description'),
                    'status' => 'requested',
                    'requested_at' => now(),
                ]);

                $refundable = 0.0;
                foreach ($orderItems as $item) {
                    $refundable += (float) $item->total_price;
                    ReturnItem::create([
                        'return_request_id' => $return->id,
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'refund_amount' => $item->total_price,
                    ]);
                }

                $this->refundService->createFromReturn($return, $user, $refundable, 'partial', 'Return request submitted.');

                return $return;
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to submit return request: '.$e->getMessage());
        }

        return redirect()->route('account.order', $order)
            ->with('success', 'Return request '.$returnRequest->return_number.' submitted. Our team will review it shortly.');
    }

    public function submitReview(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === auth('web')->id(), 403);

        $request->validate([
            'order_item_id' => ['required', 'exists:order_items,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        $item = $order->items()->findOrFail($request->input('order_item_id'));

        try {
            $review = Review::create([
                'product_id' => $item->product_id,
                'user_id' => auth('web')->id(),
                'order_item_id' => $item->id,
                'rating' => $request->input('rating'),
                'title' => $request->input('title'),
                'comment' => $request->input('comment'),
                'status' => 'pending',
                'is_verified_purchase' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // SQLSTATE 23000 (integrity constraint) covers unique violations on
            // both MySQL (1062) and SQLite (19) without being driver-specific.
            if (($e->errorInfo[0] ?? null) === '23000'
                || ($e->errorInfo[1] ?? null) === 1062
                || ($e->errorInfo[1] ?? null) === 19) {
                return back()->with('error', 'You have already reviewed this item.');
            }

            throw $e;
        }

        return back()->with('success', 'Thank you! Your review has been submitted for approval.');
    }

    public function addresses(): View
    {
        $addresses = auth('web')->user()->addresses()->latest()->get();

        return view('storefront.account.addresses', compact('addresses'));
    }

    public function createAddress(): View
    {
        $address = new \App\Models\Address();

        return view('storefront.account.address-create', compact('address'));
    }

    public function storeAddress(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateAddress($request);
        $user = auth('web')->user();

        $isFirst = ! $user->addresses()->exists();

        $address = $user->addresses()->create($data);

        if ($isFirst || $request->boolean('is_default')) {
            $this->setDefault($address);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'address' => $address]);
        }

        return redirect()->route('account.addresses')->with('success', 'Address added.');
    }

    public function editAddress(Address $address): View
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        return view('storefront.account.address-edit', compact('address'));
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse|JsonResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        $address->update($this->validateAddress($request));

        if ($request->boolean('is_default')) {
            $this->setDefault($address);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'address' => $address]);
        }

        return redirect()->route('account.addresses')->with('success', 'Address updated.');
    }

    public function destroyAddress(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        $address->delete();

        return redirect()->route('account.addresses')->with('success', 'Address removed.');
    }

    public function makeDefaultAddress(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth('web')->id(), 403);

        $this->setDefault($address);

        return redirect()->route('account.addresses')->with('success', 'Default address updated.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth('web')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\- ]+$/'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:15', 'regex:/^[0-9+\- ]+$/'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'pincode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'country' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:home,office,other'],
        ], [
            'pincode.regex' => 'Please enter a valid 6-digit pincode.',
            'mobile.regex' => 'Please enter a valid mobile number.',
        ]);
    }

    protected function setDefault(Address $address): void
    {
        auth('web')->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}