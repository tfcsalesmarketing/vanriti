<?php

namespace App\Http\Controllers;

use App\Models\PushCampaign;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebPushSubscriptionController extends Controller
{
    public function __construct(
        protected WebPushService $webPushService
    ) {}

    /**
     * Return public VAPID key for frontend browser subscription.
     */
    public function publicKey(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'publicKey' => $this->webPushService->getPublicKey(),
        ]);
    }

    /**
     * Store browser push subscription token.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'cart_token' => ['nullable', 'string', 'max:100'],
            'contentEncoding' => ['nullable', 'string', 'max:30'],
            'device_type' => ['nullable', 'string', 'max:20'],
            'browser' => ['nullable', 'string', 'max:30'],
        ]);

        $user = auth('web')->user();
        $cartSession = $request->input('cart_token') ?: $request->cookie(\App\Services\CartService::COOKIE_NAME);
        $sessionId = $cartSession ?: session()->getId();

        $subscription = $this->webPushService->subscribe(
            $validated,
            $user,
            $sessionId,
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Push notifications subscribed successfully.',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Unsubscribe a browser push token.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint', '');
        if ($endpoint) {
            $this->webPushService->unsubscribe($endpoint);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unsubscribed successfully.',
        ]);
    }

    /**
     * Track notification click event from service worker.
     */
    public function trackClick(int|string $campaignId): JsonResponse
    {
        $campaign = PushCampaign::find($campaignId);
        if ($campaign) {
            $campaign->increment('total_clicked');
        }

        return response()->json(['success' => true]);
    }
}
