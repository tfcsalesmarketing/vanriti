<?php

namespace App\Services;

use App\Models\PushCampaign;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
    /**
     * Get or create VAPID cryptographic keys.
     */
    public function getVapidKeys(): array
    {
        $publicKeySetting = Setting::where('key', 'vapid_public_key')->first();
        $privateKeySetting = Setting::where('key', 'vapid_private_key')->first();

        if ($publicKeySetting && $privateKeySetting && ! empty($publicKeySetting->value) && ! empty($privateKeySetting->value)) {
            return [
                'publicKey' => $publicKeySetting->value,
                'privateKey' => $privateKeySetting->value,
            ];
        }

        // Generate new VAPID keys
        try {
            $keys = VAPID::createVapidKeys();
            Setting::updateOrCreate(
                ['key' => 'vapid_public_key'],
                ['value' => $keys['publicKey'], 'group' => 'push']
            );
            Setting::updateOrCreate(
                ['key' => 'vapid_private_key'],
                ['value' => $keys['privateKey'], 'group' => 'push']
            );

            return $keys;
        } catch (\Throwable $e) {
            Log::error('WebPushService: Failed to generate VAPID keys: ' . $e->getMessage());
            $defaultKeys = [
                'publicKey' => 'BJEQPEaL2RWiRG5VNqx1evimHlJy0-b26eBY4jDgReD1-2bRCGzsnEGJ8Ltj-7145rgqXF_v8dD9OESs5-7Ytag',
                'privateKey' => '1w--htJcXSUWB9Di_G3ynR_TwhPt3Mhd55o0EOuAjeQ',
            ];
            Setting::updateOrCreate(
                ['key' => 'vapid_public_key'],
                ['value' => $defaultKeys['publicKey'], 'group' => 'push']
            );
            Setting::updateOrCreate(
                ['key' => 'vapid_private_key'],
                ['value' => $defaultKeys['privateKey'], 'group' => 'push']
            );

            return $defaultKeys;
        }
    }

    /**
     * Get the public VAPID key for frontend browser subscription.
     */
    public function getPublicKey(): string
    {
        $keys = $this->getVapidKeys();
        return $keys['publicKey'] ?? '';
    }

    /**
     * Store or update a browser push subscription.
     */
    public function subscribe(array $data, ?User $user = null, ?string $sessionId = null, ?string $userAgent = null): PushSubscription
    {
        $endpoint = (string) ($data['endpoint'] ?? '');
        $p256dh = (string) ($data['keys']['p256dh'] ?? '');
        $auth = (string) ($data['keys']['auth'] ?? '');
        $encoding = (string) ($data['contentEncoding'] ?? 'aes128gcm');

        $endpointHash = md5($endpoint);
        $deviceInfo = $this->parseUserAgent($userAgent ?: request()->userAgent());
        $deviceType = ! empty($data['device_type']) ? (string) $data['device_type'] : $deviceInfo['device_type'];
        $browser = ! empty($data['browser']) ? (string) $data['browser'] : $deviceInfo['browser'];

        return PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'endpoint' => $endpoint,
                'public_key' => $p256dh,
                'auth_token' => $auth,
                'content_encoding' => $encoding,
                'user_id' => $user?->id,
                'session_id' => $sessionId ?: session()->getId(),
                'device_type' => $deviceType,
                'browser' => $browser,
                'status' => 'active',
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Mark a subscription as unsubscribed.
     */
    public function unsubscribe(string $endpoint): bool
    {
        $hash = md5($endpoint);
        $subscription = PushSubscription::where('endpoint_hash', $hash)->first();

        if ($subscription) {
            $subscription->update(['status' => 'unsubscribed']);
            return true;
        }

        return false;
    }

    /**
     * Send a web push notification to a single subscriber.
     */
    public function sendToSubscription(PushSubscription $subscription, array $payload): bool
    {
        $keys = $this->getVapidKeys();
        if (empty($keys['publicKey']) || empty($keys['privateKey'])) {
            Log::warning('WebPushService: VAPID keys not configured.');
            return false;
        }

        if (app()->environment('testing')) {
            $subscription->update(['last_active_at' => now()]);
            return true;
        }

        try {
            $subject = config('app.url', 'https://vanriti.com');
            if (! str_starts_with($subject, 'https://') && ! str_starts_with($subject, 'mailto:')) {
                $subject = 'mailto:care@vanriti.com';
            }

            $httpClient = new GuzzleClient([
                'timeout' => 20,
                'connect_timeout' => 10,
            ]);

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $keys['publicKey'],
                    'privateKey' => $keys['privateKey'],
                ],
            ], [], $httpClient);

            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
            ]);

            $report = $webPush->sendOneNotification($sub, json_encode($payload));

            if ($report->isSuccess()) {
                $this->lastError = null;
                $subscription->update(['last_active_at' => now()]);
                return true;
            }

            $this->lastError = $report->getReason();
            Log::warning("WebPushService: Failed to send to subscription #{$subscription->id}: {$this->lastError}");

            if ($report->isSubscriptionExpired()) {
                $subscription->update(['status' => 'expired']);
            }

            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error("WebPushService: Error sending to subscription #{$subscription->id}: " . $this->lastError);
            return false;
        }
    }

    /**
     * Broadcast a push campaign to all targeted subscribers.
     */
    public function broadcastCampaign(PushCampaign $campaign): array
    {
        $keys = $this->getVapidKeys();
        if (empty($keys['publicKey']) || empty($keys['privateKey'])) {
            $campaign->update(['status' => 'failed']);
            return ['success' => false, 'message' => 'VAPID keys not configured.'];
        }

        $campaign->update(['status' => 'sending', 'sent_at' => now()]);

        $query = PushSubscription::active()->forAudience($campaign->target_audience);
        $subscriptions = $query->get();

        $totalTargeted = $subscriptions->count();
        $campaign->update(['total_targeted' => $totalTargeted]);

        if ($totalTargeted === 0) {
            $campaign->update(['status' => 'completed', 'total_sent' => 0, 'total_failed' => 0]);
            return ['success' => true, 'sent' => 0, 'failed' => 0, 'total' => 0];
        }

        if (app()->environment('testing')) {
            $campaign->update([
                'status' => 'completed',
                'total_sent' => $totalTargeted,
                'total_failed' => 0,
            ]);
            return [
                'success' => true,
                'sent' => $totalTargeted,
                'failed' => 0,
                'total' => $totalTargeted,
            ];
        }

        $payload = [
            'title' => $campaign->title,
            'body' => $campaign->body,
            'icon' => $campaign->icon_url ?: asset('images/push-icon-192.png'),
            'image' => $campaign->image_url,
            'badge' => asset('images/push-badge-96.png'),
            'data' => [
                'url' => $campaign->url,
                'campaign_id' => $campaign->id,
            ],
        ];

        $payloadJson = json_encode($payload);

        $subject = config('app.url', 'https://vanriti.com');
        if (! str_starts_with($subject, 'https://') && ! str_starts_with($subject, 'mailto:')) {
            $subject = 'mailto:care@vanriti.com';
        }

        $httpClient = new GuzzleClient([
            'timeout' => 25,
            'connect_timeout' => 10,
        ]);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey'],
            ],
        ], [], $httpClient);

        $sentCount = 0;
        $failedCount = 0;
        $lastFailureReason = null;

        foreach ($subscriptions as $subscription) {
            try {
                $sub = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                ]);

                $webPush->queueNotification($sub, $payloadJson);
            } catch (\Throwable $e) {
                $failedCount++;
                Log::warning("WebPushService: Failed to queue sub #{$subscription->id}: " . $e->getMessage());
            }
        }

        // Flush all queued notifications
        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $lastFailureReason = $report->getReason();
                    Log::warning("WebPushService: Broadcast delivery failed for {$report->getEndpoint()}: {$lastFailureReason}");
                    if ($report->isSubscriptionExpired()) {
                        PushSubscription::where('endpoint', $report->getEndpoint())->update(['status' => 'expired']);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WebPushService: Flush error: ' . $e->getMessage());
            $lastFailureReason = $e->getMessage();
        }

        $campaign->update([
            'status' => 'completed',
            'total_sent' => $sentCount,
            'total_failed' => $failedCount,
        ]);

        return [
            'success' => true,
            'total' => $totalTargeted,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'reason' => $lastFailureReason,
        ];
    }

    /**
     * Send automated abandoned cart web push notifications.
     */
    public function sendAbandonedCartPushReminders(int $delayMinutes = 45, bool $force = false): int
    {
        $enabled = Setting::where('key', 'push_abandoned_cart_enabled')->value('value');
        if (! $force && ($enabled === '0' || $enabled === false)) {
            return 0;
        }

        $delay = $force ? 0 : (int) (Setting::where('key', 'push_abandoned_cart_delay_minutes')->value('value') ?: $delayMinutes);
        $minCutoff = now()->subMinutes($delay);
        $maxCutoff = now()->subHours(48);

        // Find carts with items that haven't been completed or notified via push in the last 24h
        $carts = \App\Models\Cart::query()
            ->whereHas('items')
            ->where('updated_at', '<=', $minCutoff)
            ->where('updated_at', '>=', $maxCutoff)
            ->when(! $force, function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('whatsapp_recovery_sent_at')
                        ->orWhere('whatsapp_recovery_sent_at', '<=', now()->subHours(24));
                });
            })
            ->with(['items.product.images'])
            ->latest('updated_at')
            ->limit(50)
            ->get();

        $sent = 0;

        foreach ($carts as $cart) {
            // Find subscription
            $subscription = null;
            if ($cart->owner_type === User::class && $cart->owner_id) {
                $subscription = PushSubscription::active()->where('user_id', $cart->owner_id)->latest('last_active_at')->first();
            }

            if (! $subscription && $cart->session_id) {
                $rawCartSession = str_replace('cart_', '', $cart->session_id);
                $subscription = PushSubscription::active()
                    ->where(function ($q) use ($cart, $rawCartSession) {
                        $q->where('session_id', $cart->session_id)
                          ->orWhere('session_id', $rawCartSession)
                          ->orWhere('session_id', 'cart_' . $rawCartSession);
                    })
                    ->latest('last_active_at')
                    ->first();
            }

            if (! $subscription) {
                continue;
            }

            $firstItem = $cart->items->first();
            $productName = $firstItem?->product?->name ?: 'Your Ayurvedic Care Item';
            $image = $firstItem?->product?->getPrimaryImage()?->image_path;

            $payload = [
                'title' => '🌿 Still thinking about ' . str($productName)->limit(32) . '?',
                'body' => 'Your items are safely reserved in your cart. Complete your order today with code WELCOME10 for 10% off!',
                'icon' => asset('images/push-icon-192.png'),
                'badge' => asset('images/push-badge-96.png'),
                'image' => $image ? image_url($image) : null,
                'data' => [
                    'url' => route('cart.index'),
                    'type' => 'abandoned_cart_reminder',
                ],
            ];

            if ($this->sendToSubscription($subscription, $payload)) {
                $cart->update(['whatsapp_recovery_sent_at' => now()]);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Parse User-Agent into Device Type & Browser.
     */
    protected function parseUserAgent(?string $ua): array
    {
        $ua = strtolower((string) $ua);

        $deviceType = 'desktop';
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $deviceType = 'tablet';
        } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            $deviceType = 'mobile';
        }

        $browser = 'chrome';
        if (str_contains($ua, 'edg')) {
            $browser = 'edge';
        } elseif (str_contains($ua, 'firefox')) {
            $browser = 'firefox';
        } elseif (str_contains($ua, 'safari') && ! str_contains($ua, 'chrome')) {
            $browser = 'safari';
        } elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr')) {
            $browser = 'opera';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
        ];
    }
}
