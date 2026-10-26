<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StockNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockNotificationController extends Controller
{
    public function store(Request $request, StockNotificationService $service): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        $result = $service->subscribe($validated, $request->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
