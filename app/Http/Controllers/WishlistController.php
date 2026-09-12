<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlistService)
    {
    }

    public function index(): View
    {
        $wishlist = $this->wishlistService->getWishlist();
        $wishlistItems = $wishlist
            ? $wishlist->items()->with(['images', 'categories'])->get()
            : collect();

        return view('storefront.wishlist.index', compact('wishlistItems'));
    }

    public function toggle(Product $product): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->wishlistService->toggle($product);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'added' => $result['added'],
                    'count' => $result['count'],
                ]);
            }

            $message = $result['added'] ? 'Added to wishlist.' : 'Removed from wishlist.';

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Unable to update wishlist.');
        }
    }
}
