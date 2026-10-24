<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppCampaignJob;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppCampaignController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    /**
     * Display campaign dashboard and list.
     */
    public function index(): View
    {
        $campaigns = WhatsAppCampaign::with('creator')
            ->latest()
            ->paginate(15);

        $totalCampaigns = WhatsAppCampaign::count();
        $totalSent = (int) WhatsAppCampaign::sum('sent_count');
        $totalDelivered = (int) WhatsAppCampaign::sum('delivered_count');
        $totalRead = (int) WhatsAppCampaign::sum('read_count');

        $overallDeliveryRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0;
        $overallReadRate = $totalDelivered > 0 ? round(($totalRead / $totalDelivered) * 100, 1) : 0;

        return view('admin.whatsapp.campaigns.index', compact(
            'campaigns',
            'totalCampaigns',
            'totalSent',
            'overallDeliveryRate',
            'overallReadRate'
        ));
    }

    /**
     * Show the campaign creation wizard.
     */
    public function create(): View
    {
        // Calculate audience estimates
        $estimates = [
            'all' => User::whereNotNull('phone')->where('phone', '!=', '')->count(),
            'abandoned_cart' => User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereHas('carts', function ($q) {
                    $q->has('items')->where('updated_at', '>=', now()->subDays(7));
                })
                ->count(),
            'buyers' => User::whereHas('orders', fn($q) => $q->where('payment_status', 'paid'))->count(),
            'inactive' => User::whereNotNull('phone')
                ->whereDoesntHave('orders', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->count(),
        ];

        $approvedTemplates = \App\Models\WhatsAppTemplate::approved()->orderBy('name')->get();

        return view('admin.whatsapp.campaigns.create', compact('estimates', 'approvedTemplates'));
    }

    /**
     * Store and queue or schedule a campaign.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_segment' => 'required|string|in:all,abandoned_cart,buyers,inactive',
            'template_name' => 'required|string|max:120',
            'template_lang' => 'nullable|string|max:10',
            'parameters' => 'nullable|array',
            'header_image_url' => 'nullable|url|max:500',
            'scheduled_at' => 'nullable|date',
        ]);

        $segment = $request->input('target_segment');

        // Resolve recipients list
        $recipients = $this->resolveSegmentRecipients($segment);

        if (empty($recipients)) {
            return back()->withInput()->with('error', 'Selected target audience segment currently has 0 customers with valid phone numbers.');
        }

        $campaign = WhatsAppCampaign::create([
            'name' => $request->input('name'),
            'template_name' => $request->input('template_name'),
            'template_lang' => $request->input('template_lang', 'en'),
            'target_segment' => $segment,
            'parameters' => $request->input('parameters', []),
            'header_image_url' => $request->input('header_image_url'),
            'status' => 'draft',
            'total_recipients' => count($recipients),
            'scheduled_at' => $request->filled('scheduled_at') ? $request->input('scheduled_at') : null,
            'created_by' => auth('admin')->id(),
            'notes' => $request->input('notes'),
        ]);

        // Bulk insert recipients in chunks of 200
        $now = now();
        $batch = [];
        foreach ($recipients as $item) {
            $batch[] = [
                'whatsapp_campaign_id' => $campaign->id,
                'phone' => $this->apiService->formatRecipientPhone($item['phone']),
                'customer_name' => $item['name'] ?: 'Customer',
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 200) {
                WhatsAppCampaignRecipient::insert($batch);
                $batch = [];
            }
        }
        if (! empty($batch)) {
            WhatsAppCampaignRecipient::insert($batch);
        }

        // If user selected immediate broadcast
        if ($request->boolean('dispatch_now')) {
            ProcessWhatsAppCampaignJob::dispatch($campaign);
            $msg = "WhatsApp Campaign \"{$campaign->name}\" launched! Background broadcast is now processing.";
        } else {
            $msg = "WhatsApp Campaign \"{$campaign->name}\" created with {$campaign->total_recipients} recipients.";
        }

        return redirect()->route('admin.whatsapp.campaigns.show', $campaign->id)->with('success', $msg);
    }

    /**
     * Show detailed campaign report with recipient delivery logs.
     */
    public function show(Request $request, WhatsAppCampaign $campaign): View
    {
        $statusFilter = $request->input('status');
        $search = trim((string) $request->input('search', ''));

        $query = $campaign->recipients();

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $recipients = $query->paginate(30)->withQueryString();

        return view('admin.whatsapp.campaigns.show', compact('campaign', 'recipients', 'statusFilter', 'search'));
    }

    /**
     * Send a single test message to verify how the template looks on phone.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'test_phone' => 'required|string|min:10',
            'template_name' => 'required|string',
            'template_lang' => 'nullable|string',
            'parameters' => 'nullable|array',
            'header_image_url' => 'nullable|url',
        ]);

        $phone = $this->apiService->formatRecipientPhone($request->input('test_phone'));
        $components = [];

        if ($request->filled('header_image_url')) {
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'image', 'image' => ['link' => $request->input('header_image_url')]],
                ],
            ];
        }

        $parameters = (array) $request->input('parameters', []);
        if (! empty($parameters)) {
            $bodyParams = [];
            foreach ($parameters as $p) {
                $bodyParams[] = ['type' => 'text', 'text' => (string) $p];
            }
            $components[] = ['type' => 'body', 'parameters' => $bodyParams];
        }

        $res = $this->apiService->sendTemplateMessage(
            $phone,
            $request->input('template_name'),
            $request->input('template_lang', 'en'),
            $components
        );

        if (! ($res['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Test send failed',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Test WhatsApp template dispatched successfully to {$phone}!",
        ]);
    }

    /**
     * Manually trigger/resume a broadcast.
     */
    public function dispatchCampaign(WhatsAppCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'completed') {
            return back()->with('error', 'Campaign has already completed.');
        }

        ProcessWhatsAppCampaignJob::dispatch($campaign);

        return back()->with('success', "Campaign broadcast job queued for processing.");
    }

    /**
     * Resolve audience cohort into unique phone numbers and names.
     */
    protected function resolveSegmentRecipients(string $segment): array
    {
        $recipients = [];

        if ($segment === 'all') {
            $users = User::whereNotNull('phone')->where('phone', '!=', '')->get(['name', 'phone']);
            foreach ($users as $u) {
                $recipients[] = ['phone' => $u->phone, 'name' => $u->name];
            }
        } elseif ($segment === 'abandoned_cart') {
            $users = User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereHas('carts', function ($q) {
                    $q->has('items')->where('updated_at', '>=', now()->subDays(7));
                })
                ->get(['name', 'phone']);

            $seen = [];
            foreach ($users as $u) {
                if ($u->phone && ! isset($seen[$u->phone])) {
                    $seen[$u->phone] = true;
                    $recipients[] = ['phone' => $u->phone, 'name' => $u->name ?: 'Customer'];
                }
            }
        } elseif ($segment === 'buyers') {
            $users = User::whereHas('orders', fn($q) => $q->where('payment_status', 'paid'))->get(['name', 'phone']);
            foreach ($users as $u) {
                $recipients[] = ['phone' => $u->phone, 'name' => $u->name];
            }
        } elseif ($segment === 'inactive') {
            $users = User::whereNotNull('phone')
                ->whereDoesntHave('orders', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->get(['name', 'phone']);
            foreach ($users as $u) {
                $recipients[] = ['phone' => $u->phone, 'name' => $u->name];
            }
        }

        return $recipients;
    }
}
