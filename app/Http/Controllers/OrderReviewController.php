<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderReviewController extends Controller
{
    public function show(Order $order, Request $request): View
    {
        $this->verifyCustomerMobile($order, $request);

        $order->load(['items.product.images', 'items.review.images']);

        $selectedItemId = $request->query('item');
        $selectedItem = $selectedItemId
            ? $order->items->firstWhere('id', (int) $selectedItemId)
            : null;

        if (! $selectedItem) {
            $selectedItem = $order->items->sortByDesc(fn ($i) => (float) $i->total_price)->first();
        }

        $existingReview = $selectedItem ? Review::where('order_item_id', $selectedItem->id)->with('images')->first() : null;

        return view('storefront.orders.review', compact('order', 'selectedItem', 'existingReview'));
    }

    public function submit(Order $order, Request $request): RedirectResponse
    {
        $this->verifyCustomerMobile($order, $request);

        $data = $request->validate([
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:1500'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $item = $order->items()->findOrFail($data['order_item_id']);

        if ($item->review) {
            return back()->with('error', 'You have already reviewed this product. Thank you!');
        }

        try {
            $review = Review::create([
                'product_id' => $item->product_id,
                'user_id' => $order->user_id,
                'order_item_id' => $item->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'] ?? null,
                'status' => (bool) setting('auto_approve_reviews', true) ? 'approved' : 'pending',
                'is_verified_purchase' => true,
            ]);

            if ($request->hasFile('images')) {
                $this->storeReviewImages($request->file('images'), $review);
            }

            return back()->with('success', 'Thank you! Your review for ' . ($item->product_name ?: 'this item') . ' has been submitted.');
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062 || ($e->errorInfo[0] ?? null) === '23000') {
                return back()->with('error', 'You have already submitted a review for this product.');
            }
            throw $e;
        }
    }

    protected function verifyCustomerMobile(Order $order, Request $request): void
    {
        $mobile = canonical_phone(trim((string) $request->query('mobile', '')));
        $orderMobile = canonical_phone($order->shipping_mobile ?: ($order->billing_mobile ?: ($order->guest_mobile ?: $order->user?->phone)));

        if ($mobile === null || $orderMobile === null || $mobile !== $orderMobile) {
            abort(403, 'Invalid or expired review link.');
        }
    }

    protected function storeReviewImages(array $files, Review $review): void
    {
        $sortOrder = 0;
        foreach ($files as $file) {
            $path = $file->store('reviews/' . $review->id, 's3');
            ReviewImage::create([
                'review_id' => $review->id,
                'path' => $path,
                'disk' => 's3',
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'sort_order' => $sortOrder++,
            ]);
        }
    }
}
