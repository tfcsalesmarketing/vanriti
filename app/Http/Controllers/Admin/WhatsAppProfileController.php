<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppProfileController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $whatsappApi
    ) {}

    /**
     * Display the WhatsApp Business Profile & Account Health overview.
     */
    public function index(): View
    {
        $isConfigured = $this->whatsappApi->isConfigured();
        $phoneNumberId = $this->whatsappApi->phoneNumberId();
        $wabaId = (string) (setting('whatsapp_waba_id', '') ?: env('WHATSAPP_WABA_ID', ''));
        $verifyToken = (string) (setting('whatsapp_verify_token', '') ?: env('WHATSAPP_VERIFY_TOKEN', ''));
        $webhookUrl = url('/api/whatsapp/webhook');

        $healthData = null;
        $profileData = null;
        $apiError = null;

        if ($isConfigured) {
            $healthRes = $this->whatsappApi->getPhoneNumberHealth();
            if ($healthRes['success']) {
                $healthData = $healthRes['data'];
            } else {
                $apiError = $healthRes['message'] ?? 'Unable to connect to WhatsApp Cloud API';
            }

            $profileRes = $this->whatsappApi->getBusinessProfile();
            if ($profileRes['success']) {
                $profileData = $profileRes['data'];
            }
        }

        // Available Meta verticals
        $verticals = [
            'APPAREL' => 'Clothing & Apparel',
            'BEAUTY' => 'Beauty, Cosmetic & Personal Care',
            'RETAIL' => 'Retail & E-Commerce',
            'HEALTH' => 'Health & Wellness',
            'GROCERY' => 'Grocery & Organic Food',
            'PROF_SERVICES' => 'Professional Services',
            'OTHER' => 'Other / General Business',
        ];

        return view('admin.whatsapp.profile', [
            'isConfigured' => $isConfigured,
            'phoneNumberId' => $phoneNumberId,
            'wabaId' => $wabaId,
            'verifyToken' => $verifyToken,
            'webhookUrl' => $webhookUrl,
            'healthData' => $healthData,
            'profileData' => $profileData,
            'apiError' => $apiError,
            'verticals' => $verticals,
        ]);
    }

    /**
     * Update the WhatsApp Business Profile on Meta.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'about' => ['nullable', 'string', 'max:139'],
            'description' => ['nullable', 'string', 'max:512'],
            'address' => ['nullable', 'string', 'max:256'],
            'email' => ['nullable', 'email', 'max:128'],
            'website' => ['nullable', 'url', 'max:256'],
            'vertical' => ['nullable', 'string'],
        ]);

        if (! $this->whatsappApi->isConfigured()) {
            return back()->with('error', 'WhatsApp Cloud API credentials are not configured.');
        }

        $websites = [];
        if (! empty($validated['website'])) {
            $websites[] = $validated['website'];
        }

        $metaPayload = [
            'about' => $validated['about'] ?? null,
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'email' => $validated['email'] ?? null,
            'websites' => $websites,
            'vertical' => $validated['vertical'] ?? null,
        ];

        $res = $this->whatsappApi->updateBusinessProfile($metaPayload);

        if ($res['success']) {
            return back()->with('success', 'WhatsApp Business Profile successfully synchronized with Meta!');
        }

        return back()->withInput()->with('error', 'Failed to update profile on Meta: ' . ($res['message'] ?? 'Unknown error'));
    }

    /**
     * Re-sync profile from Meta.
     */
    public function sync(): RedirectResponse
    {
        return redirect()->route('admin.whatsapp.profile')->with('info', 'Profile reloaded from Meta Cloud API.');
    }
}
