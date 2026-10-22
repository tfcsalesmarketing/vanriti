<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartWishlistController extends Controller
{
    public function carts(Request $request): View
    {
        $query = CartItem::query()
            ->with(['cart.owner', 'product', 'variant'])
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
}
