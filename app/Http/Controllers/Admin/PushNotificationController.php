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
            'icon_url' => $validated['icon_url'] ?? asset('images/logo.png'),
            'sent_by' => auth('admin')->id(),
            'status' => 'draft',
        ]);

        $result = $this->webPushService->broadcastCampaign($campaign);

        if (! empty($result['success'])) {
            $msg = "Push notification broadcast completed! Sent: {$result['sent']} devices";
            if (! empty($result['failed'])) {
                $msg .= ", Failed/Expired: {$result['failed']}";
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', $result['message'] ?? 'Failed to broadcast notification.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:300'],
            'url' => ['required', 'url', 'max:500'],
        ]);

        $subscription = PushSubscription::active()->latest('last_active_at')->first();

        if (! $subscription) {
            return back()->with('error', 'No active device subscriptions found to receive a test alert. Visit the website and click "Allow Alerts" first.');
        }

        $payload = [
            'title' => '[TEST] ' . $validated['title'],
            'body' => $validated['body'],
            'icon' => asset('images/logo.png'),
            'data' => [
                'url' => $validated['url'],
            ],
        ];

        $success = $this->webPushService->sendToSubscription($subscription, $payload);

        if ($success) {
            return back()->with('success', "Test push notification dispatched successfully to subscription #{$subscription->id} ({$subscription->device_type} / {$subscription->browser})!");
        }

        return back()->with('error', 'Failed to dispatch test notification. Endpoint may be expired.');
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
