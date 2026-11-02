<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppTemplate;
use App\Services\ActivityLogger;
use App\Services\WhatsAppChatService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected ActivityLogger $logger,
        protected WhatsAppChatService $chatService,
        protected WhatsAppCloudApiService $apiService
    ) {}

    public function index(Request $request): View
    {
        $baseQuery = User::vanritiCustomers();

        // 1. Calculate KPI summary metrics for VANRITI retail storefront customers only
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active_shoppers' => (clone $baseQuery)->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']))->count(),
            'vip' => (clone $baseQuery)->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']))
                ->withCount(['orders' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])])
                ->withSum(['orders as total_spent' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])], 'grand_total')
                ->get()
                ->filter(fn ($u) => ($u->total_spent ?? 0) >= 2500 || ($u->orders_count ?? 0) >= 3)
                ->count(),
            'whatsapp_connected' => (clone $baseQuery)->whereNotNull('phone')->where('phone', '!=', '')->count(),
            'direct_sellers_count' => User::directSellers()->count(),
        ];

        // 2. Query builder strictly scoped to VANRITI retail customers (excluding TFC direct sellers)
        $query = (clone $baseQuery)
            ->withCount(['orders' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])])
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])], 'grand_total')
            ->with(['whatsappConversation']);

        // Search
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if (in_array($status, ['active', 'inactive'])) {
                $query->where('status', $status);
            }
        }

        // Segment Filter
        if ($segment = $request->input('segment')) {
            if ($segment === 'vip') {
                $query->where(function ($sub) {
                    $sub->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']), '>=', 3)
                        ->orWhere(function ($orSub) {
                            $orSub->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']), '>=', 1)
                                ->whereRaw('(SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE orders.user_id = users.id AND orders.order_status NOT IN ("cancelled")) >= 2500');
                        });
                });
            } elseif ($segment === 'repeat') {
                $query->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']), '>=', 2)
                    ->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']), '<', 3)
                    ->whereRaw('(SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE orders.user_id = users.id AND orders.order_status NOT IN ("cancelled")) < 2500');
            } elseif ($segment === 'first_time') {
                $query->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']), '=', 1);
            } elseif ($segment === 'prospect') {
                $query->whereDoesntHave('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']));
            }
        }

        // WhatsApp Filter
        if ($request->boolean('has_whatsapp')) {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        if ($sort === 'clv_desc') {
            $query->orderByDesc('total_spent')->orderByDesc('created_at');
        } elseif ($sort === 'orders_desc') {
            $query->orderByDesc('orders_count')->orderByDesc('created_at');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name');
        } else {
            $query->orderByDesc('created_at');
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers', 'kpis'));
    }

    /**
     * TFC Sales & Marketing Direct Sellers Customer Directory.
     * Strictly scopes records to users associated with direct_selling_members.
     */
    public function tfcIndex(Request $request): View
    {
        $baseQuery = User::directSellers();

        // 1. Calculate KPI summary metrics for Direct Sellers only
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active_members' => (clone $baseQuery)->whereHas('directSellingMember', fn ($q) => $q->where('membership_status', 'active'))->count(),
            'ordering_sellers' => (clone $baseQuery)->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']))->count(),
            'whatsapp_connected' => (clone $baseQuery)->whereNotNull('phone')->where('phone', '!=', '')->count(),
            'vanriti_customers_count' => User::vanritiCustomers()->count(),
        ];

        // 2. Query builder strictly scoped to Direct Sellers
        $query = (clone $baseQuery)
            ->with([
                'directSellingMember.sponsor',
                'directSellingMember.placementParent',
                'directSellingMember.kyc',
                'whatsappConversation',
            ])
            ->withCount(['orders' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])])
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereNotIn('order_status', ['cancelled'])], 'grand_total');

        // Search: Name, Email, Phone, or TFC Member ID
        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('directSellingMember', function ($dq) use ($q) {
                        $dq->where('member_id', 'like', "%{$q}%");
                    });
            });
        }

        // Membership Status Filter
        if ($status = $request->input('status')) {
            if (in_array($status, ['active', 'inactive', 'suspended', 'terminated'], true)) {
                $query->whereHas('directSellingMember', fn ($dq) => $dq->where('membership_status', $status));
            }
        }

        // Leg / Placement Position Filter
        if ($leg = $request->input('leg')) {
            if (in_array($leg, ['orange', 'blue'], true)) {
                $query->whereHas('directSellingMember', fn ($dq) => $dq->where('placement_position', $leg));
            }
        }

        // KYC Status Filter
        if ($kycStatus = $request->input('kyc')) {
            if (in_array($kycStatus, ['approved', 'pending', 'rejected', 'not_submitted'], true)) {
                if ($kycStatus === 'not_submitted') {
                    $query->whereDoesntHave('directSellingMember.kyc');
                } else {
                    $query->whereHas('directSellingMember.kyc', fn ($kq) => $kq->where('status', $kycStatus));
                }
            }
        }

        // WhatsApp Filter
        if ($request->boolean('has_whatsapp')) {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        }

        // Orders Activity Filter
        if ($activity = $request->input('activity')) {
            if ($activity === 'with_orders') {
                $query->whereHas('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']));
            } elseif ($activity === 'no_orders') {
                $query->whereDoesntHave('orders', fn ($q) => $q->whereNotIn('order_status', ['cancelled']));
            }
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        if ($sort === 'clv_desc') {
            $query->orderByDesc('total_spent')->orderByDesc('created_at');
        } elseif ($sort === 'orders_desc') {
            $query->orderByDesc('orders_count')->orderByDesc('created_at');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name');
        } else {
            $query->orderByDesc('created_at');
        }

        $sellers = $query->paginate(20)->withQueryString();

        return view('admin.tfc.customers.index', compact('sellers', 'kpis'));
    }

    public function show(User $user): View
    {
        $user->load([
            'orders' => fn ($q) => $q->with(['items.product', 'payment'])->orderByDesc('created_at'),
            'addresses',
            'reviews.product',
            'activeCart.items.product',
            'returnRequests',
            'directSellingMember.sponsor',
            'directSellingMember.placementParent',
            'directSellingMember.kyc',
        ]);

        $orders = $user->orders;
        $addresses = $user->addresses;
        $reviews = $user->reviews;
        $returnRequests = $user->returnRequests;

        $validOrders = $orders->whereNotIn('order_status', ['cancelled']);
        $totalOrdersCount = $validOrders->count();
        $totalSpent = (float) $validOrders->sum('grand_total');
        $aov = $totalOrdersCount > 0 ? round($totalSpent / $totalOrdersCount, 2) : 0;
        $deliveredOrdersCount = $orders->where('order_status', 'delivered')->count();
        $cancelledOrdersCount = $orders->where('order_status', 'cancelled')->count();

        // WhatsApp conversation
        $conversation = null;
        if ($user->phone) {
            $cleanPhone = $user->getCleanPhone();
            $conversation = WhatsAppConversation::with(['messages' => fn ($q) => $q->latest()->take(10)])
                ->where('user_id', $user->id)
                ->orWhere('phone', $cleanPhone)
                ->first();
        }

        $segment = $user->getCustomerSegment();
        $lastActive = $user->getLastActiveAt();

        // Approved templates for quick WhatsApp sending
        $approvedTemplates = WhatsAppTemplate::approved()->orderBy('name')->get();

        $stats = [
            'total_orders' => $totalOrdersCount,
            'total_spent' => $totalSpent,
            'average_order_value' => $aov,
            'delivered_orders' => $deliveredOrdersCount,
            'cancelled_orders' => $cancelledOrdersCount,
            'return_requests' => $returnRequests->count(),
            'last_order_date' => $orders->first()?->created_at,
            'last_active' => $lastActive,
            'segment' => $segment,
            'cart_items_count' => $user->activeCart?->items?->count() ?? 0,
            'cart_total_value' => $user->activeCart?->items?->sum(fn ($i) => $i->quantity * ($i->variant?->selling_price ?? $i->product?->selling_price ?? 0)) ?? 0,
        ];

        return view('admin.customers.show', compact('user', 'orders', 'addresses', 'reviews', 'stats', 'conversation', 'approvedTemplates'));
    }

    public function tfcShow(User $user): View
    {
        return $this->show($user);
    }

    public function toggleStatus(User $user): JsonResponse|RedirectResponse
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $this->logger->customerChanged(auth('admin')->user(), $user, $newStatus);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Customer status updated to ' . ucfirst($newStatus) . '.',
            ]);
        }

        return redirect()->back()->with('success', 'Customer status updated.');
    }

    public function sendWhatsApp(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:template,direct',
            'template_name' => 'required_if:type,template|nullable|string',
            'message' => 'required_if:type,direct|nullable|string',
        ]);

        if (! $user->phone) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Customer does not have a phone number.'], 422);
            }
            return redirect()->back()->with('error', 'Customer does not have a phone number.');
        }

        $cleanPhone = $user->getCleanPhone();
        $conversation = WhatsAppConversation::findOrCreateForPhone($cleanPhone, $user->name);

        if ($request->input('type') === 'direct') {
            if (! $conversation->isWindowOpen()) {
                $err = '24-hour service window is closed. Send an approved Meta template instead.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'error' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            $msg = trim((string) $request->input('message'));
            $res = $this->chatService->sendTextMessage($conversation, $msg, auth('admin')->user());

            if (! ($res['success'] ?? false)) {
                $err = $res['error'] ?? 'Failed to send WhatsApp message.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'error' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            $successMsg = 'WhatsApp message sent successfully!';
        } else {
            $templateName = $request->input('template_name');
            $template = WhatsAppTemplate::where('name', $templateName)->first();
            $lang = $template?->language ?? 'en';

            $res = $this->chatService->sendTemplateMessage(
                conversation: $conversation,
                templateName: $templateName,
                langCode: $lang,
                parameters: [$user->name],
                headerImageUrl: null,
                sender: auth('admin')->user()
            );

            if (! ($res['success'] ?? false)) {
                $err = $res['error'] ?? 'Failed to send WhatsApp template.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'error' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            $successMsg = "WhatsApp template [{$templateName}] sent successfully!";
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->back()->with('success', $successMsg);
    }
}
