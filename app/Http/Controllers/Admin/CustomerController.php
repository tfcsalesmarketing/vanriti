<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount(['orders' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])])
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])], 'grand_total');

        if ($q = $request->input('q')) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user): View
    {
        $user->load(['orders', 'addresses', 'reviews']);

        $orders = $user->orders()->with(['items', 'user'])->orderByDesc('created_at')->get();
        $addresses = $user->addresses()->get();
        $reviews = $user->reviews()->with(['product', 'orderItem'])->latest()->get();

        $stats = [
            'total_orders' => $orders->whereNotIn('order_status', ['cancelled'])->count(),
            'total_spent' => $orders->whereNotIn('order_status', ['cancelled'])->sum('grand_total'),
            'last_order_date' => $orders->first()?->created_at,
        ];

        return view('admin.customers.show', compact('user', 'orders', 'addresses', 'reviews', 'stats'));
    }

    public function toggleStatus(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        $this->logger->customerChanged(auth('admin')->user(), $user, $user->status);

        return redirect()->back()->with('success', 'Customer status updated.');
    }
}
