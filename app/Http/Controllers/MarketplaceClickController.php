<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceClick;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceClickController extends Controller
{
    /**
     * Record an outbound click event to Amazon or Flipkart for a given product.
     */
    public function record(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'marketplace' => ['required', 'string', 'in:amazon,flipkart,Amazon,Flipkart'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $product = Product::query()->select(['id', 'sku'])->find($validated['product_id']);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $marketplace = strtolower($validated['marketplace']);

        MarketplaceClick::create([
            'product_id' => $product->id,
            'sku' => $product->sku,
            'marketplace' => $marketplace,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'referrer' => substr((string) $request->header('referer'), 0, 1000),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Click recorded successfully',
        ], 200);
    }
}

