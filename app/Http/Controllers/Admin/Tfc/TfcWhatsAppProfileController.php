<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Tfc\TfcSetting;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class TfcWhatsAppProfileController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    protected function tfcApi(): WhatsAppCloudApiService
    {
        return $this->apiService->forChannel('tfc');
    }

    /**
     * Display WhatsApp Profile, Cloud API and Floating Widget settings.
     */
    public function index(): View
    {
        $api = $this->tfcApi();
        $isConfigured = $api->isConfigured();
        $accountHealth = $isConfigured ? $api->getPhoneNumberHealth() : ['success' => false, 'data' => null];

        // Gather settings
        $settings = [
            'phone_number_id' => tfc_setting('whatsapp_phone_number_id', '') ?: setting('tfc_whatsapp_phone_number_id', setting('whatsapp_phone_number_id', '')),
            'waba_id' => tfc_setting('whatsapp_waba_id', '') ?: setting('tfc_whatsapp_waba_id', setting('whatsapp_waba_id', '')),
            'has_access_token' => ! empty(tfc_setting('whatsapp_access_token', '') ?: secret_setting('tfc_whatsapp_access_token', secret_setting('whatsapp_access_token', ''))),
            'whatsapp_phone_number' => tfc_setting('whatsapp_phone_number', '+91 98765 43210'),
            'widget_enabled' => (bool) tfc_setting('whatsapp_widget_enabled', true),
            'widget_greeting' => tfc_setting('whatsapp_widget_greeting', 'Hello! Welcome to TFC Sales & Marketing. Have questions about our products or direct selling? Chat with us!'),
            'widget_default_msg' => tfc_setting('whatsapp_widget_default_msg', 'Hello TFC Team, I am interested in learning more about VANRITI botanical formulations and TFC Direct Selling.'),
            'bot_enabled' => (bool) tfc_setting('whatsapp_bot_enabled', true),
        ];

        $webhookUrl = url('/whatsapp/webhook');
        $verifyToken = setting('whatsapp_webhook_verify_token', 'vanriti_whatsapp_secret_token');

        return view('admin.tfc.whatsapp.profile', compact(
            'isConfigured',
            'accountHealth',
            'settings',
            'webhookUrl',
            'verifyToken'
        ));
    }

    /**
     * Update WhatsApp Cloud API and Widget Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_phone_number_id' => ['nullable', 'string', 'max:100'],
            'whatsapp_waba_id' => ['nullable', 'string', 'max:100'],
            'whatsapp_access_token' => ['nullable', 'string', 'max:1000'],
            'whatsapp_phone_number' => ['nullable', 'string', 'max:25'],
            'whatsapp_widget_enabled' => ['nullable', 'boolean'],
            'whatsapp_widget_greeting' => ['nullable', 'string', 'max:255'],
            'whatsapp_widget_default_msg' => ['nullable', 'string', 'max:500'],
            'whatsapp_bot_enabled' => ['nullable', 'boolean'],
        ]);

        $keysToUpdate = [
            'whatsapp_phone_number_id' => $validated['whatsapp_phone_number_id'] ?? '',
            'whatsapp_waba_id' => $validated['whatsapp_waba_id'] ?? '',
            'whatsapp_phone_number' => $validated['whatsapp_phone_number'] ?? '',
            'whatsapp_widget_enabled' => $request->boolean('whatsapp_widget_enabled') ? '1' : '0',
            'whatsapp_widget_greeting' => $validated['whatsapp_widget_greeting'] ?? '',
            'whatsapp_widget_default_msg' => $validated['whatsapp_widget_default_msg'] ?? '',
            'whatsapp_bot_enabled' => $request->boolean('whatsapp_bot_enabled') ? '1' : '0',
        ];

        if ($request->filled('whatsapp_access_token')) {
            $keysToUpdate['whatsapp_access_token'] = Crypt::encryptString(trim($request->input('whatsapp_access_token')));
        }

        foreach ($keysToUpdate as $key => $val) {
            TfcSetting::set($key, $val);
        }

        return back()->with('success', 'TFC WhatsApp settings and Cloud API configuration saved successfully.');
    }

    /**
     * Send instant test WhatsApp message to verify connection.
     */
    public function testConnection(Request $request): RedirectResponse
    {
        $request->validate([
            'test_phone' => ['required', 'string', 'min:10'],
        ]);

        $api = $this->tfcApi();

        if (! $api->isConfigured()) {
            return back()->with('error', 'Cannot send test message: WhatsApp Cloud API is not configured.');
        }

        $phone = $request->input('test_phone');
        $text = "🌿 TFC Sales & Marketing Live Test: WhatsApp Cloud API is connected successfully! Operating in full compliance with Consumer Protection (Direct Selling) Rules.";

        $res = $api->sendTextMessage($phone, $text);

        if ($res['success'] ?? false) {
            return back()->with('success', "Test message sent successfully to {$phone}! Message ID: " . ($res['data']['messages'][0]['id'] ?? 'N/A'));
        }

        return back()->with('error', 'Test Send Failed: ' . ($res['message'] ?? 'Unknown Meta API error.'));
    }
}
