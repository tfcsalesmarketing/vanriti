<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppTemplateController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $whatsappApi
    ) {}

    /**
     * Display templates dashboard with approval statuses and filters.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'all');
        $categoryFilter = $request->query('category', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = WhatsAppTemplate::where(function ($q) {
            $q->where('channel', 'vanriti')->orWhereNull('channel');
        });

        if ($statusFilter !== 'all') {
            $query->where('status', strtoupper($statusFilter));
        }

        if ($categoryFilter !== 'all') {
            $query->where('category', strtoupper($categoryFilter));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('body_text', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderByDesc('id')->paginate(12)->withQueryString();

        $allTemplates = WhatsAppTemplate::where(function ($q) {
            $q->where('channel', 'vanriti')->orWhereNull('channel');
        })->get();

        return view('admin.whatsapp.templates.index', [
            'templates' => $templates,
            'statusFilter' => $statusFilter,
            'categoryFilter' => $categoryFilter,
            'search' => $search,
            'totalCount' => $allTemplates->count(),
            'approvedCount' => $allTemplates->where('status', 'APPROVED')->count(),
            'pendingCount' => $allTemplates->where('status', 'PENDING')->count(),
            'rejectedCount' => $allTemplates->where('status', 'REJECTED')->count(),
            'lastSynced' => $allTemplates->max('synced_at'),
            'isConfigured' => $this->whatsappApi->isConfigured(),
            'wabaId' => $this->whatsappApi->wabaId(),
        ]);
    }

    /**
     * Core routine to fetch and upsert templates from Meta Cloud API.
     *
     * @return array{success: bool, count: int, message?: string}
     */
    private function syncFromMeta(): array
    {
        if (! $this->whatsappApi->isConfigured()) {
            return ['success' => false, 'count' => 0, 'message' => 'WhatsApp Cloud API credentials (Phone Number ID or Access Token) are not configured.'];
        }

        $wabaId = $this->whatsappApi->wabaId();
        if (empty($wabaId)) {
            return ['success' => false, 'count' => 0, 'message' => 'WhatsApp Business Account (WABA) ID is not configured. Please enter your WABA ID from Meta Developer Portal > WhatsApp > API Setup.'];
        }

        $res = $this->whatsappApi->getMessageTemplates();

        if (! $res['success']) {
            return ['success' => false, 'count' => 0, 'message' => $res['message'] ?? 'Failed to fetch templates from Meta.'];
        }

        $metaTemplates = $res['data'] ?? [];
        $syncedCount = 0;

        foreach ($metaTemplates as $item) {
            $name = $item['name'] ?? null;
            $lang = $item['language'] ?? 'en';
            if (! $name) {
                continue;
            }

            $components = $item['components'] ?? [];
            $headerType = null;
            $headerText = null;
            $bodyText = '';
            $footerText = null;
            $buttons = [];

            foreach ($components as $c) {
                $cType = strtoupper($c['type'] ?? '');
                if ($cType === 'HEADER') {
                    $headerType = strtoupper($c['format'] ?? 'TEXT');
                    $headerText = $c['text'] ?? null;
                } elseif ($cType === 'BODY') {
                    $bodyText = $c['text'] ?? '';
                } elseif ($cType === 'FOOTER') {
                    $footerText = $c['text'] ?? null;
                } elseif ($cType === 'BUTTONS') {
                    $buttons = $c['buttons'] ?? [];
                }
            }

            WhatsAppTemplate::updateOrCreate(
                [
                    'name' => $name,
                    'language' => $lang,
                    'channel' => 'vanriti',
                ],
                [
                    'meta_template_id' => $item['id'] ?? null,
                    'category' => strtoupper($item['category'] ?? 'MARKETING'),
                    'channel' => 'vanriti',
                    'status' => strtoupper($item['status'] ?? 'PENDING'),
                    'header_type' => $headerType,
                    'header_text' => $headerText,
                    'body_text' => $bodyText,
                    'footer_text' => $footerText,
                    'buttons' => ! empty($buttons) ? $buttons : null,
                    'rejected_reason' => $item['rejected_reason'] ?? null,
                    'synced_at' => now(),
                ]
            );

            $syncedCount++;
        }

        return ['success' => true, 'count' => $syncedCount];
    }

    /**
     * Sync all registered templates from Meta Graph API.
     */
    public function sync(Request $request): RedirectResponse|JsonResponse
    {
        $result = $this->syncFromMeta();

        if ($request->wantsJson() || $request->ajax()) {
            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            $templates = WhatsAppTemplate::approved()->orderBy('name')->get();
            return response()->json([
                'success' => true,
                'count' => $result['count'],
                'message' => "Successfully synced {$result['count']} templates from Meta WABA.",
                'templates' => $templates,
            ]);
        }

        if (! $result['success']) {
            return back()->with('error', 'Failed to fetch templates from Meta: ' . $result['message']);
        }

        return back()->with('success', "Successfully synced {$result['count']} templates directly from Meta WABA.");
    }

    /**
     * Update the WhatsApp Business Account (WABA) ID and immediately attempt sync.
     */
    public function updateWabaId(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'waba_id' => ['required', 'string', 'regex:/^[0-9]{10,25}$/'],
        ], [
            'waba_id.required' => 'Please provide your WhatsApp Business Account (WABA) ID.',
            'waba_id.regex' => 'The WABA ID must be numeric (typically 15-16 digits) as found in Meta Developer Portal.',
        ]);

        $cleanWabaId = trim($validated['waba_id']);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'whatsapp_waba_id'],
            [
                'value' => $cleanWabaId,
                'label' => 'WhatsApp Business Account (WABA) ID',
                'group' => 'whatsapp',
                'type' => 'text',
            ]
        );

        if (function_exists('cache')) {
            cache()->forget('settings.all');
        }

        $syncResult = $this->syncFromMeta();

        if ($syncResult['success']) {
            return redirect()->route('admin.whatsapp.templates.index')
                ->with('success', "WhatsApp Business Account (WABA) ID saved! Successfully synced {$syncResult['count']} templates from Meta.");
        }

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('warning', 'WhatsApp Business Account (WABA) ID saved! Sync attempt message: ' . $syncResult['message']);
    }

    /**
     * Show template creation builder with live bubble preview.
     */
    public function create(): View
    {
        return view('admin.whatsapp.templates.create');
    }

    /**
     * Submit a new template to Meta Graph API for review and approval.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'category' => ['required', 'string', 'in:MARKETING,UTILITY'],
            'language' => ['required', 'string', 'max:10'],
            'header_type' => ['required', 'string', 'in:NONE,TEXT,IMAGE,DOCUMENT'],
            'header_text' => ['nullable', 'string', 'max:60', 'required_if:header_type,TEXT'],
            'body_text' => ['required', 'string', 'max:1024'],
            'footer_text' => ['nullable', 'string', 'max:60'],
            'button_type' => ['nullable', 'string', 'in:NONE,QUICK_REPLY,URL'],
            'button_text' => ['nullable', 'string', 'max:25'],
            'button_url' => ['nullable', 'url', 'max:256'],
        ]);

        if (! $this->whatsappApi->isConfigured()) {
            return back()->withInput()->with('error', 'WhatsApp Cloud API is not configured.');
        }

        // Build Meta Components Array
        $components = [];

        // 1. Header
        if ($validated['header_type'] === 'TEXT') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $validated['header_text'],
            ];
        } elseif ($validated['header_type'] === 'IMAGE') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'IMAGE',
            ];
        } elseif ($validated['header_type'] === 'DOCUMENT') {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'DOCUMENT',
            ];
        }

        // 2. Body
        $components[] = [
            'type' => 'BODY',
            'text' => $validated['body_text'],
        ];

        // 3. Footer
        if (! empty($validated['footer_text'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $validated['footer_text'],
            ];
        }

        // 4. Buttons
        $buttonsList = [];
        if (! empty($validated['button_type']) && $validated['button_type'] !== 'NONE' && ! empty($validated['button_text'])) {
            if ($validated['button_type'] === 'QUICK_REPLY') {
                $buttonsList[] = [
                    'type' => 'QUICK_REPLY',
                    'text' => $validated['button_text'],
                ];
            } elseif ($validated['button_type'] === 'URL' && ! empty($validated['button_url'])) {
                $buttonsList[] = [
                    'type' => 'URL',
                    'text' => $validated['button_text'],
                    'url' => $validated['button_url'],
                ];
            }

            if (! empty($buttonsList)) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $buttonsList,
                ];
            }
        }

        $metaPayload = [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'language' => $validated['language'],
            'components' => $components,
        ];

        $res = $this->whatsappApi->createMessageTemplate($metaPayload);

        if (! $res['success']) {
            return back()->withInput()->with('error', 'Meta Rejected Submission: ' . ($res['message'] ?? 'Unknown error'));
        }

        // Store locally
        WhatsAppTemplate::updateOrCreate(
            [
                'name' => $validated['name'],
                'language' => $validated['language'],
            ],
            [
                'meta_template_id' => $res['template_id'] ?? null,
                'category' => $validated['category'],
                'status' => strtoupper($res['status'] ?? 'PENDING'),
                'header_type' => $validated['header_type'] !== 'NONE' ? $validated['header_type'] : null,
                'header_text' => $validated['header_text'] ?? null,
                'body_text' => $validated['body_text'],
                'footer_text' => $validated['footer_text'] ?? null,
                'buttons' => ! empty($buttonsList) ? $buttonsList : null,
                'synced_at' => now(),
            ]
        );

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', "Template '{$validated['name']}' submitted to Meta! Status: PENDING review.");
    }

    /**
     * Delete template from Meta and local database.
     */
    public function destroy(WhatsAppTemplate $template): RedirectResponse
    {
        $name = $template->name;

        if ($this->whatsappApi->isConfigured()) {
            $this->whatsappApi->deleteMessageTemplate($name);
        }

        $template->delete();

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', "Template '{$name}' deleted from Meta and database.");
    }

    /**
     * API returning approved templates for Live Inbox and Broadcast Campaigns.
     */
    public function apiApproved(): JsonResponse
    {
        $templates = WhatsAppTemplate::where('status', 'APPROVED')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }
}
