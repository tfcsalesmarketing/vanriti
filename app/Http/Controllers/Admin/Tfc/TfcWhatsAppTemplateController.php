<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcWhatsAppTemplateController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    protected function tfcApi(): WhatsAppCloudApiService
    {
        return $this->apiService->forChannel('tfc');
    }

    /**
     * Display templates explorer.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = WhatsAppTemplate::where('channel', 'tfc')->orderBy('name');

        if ($status !== 'all') {
            $query->where('status', strtoupper($status));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('body_text', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => WhatsAppTemplate::where('channel', 'tfc')->count(),
            'approved' => WhatsAppTemplate::where('channel', 'tfc')->where('status', 'APPROVED')->count(),
            'pending' => WhatsAppTemplate::where('channel', 'tfc')->where('status', 'PENDING')->count(),
            'rejected' => WhatsAppTemplate::where('channel', 'tfc')->where('status', 'REJECTED')->count(),
        ];

        return view('admin.tfc.whatsapp.templates.index', compact('templates', 'stats', 'status', 'search'));
    }

    /**
     * Synchronize templates with Meta Cloud API.
     */
    public function sync(): RedirectResponse
    {
        $api = $this->tfcApi();

        if (! $api->isConfigured()) {
            return back()->with('error', 'WhatsApp Cloud API is not configured. Please enter your WABA ID and Access Token in TFC WhatsApp Settings.');
        }

        $wabaId = $api->wabaId();
        if (empty($wabaId)) {
            return back()->with('error', 'WhatsApp Business Account (WABA) ID is not configured for TFC. Please enter your TFC WABA ID in TFC WhatsApp Settings.');
        }

        $result = $api->getMessageTemplates();

        if (! ($result['success'] ?? false)) {
            return back()->with('error', 'Meta Sync Failed: ' . ($result['message'] ?? 'Could not retrieve templates from Meta.'));
        }

        $metaTemplates = $result['data'] ?? [];
        $syncedCount = 0;

        foreach ($metaTemplates as $metaTmpl) {
            $name = (string) ($metaTmpl['name'] ?? '');
            $lang = (string) ($metaTmpl['language'] ?? 'en');

            if (empty($name)) {
                continue;
            }

            $components = $metaTmpl['components'] ?? [];
            $headerText = null;
            $headerType = null;
            $bodyText = '';
            $footerText = null;
            $buttons = [];

            foreach ($components as $comp) {
                $compType = strtoupper($comp['type'] ?? '');
                if ($compType === 'HEADER') {
                    $headerType = $comp['format'] ?? 'TEXT';
                    $headerText = $comp['text'] ?? null;
                } elseif ($compType === 'BODY') {
                    $bodyText = (string) ($comp['text'] ?? '');
                } elseif ($compType === 'FOOTER') {
                    $footerText = $comp['text'] ?? null;
                } elseif ($compType === 'BUTTONS') {
                    $buttons = $comp['buttons'] ?? [];
                }
            }

            WhatsAppTemplate::updateOrCreate(
                ['name' => $name, 'language' => $lang, 'channel' => 'tfc'],
                [
                    'meta_template_id' => $metaTmpl['id'] ?? null,
                    'category' => $metaTmpl['category'] ?? 'MARKETING',
                    'channel' => 'tfc',
                    'status' => strtoupper($metaTmpl['status'] ?? 'PENDING'),
                    'header_type' => $headerType,
                    'header_text' => $headerText,
                    'body_text' => $bodyText,
                    'footer_text' => $footerText,
                    'buttons' => $buttons,
                    'rejected_reason' => $metaTmpl['rejected_reason'] ?? null,
                    'synced_at' => now(),
                ]
            );

            $syncedCount++;
        }

        return back()->with('success', "Successfully synced {$syncedCount} TFC templates from Meta Cloud API!");
    }
}
