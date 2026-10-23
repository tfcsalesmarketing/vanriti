<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\WhatsAppOtpService;

class CartWishlistController extends Controller
{
    public function carts(Request $request): View
    {
        $query = CartItem::query()
            ->with(['cart', 'product', 'variant'])
            ->orderByDesc('created_at');

        if ($productId = $request->integer('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->whereHas('cart', fn ($q) => $q
                ->where('owner_type', User::class)
                ->where('owner_id', $userId));
        }

        if ($sessionId = trim((string) $request->input('session_id'))) {
            $query->whereHas('cart', fn ($q) => $q
                ->where('owner_type', 'guest')
                ->where('session_id', $sessionId));
        }

        if ($ownerType = $request->input('owner_type')) {
            if ($ownerType === 'users') {
                $query->whereHas('cart', fn ($q) => $q->where('owner_type', User::class));
            } elseif ($ownerType === 'guests') {
                $query->whereHas('cart', fn ($q) => $q->where('owner_type', 'guest'));
            }
        }

        if ($dateFrom = $request->date('date_from')) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo = $request->date('date_to')) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
        }

        $cartItems = $query->paginate(25)->withQueryString();

        $topProducts = $this->topCartProducts($request);

        return view('admin.carts.index', [
            'cartItems' => $cartItems,
            'topProducts' => $topProducts,
            'products' => Product::orderBy('name')->limit(500)->get(['id', 'name']),
            'users' => User::orderBy('name')->limit(500)->get(['id', 'name', 'email']),
        ]);
    }

    public function wishlists(Request $request): View
    {
        $query = DB::table('wishlist_items as wi')
            ->join('wishlists as w', 'w.id', '=', 'wi.wishlist_id')
            ->join('products as p', 'p.id', '=', 'wi.product_id')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'w.owner_id')
                    ->where('w.owner_type', '=', User::class);
            })
            ->select([
                'wi.id',
                'wi.wishlist_id',
                'wi.product_id',
                'wi.created_at',
                'w.owner_type',
                'w.owner_id',
                'w.session_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'u.name as user_name',
                'u.email as user_email',
            ])
            ->orderByDesc('wi.created_at');

        if ($productId = $request->integer('product_id')) {
            $query->where('wi.product_id', $productId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('w.owner_type', User::class)
                ->where('w.owner_id', $userId);
        }

        if ($sessionId = trim((string) $request->input('session_id'))) {
            $query->where('w.owner_type', 'guest')
                ->where('w.session_id', $sessionId);
        }

        if ($ownerType = $request->input('owner_type')) {
            if ($ownerType === 'users') {
                $query->where('w.owner_type', User::class);
            } elseif ($ownerType === 'guests') {
                $query->where('w.owner_type', 'guest');
            }
        }

        if ($dateFrom = $request->date('date_from')) {
            $query->where('wi.created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo = $request->date('date_to')) {
            $query->where('wi.created_at', '<=', $dateTo->endOfDay());
        }

        $wishlistItems = $query->paginate(25)->withQueryString();

        $topProducts = $this->topWishlistProducts($request);

        return view('admin.wishlists.index', [
            'wishlistItems' => $wishlistItems,
            'topProducts' => $topProducts,
            'products' => Product::orderBy('name')->limit(500)->get(['id', 'name']),
            'users' => User::orderBy('name')->limit(500)->get(['id', 'name', 'email']),
        ]);
    }

    protected function topCartProducts(Request $request)
    {
        $query = CartItem::query()
            ->join('products', 'products.id', '=', 'cart_items.product_id')
            ->select([
                'cart_items.product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                DB::raw('SUM(cart_items.quantity) as total_quantity'),
                DB::raw('COUNT(*) as line_count'),
                DB::raw('COUNT(DISTINCT cart_items.cart_id) as cart_count'),
            ])
            ->groupBy('cart_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit(10);

        if ($productId = $request->integer('product_id')) {
            $query->where('cart_items.product_id', $productId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->whereExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('carts')
                    ->whereColumn('carts.id', 'cart_items.cart_id')
                    ->where('carts.owner_type', User::class)
                    ->where('carts.owner_id', $userId);
            });
        }

        if ($sessionId = trim((string) $request->input('session_id'))) {
            $query->whereExists(function ($sub) use ($sessionId) {
                $sub->select(DB::raw(1))
                    ->from('carts')
                    ->whereColumn('carts.id', 'cart_items.cart_id')
                    ->where('carts.owner_type', 'guest')
                    ->where('carts.session_id', $sessionId);
            });
        }

        if ($ownerType = $request->input('owner_type')) {
            if ($ownerType === 'users') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('carts')
                        ->whereColumn('carts.id', 'cart_items.cart_id')
                        ->where('carts.owner_type', User::class);
                });
            } elseif ($ownerType === 'guests') {
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('carts')
                        ->whereColumn('carts.id', 'cart_items.cart_id')
                        ->where('carts.owner_type', 'guest');
                });
            }
        }

        if ($dateFrom = $request->date('date_from')) {
            $query->where('cart_items.created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo = $request->date('date_to')) {
            $query->where('cart_items.created_at', '<=', $dateTo->endOfDay());
        }

        return $query->get();
    }

    protected function topWishlistProducts(Request $request)
    {
        $query = DB::table('wishlist_items as wi')
            ->join('wishlists as w', 'w.id', '=', 'wi.wishlist_id')
            ->join('products as p', 'p.id', '=', 'wi.product_id')
            ->select([
                'wi.product_id',
                'p.name as product_name',
                'p.sku as product_sku',
                DB::raw('COUNT(*) as wishlist_count'),
                DB::raw('COUNT(DISTINCT w.id) as wishlist_list_count'),
            ])
            ->groupBy('wi.product_id', 'p.name', 'p.sku')
            ->orderByDesc('wishlist_count')
            ->limit(10);

        if ($productId = $request->integer('product_id')) {
            $query->where('wi.product_id', $productId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('w.owner_type', User::class)
                ->where('w.owner_id', $userId);
        }

        if ($sessionId = trim((string) $request->input('session_id'))) {
            $query->where('w.owner_type', 'guest')
                ->where('w.session_id', $sessionId);
        }

        if ($ownerType = $request->input('owner_type')) {
            if ($ownerType === 'users') {
                $query->where('w.owner_type', User::class);
            } elseif ($ownerType === 'guests') {
                $query->where('w.owner_type', 'guest');
            }
        }

        if ($dateFrom = $request->date('date_from')) {
            $query->where('wi.created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo = $request->date('date_to')) {
            $query->where('wi.created_at', '<=', $dateTo->endOfDay());
        }

        return $query->get();
    }

    public function sendRecoveryWhatsApp(Cart $cart, WhatsAppOtpService $whatsapp): RedirectResponse
    {
        $user = $cart->owner;
        if (! $user instanceof User) {
            return back()->with('error', 'Cannot send WhatsApp message to anonymous guest without registered account.');
        }

        $phone = trim((string) $user->phone);
        if ($phone === '') {
            return back()->with('error', 'Customer has no phone number on their account.');
        }

        $cart->loadMissing('items.product.images', 'items.variant');
        if ($cart->items->isEmpty()) {
            return back()->with('error', 'Cart is empty.');
        }

        $firstItem = $cart->items->first();
        $product = $firstItem?->product;

        $itemDescriptions = [];
        foreach ($cart->items as $ci) {
            $pName = $ci->product?->name ?: 'Item';
            if ($ci->variant) {
                $pName .= ' (' . $ci->variant->name . ')';
            }
            $itemDescriptions[] = "{$pName} (x{$ci->quantity})";
        }
        $productName = \Illuminate\Support\Str::limit(implode(', ', $itemDescriptions), 80);

        $whatsappImage = $product?->getWhatsAppImage()?->image_path;
        $rawImage = $whatsappImage ?: $product?->getPrimaryImage()?->image_path;
        if ($product && (empty($rawImage) || str_contains(strtolower($rawImage), '.webp'))) {
            $imageUrl = route('media.whatsapp.product', $product);
        } else {
            $imageUrl = $rawImage ? image_url($rawImage) : null;
        }

        $name = trim((string) $user->name) ?: 'Customer';
        $total = (string) number_format((float) $cart->subtotal(), 2, '.', '');
        $cartRef = 'Cart (' . $cart->itemsCount() . ' item' . ($cart->itemsCount() > 1 ? 's' : '') . ')';
        $recoveryUrl = url('/cart');

        // Pre-initialize WhatsApp checkout session so customer can order directly from WhatsApp
        try {
            $checkoutService = app(\App\Services\WhatsAppCheckoutService::class);
            $session = $checkoutService->createSessionFromAbandonedCart($phone, $cart, $user);

            // Attempt direct interactive Order Review if within 24h window
            if (! empty($session->customer_name) && ! empty($session->pincode) && ! empty($session->address_data['address_line1'])) {
                $session->transitionTo(\App\Models\WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION);
                $checkoutService->sendOrderReviewMessage($session);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Direct WhatsApp review send skipped: ' . $e->getMessage());
        }

        $result = $whatsapp->sendAbandonedCheckoutRecovery(
            $phone,
            $name,
            $cartRef,
            $total,
            $recoveryUrl,
            $productName,
            $imageUrl
        );

        if (($result['result'] ?? '0') === '1') {
            $cart->update(['whatsapp_recovery_sent_at' => now()]);

            return back()->with('success', "WhatsApp cart recovery message sent to {$phone}! The customer can now place their order directly from WhatsApp.");
        }

        return back()->with('error', 'Meta WhatsApp API delivery failed: ' . ($result['message'] ?? 'Unknown error'));
    }
}
