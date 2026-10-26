<?php

namespace App\Http\Controllers;

use App\Services\PincodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PincodeController extends Controller
{
    public function __construct(protected PincodeService $pincodeService) {}

    /**
     * Check pincode serviceability, estimated delivery, and COD availability.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => 'required|string|max:10',
        ]);

        $result = $this->pincodeService->check((string) $validated['pincode']);

        return response()->json($result);
    }

    /**
     * Get saved delivery pincode from current session.
     */
    public function saved(): JsonResponse
    {
        $pincode = session('delivery_pincode');
        if (! $pincode) {
            return response()->json(['has_saved' => false]);
        }

        $result = $this->pincodeService->check($pincode);
        $result['has_saved'] = true;

        return response()->json($result);
    }
}
