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
            ->with(['user', 'product.images', 'orderItem', 'images']);

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

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('import_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return redirect()->back()->with('error', 'Unable to open uploaded file.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV file appears to be empty.');
        }

        $normalizedHeader = array_map(function ($col) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', (string) $col)));
        }, $header);

        $skuIdx = array_search('sku', $normalizedHeader, true);
        $prodIdIdx = array_search('productid', $normalizedHeader, true);
        if ($prodIdIdx === false) {
            $prodIdIdx = array_search('product_id', $normalizedHeader, true);
        }

        $nameIdx = array_search('username', $normalizedHeader, true);
        if ($nameIdx === false) {
            $nameIdx = array_search('user_name', $normalizedHeader, true);
        }
        if ($nameIdx === false) {
            $nameIdx = array_search('name', $normalizedHeader, true);
        }
        if ($nameIdx === false) {
            $nameIdx = array_search('reviewer', $normalizedHeader, true);
        }
        if ($nameIdx === false) {
            $nameIdx = array_search('author', $normalizedHeader, true);
        }

        $ratingIdx = array_search('rating', $normalizedHeader, true);
        if ($ratingIdx === false) {
            $ratingIdx = array_search('stars', $normalizedHeader, true);
        }

        $titleIdx = array_search('title', $normalizedHeader, true);
        if ($titleIdx === false) {
            $titleIdx = array_search('headline', $normalizedHeader, true);
        }

        $commentIdx = array_search('comment', $normalizedHeader, true);
        if ($commentIdx === false) {
            $commentIdx = array_search('review', $normalizedHeader, true);
        }
        if ($commentIdx === false) {
            $commentIdx = array_search('content', $normalizedHeader, true);
        }

        $verifiedIdx = array_search('isverifiedpurchase', $normalizedHeader, true);
        if ($verifiedIdx === false) {
            $verifiedIdx = array_search('is_verified_purchase', $normalizedHeader, true);
        }
        if ($verifiedIdx === false) {
            $verifiedIdx = array_search('verified', $normalizedHeader, true);
        }

        $statusIdx = array_search('status', $normalizedHeader, true);

        $dateIdx = array_search('createdat', $normalizedHeader, true);
        if ($dateIdx === false) {
            $dateIdx = array_search('created_at', $normalizedHeader, true);
        }
        if ($dateIdx === false) {
            $dateIdx = array_search('date', $normalizedHeader, true);
        }

        if ($skuIdx === false && $prodIdIdx === false) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV header must include at least a "sku" or "product_id" column.');
        }

        $imported = 0;
        $skipped = 0;
        $affectedProducts = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $sku = $skuIdx !== false ? trim((string) ($row[$skuIdx] ?? '')) : null;
            $productId = $prodIdIdx !== false ? trim((string) ($row[$prodIdIdx] ?? '')) : null;

            $product = null;
            if ($sku) {
                $product = Product::where('sku', strtoupper($sku))->first();
            }
            if (! $product && $productId && is_numeric($productId)) {
                $product = Product::find((int) $productId);
            }

            if (! $product) {
                $skipped++;
                continue;
            }

            $userName = $nameIdx !== false ? trim((string) ($row[$nameIdx] ?? '')) : 'Verified Buyer';
            $rating = $ratingIdx !== false && is_numeric(trim((string) ($row[$ratingIdx] ?? '')))
                ? (int) trim((string) $row[$ratingIdx])
                : 5;
            $rating = max(1, min(5, $rating));

            $title = $titleIdx !== false ? trim((string) ($row[$titleIdx] ?? '')) : null;
            $comment = $commentIdx !== false ? trim((string) ($row[$commentIdx] ?? '')) : null;
            $rawStatus = $statusIdx !== false ? strtolower(trim((string) ($row[$statusIdx] ?? ''))) : 'approved';
            $status = in_array($rawStatus, ['approved', 'pending', 'rejected', 'hidden'], true) ? $rawStatus : 'approved';

            $isVerified = true;
            if ($verifiedIdx !== false) {
                $rawV = strtolower(trim((string) ($row[$verifiedIdx] ?? '1')));
                $isVerified = in_array($rawV, ['1', 'true', 'yes', 'y'], true);
            }

            $createdAt = now();
            if ($dateIdx !== false && ! empty(trim((string) ($row[$dateIdx] ?? '')))) {
                try {
                    $createdAt = \Carbon\Carbon::parse(trim((string) $row[$dateIdx]));
                } catch (\Throwable $e) {
                    $createdAt = now();
                }
            }

            Review::create([
                'product_id' => $product->id,
                'user_id' => null,
                'user_name' => $userName ?: 'Verified Buyer',
                'rating' => $rating,
                'title' => $title ?: null,
                'comment' => $comment ?: null,
                'status' => $status,
                'is_verified_purchase' => $isVerified,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $affectedProducts[$product->id] = $product;
            $imported++;
        }

        fclose($handle);

        foreach ($affectedProducts as $prod) {
            $this->recomputeProductRatings($prod);
        }

        $msg = "Successfully imported {$imported} reviews.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} rows skipped due to missing or unmatchable SKU/Product ID).";
        }

        return redirect()->back()->with('success', $msg);
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
