<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PushCampaign;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Services\WebPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PushNotificationController extends Controller
{
    public function __construct(
        protected WebPushService $webPushService
    ) {}

    public function index(): View
    {
        $keys = $this->webPushService->getVapidKeys();

        $stats = [
            'total_subscribers' => PushSubscription::count(),
            'active_subscribers' => PushSubscription::active()->count(),
            'anonymous_subscribers' => PushSubscription::active()->anonymous()->count(),
            'customer_subscribers' => PushSubscription::active()->whereNotNull('user_id')->count(),
            'mobile_subscribers' => PushSubscription::active()->where('device_type', 'mobile')->count(),
            'desktop_subscribers' => PushSubscription::active()->where('device_type', 'desktop')->count(),
            'total_campaigns' => PushCampaign::count(),
            'total_sent' => (int) PushCampaign::sum('total_sent'),
            'total_clicks' => (int) PushCampaign::sum('total_clicked'),
        ];

        $stats['overall_ctr'] = $stats['total_sent'] > 0
            ? round(($stats['total_clicks'] / $stats['total_sent']) * 100, 1)
            : 0.0;

        $campaigns = PushCampaign::with('sender')
            ->latest('created_at')
            ->paginate(15);

        $subscribers = PushSubscription::with('user')
            ->latest('last_active_at')
            ->take(15)
            ->get();

        $autoPushEnabled = Setting::where('key', 'push_abandoned_cart_enabled')->value('value') !== '0';
        $autoPushDelay = (int) (Setting::where('key', 'push_abandoned_cart_delay_minutes')->value('value') ?: 45);

        $products = Product::active()->latest()->take(10)->get(['id', 'name', 'slug', 'selling_price']);

        return view('admin.marketing.push.index', compact(
            'stats',
            'campaigns',
            'subscribers',
            'keys',
            'autoPushEnabled',
            'autoPushDelay',
            'products'
        ));
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->filled('url')) {
            $inputUrl = trim((string) $request->input('url'));
            if (! str_starts_with($inputUrl, 'http://') && ! str_starts_with($inputUrl, 'https://')) {
                $request->merge(['url' => url(ltrim($inputUrl, '/'))]);
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:300'],
            'url' => ['required', 'url', 'max:500'],
            'target_audience' => ['required', 'in:all,abandoned_cart,customers'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'icon_url' => ['nullable', 'url', 'max:500'],
        ]);

        $campaign = PushCampaign::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'url' => $validated['url'],
            'target_audience' => $validated['target_audience'],
            'image_url' => $validated['image_url'] ?? null,
            'icon_url' => $validated['icon_url'] ?? asset('images/push-icon-192.png'),
            'sent_by' => auth('admin')->id(),
            'status' => 'draft',
        ]);

        $result = $this->webPushService->broadcastCampaign($campaign);

        if (! empty($result['success'])) {
            if ($result['total'] === 0) {
                return back()->with('warning', 'Broadcast campaign created, but 0 active subscriber devices were found in audience "' . ucfirst(str_replace('_', ' ', $validated['target_audience'])) . '". Please click "Subscribe This Device" above or allow notifications on the storefront first.');
            }

            $msg = "Push notification broadcast dispatched to {$result['sent']} active device(s)!";
            if (! empty($result['failed'])) {
                $msg .= " ({$result['failed']} expired device endpoints removed)";
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', $result['message'] ?? 'Failed to broadcast notification.');
    }

    public function sendTest(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->filled('url')) {
            $inputUrl = trim((string) $request->input('url'));
            if (! str_starts_with($inputUrl, 'http://') && ! str_starts_with($inputUrl, 'https://')) {
                $request->merge(['url' => url(ltrim($inputUrl, '/'))]);
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:300'],
            'url' => ['required', 'url', 'max:500'],
            'subscription_id' => ['nullable', 'integer'],
            'image_url' => ['nullable', 'url', 'max:500'],
        ]);

        $subscription = null;
        if (! empty($validated['subscription_id'])) {
            $subscription = PushSubscription::find($validated['subscription_id']);
        }
        if (! $subscription) {
            $subscription = PushSubscription::active()->latest('last_active_at')->first();
        }

        if (! $subscription) {
            $msg = 'No active device subscriptions found. Click "Subscribe This Device" above to register your browser, then send the test alert.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $payload = [
            'title' => '[TEST] ' . $validated['title'],
            'body' => $validated['body'],
            'icon' => asset('images/push-icon-192.png'),
            'badge' => asset('images/push-badge-96.png'),
            'image' => $validated['image_url'] ?? null,
            'data' => [
                'url' => $validated['url'],
            ],
        ];

        $success = $this->webPushService->sendToSubscription($subscription, $payload);

        if ($success) {
            $msg = "Test alert dispatched to {$subscription->device_type} ({$subscription->browser}) #{$subscription->id}! It should appear on your screen now.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        }

        $reason = $this->webPushService->getLastError();
        $failMsg = 'Failed to deliver test alert' . ($reason ? ": {$reason}" : '. The browser endpoint may have expired or was denied.');
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $failMsg], 422);
        }
        return back()->with('error', $failMsg);
    }

    public function destroyCampaign(PushCampaign $campaign): RedirectResponse
    {
        $title = $campaign->title;
        $campaign->delete();

        return back()->with('success', "Campaign \"{$title}\" was deleted successfully.");
    }

    public function cleanupCampaigns(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:all,older_30,older_7,failed'],
        ]);

        $query = PushCampaign::query();

        $count = match ($validated['scope']) {
            'older_30' => $query->where('created_at', '<', now()->subDays(30))->delete(),
            'older_7' => $query->where('created_at', '<', now()->subDays(7))->delete(),
            'failed' => $query->where('status', 'failed')->delete(),
            'all' => $query->delete(),
        };

        return back()->with('success', "Cleanup complete: {$count} push notification campaign(s) removed.");
    }

    public function destroySubscriber(PushSubscription $subscription): RedirectResponse
    {
        $subscription->delete();
        return back()->with('success', 'Subscriber removed successfully.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'push_abandoned_cart_enabled' => ['nullable', 'boolean'],
            'push_abandoned_cart_delay_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'push_abandoned_cart_enabled'],
            ['value' => $request->boolean('push_abandoned_cart_enabled') ? '1' : '0', 'group' => 'push']
        );

        Setting::updateOrCreate(
            ['key' => 'push_abandoned_cart_delay_minutes'],
            ['value' => (string) $validated['push_abandoned_cart_delay_minutes'], 'group' => 'push']
        );

        return back()->with('success', 'Push notification automation settings updated successfully.');
    }
}
