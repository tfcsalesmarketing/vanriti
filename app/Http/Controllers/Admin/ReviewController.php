<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $query = Review::query()
            ->with(['user', 'product.images', 'orderItem']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->input('q')) {
            $query->whereHas('product', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })->orWhere('title', 'like', "%{$q}%")
              ->orWhere('comment', 'like', "%{$q}%");
        }

        $reviews = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve(Review $review)
    {
        $review->update(['status' => 'approved']);

        if ($review->product) {
            $this->recomputeProductRatings($review->product);
        }

        return redirect()->back()->with('success', 'Review approved.');
    }

    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected']);

        if ($review->product) {
            $this->recomputeProductRatings($review->product);
        }

        return redirect()->back()->with('success', 'Review rejected.');
    }

    public function destroy(Review $review)
    {
        $product = $review->product;

        $review->delete();

        if ($product) {
            $this->recomputeProductRatings($product);
        }

        return redirect()->back()->with('success', 'Review deleted.');
    }

    private function recomputeProductRatings(Product $product): void
    {
        $product->update([
            'review_count' => $product->approvedReviews()->count(),
            'review_rating' => round((float) $product->approvedReviews()->avg('rating'), 2),
        ]);
    }
}
