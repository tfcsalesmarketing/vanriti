<?php

namespace App\Http\Controllers;

use App\Models\InstagramReel;
use Illuminate\Http\JsonResponse;

class ReelInteractionController extends Controller
{
    public function like(InstagramReel $reel): JsonResponse
    {
        $reel->increment('likes_count');

        return response()->json([
            'success' => true,
            'likes' => (int) $reel->fresh()->likes_count,
        ]);
    }

    public function share(InstagramReel $reel): JsonResponse
    {
        $reel->increment('shares_count');

        return response()->json([
            'success' => true,
            'shares' => (int) $reel->fresh()->shares_count,
        ]);
    }
}
