<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
    protected string $baseUrl = 'https://graph.facebook.com/v21.0';

    public function accessToken(): string
    {
        return (string) (secret_setting('whatsapp_access_token', '') ?: env('WHATSAPP_ACCESS_TOKEN', ''));
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function phoneNumberId(): string
    {
        return (string) (setting('whatsapp_phone_number_id', '') ?: env('WHATSAPP_PHONE_NUMBER_ID', ''));
    }

    public function wabaId(): string
    {
        return (string) (setting('whatsapp_waba_id', '') ?: env('WHATSAPP_WABA_ID', ''));
    }

    public function isConfigured(): bool
    {
        return ! empty($this->phoneNumberId())
            && ! empty($this->accessToken());
    }

    /**
     * Verify incoming webhook HMAC-SHA256 signature from X-Hub-Signature-256 header.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $appSecret = (string) (env('WHATSAPP_APP_SECRET') ?: secret_setting('whatsapp_app_secret', ''));

        // If no app secret configured yet, return true in local/testing or log warning
        if ($appSecret === '') {
            return true;
        }

        if (! $signatureHeader) {
            return false;
        }

        $expectedHash = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);

        $matches = hash_equals($expectedHash, $signatureHeader);

        if (! $matches) {
            Log::warning('WhatsApp webhook signature mismatch', [
                'received' => substr((string) $signatureHeader, 0, 16) . '...',
                'secret_len' => strlen($appSecret),
            ]);
        }

        return $matches;
    }

    /**
     * Send plain text message.
     */
    public function sendTextMessage(string $to, string $text): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $to = $this->formatRecipientPhone($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send interactive button reply message (up to 3 buttons).
     *
     * @param  array<int, array{id: string, title: string}>  $buttons
     */
    public function sendInteractiveButtonMessage(
        string $to,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): array {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $to = $this->formatRecipientPhone($to);

        $actionButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) $btn['id'],
                    'title' => (string) \Illuminate\Support\Str::limit($btn['title'], 20),
                ],
            ];
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => ['buttons' => $actionButtons],
        ];

        if ($headerText) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => \Illuminate\Support\Str::limit($headerText, 60),
            ];
        }

        if ($footerText) {
            $interactive['footer'] = [
                'text' => \Illuminate\Support\Str::limit($footerText, 60),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send catalogue message or multi-product message if catalog_id is configured,
     * otherwise falls back to a curated text showcase with direct shopping link.
     */
    public function sendCatalogueMessage(string $to, ?string $bodyText = null): array
    {
        $catalogId = (string) setting('whatsapp_catalog_id', '');
        $to = $this->formatRecipientPhone($to);

        $defaultBody = $bodyText ?: "Welcome to VANRITI! 🌿\nExplore our pure, natural skincare and wellness collection.";

        if (! empty($catalogId)) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'catalog_message',
                    'body' => ['text' => $defaultBody],
                    'action' => [
                        'name' => 'catalog_message',
                        'parameters' => [
                            'catalog_id' => $catalogId,
                        ],
                    ],
                    'footer' => ['text' => 'Tap above to browse products'],
                ],
            ];

            $res = $this->postMessage($payload);
            if ($res['success']) {
                return $res;
            }
        }

        // Fallback: Send message with catalogue link and invitation
        $fallbackText = "Welcome to VANRITI! 🌿\n\n"
            . "Discover our Ayurvedic & Natural Skincare collection.\n"
            . "You can browse our full range here:\n"
            . url('/shop') . "\n\n"
            . "To order directly on WhatsApp, send us the product name or SKU, or use our WhatsApp catalogue!";

        return $this->sendTextMessage($to, $fallbackText);
    }

    /**
     * Send Meta template message.
     */
    public function sendTemplateMessage(string $to, string $templateName, string $langCode = 'en', array $components = []): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $to = $this->formatRecipientPhone($to);

        $templateData = [
            'name' => $templateName,
            'language' => ['code' => $langCode],
        ];

        if (! empty($components)) {
            $templateData['components'] = array_values($components);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => $templateData,
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send rich media message (image, document, audio, video).
     */
    public function sendMediaMessage(
        string $to,
        string $type,
        string $mediaIdOrUrl,
        ?string $caption = null,
        ?string $filename = null
    ): array {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $to = $this->formatRecipientPhone($to);
        $isUrl = str_starts_with($mediaIdOrUrl, 'http://') || str_starts_with($mediaIdOrUrl, 'https://');

        $mediaPayload = $isUrl ? ['link' => $mediaIdOrUrl] : ['id' => $mediaIdOrUrl];

        if ($caption !== null && in_array($type, ['image', 'document', 'video'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        if ($filename !== null && $type === 'document') {
            $mediaPayload['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type,
            $type => $mediaPayload,
        ];

        return $this->postMessage($payload);
    }

    /**
     * Upload media file to Meta Graph API media endpoint.
     * Returns array with 'id' on success.
     */
    public function uploadMedia(string $filePath, string $mimeType): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        if (! file_exists($filePath)) {
            return ['success' => false, 'message' => 'Local media file not found.'];
        }

        try {
            $fileContents = file_get_contents($filePath);
            $fileName = basename($filePath);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken(),
            ])
                ->timeout(30)
                ->attach('file', $fileContents, $fileName, ['Content-Type' => $mimeType])
                ->post($this->baseUrl . '/' . $this->phoneNumberId() . '/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            $data = $response->json();

            if ($response->successful() && ! empty($data['id'])) {
                return [
                    'success' => true,
                    'media_id' => $data['id'],
                ];
            }

            Log::warning('WhatsApp media upload failed', ['response' => $data]);
            return [
                'success' => false,
                'message' => $data['error']['message'] ?? 'Failed to upload media to WhatsApp.',
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp media upload exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Retrieve temporary media download URL from Meta.
     */
    public function getMediaDownloadUrl(string $mediaId): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $res = Http::withHeaders($this->headers())
                ->timeout(12)
                ->get($this->baseUrl . '/' . $mediaId);

            if ($res->successful()) {
                return $res->json('url');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch WhatsApp media download URL', ['media_id' => $mediaId, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Download binary media stream from Meta URL.
     */
    public function downloadMediaBinary(string $downloadUrl): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken(),
            ])
                ->timeout(30)
                ->get($downloadUrl);

            if ($res->successful()) {
                return $res->body();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to download WhatsApp media binary', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function postMessage(array $payload): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(12)
                ->post($this->baseUrl . '/' . $this->phoneNumberId() . '/messages', $payload);

            $data = $response->json();

            if ($response->successful() && ! isset($data['error'])) {
                $msgId = $data['messages'][0]['id'] ?? null;
                return [
                    'success' => true,
                    'message_id' => $msgId,
                    'data' => $data,
                ];
            }

            Log::warning('WhatsApp Cloud API send failed', [
                'status' => $response->status(),
                'payload' => $payload,
                'response' => $data,
            ]);

            $errorDetail = $data['error']['error_data']['details'] ?? null;
            $msg = $data['error']['message'] ?? 'Failed to deliver WhatsApp message.';
            if ($errorDetail) {
                $msg .= ' (' . $errorDetail . ')';
            }

            return [
                'success' => false,
                'message' => $msg,
                'error' => $data['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Cloud API exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get WhatsApp Business Profile from Meta Graph API.
     * https://developers.facebook.com/docs/whatsapp/cloud-api/reference/whatsapp-business-profile
     */
    public function getBusinessProfile(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'WhatsApp Cloud API is not configured.',
                'data' => null,
            ];
        }

        try {
            $fields = 'about,address,description,email,profile_picture_url,websites,vertical';
            $response = Http::withHeaders($this->headers())
                ->timeout(12)
                ->get($this->baseUrl . '/' . $this->phoneNumberId() . '/whatsapp_business_profile', [
                    'fields' => $fields,
                ]);

            if ($response->successful()) {
                $payload = $response->json();
                $profile = $payload['data'][0] ?? [];

                return [
                    'success' => true,
                    'data' => $profile,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message') ?? 'Failed to fetch WhatsApp Business Profile.',
                'data' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch WhatsApp Business Profile', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Update WhatsApp Business Profile on Meta.
     */
    public function updateBusinessProfile(array $data): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        try {
            $payload = array_filter([
                'messaging_product' => 'whatsapp',
                'about' => $data['about'] ?? null,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
                'email' => $data['email'] ?? null,
                'websites' => ! empty($data['websites']) ? array_values(array_filter((array) $data['websites'])) : null,
                'vertical' => $data['vertical'] ?? null,
            ], fn($v) => ! is_null($v));

            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post($this->baseUrl . '/' . $this->phoneNumberId() . '/whatsapp_business_profile', $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message') ?? 'Failed to update WhatsApp Business Profile on Meta.',
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to update WhatsApp Business Profile', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get Phone Number Account Health, Tier, and Quality Rating from Meta.
     */
    public function getPhoneNumberHealth(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'WhatsApp Cloud API is not configured.',
                'data' => null,
            ];
        }

        try {
            $fields = 'verified_name,code_verification_status,display_phone_number,quality_rating,messaging_limit_tier,status,is_official_business_account';
            $response = Http::withHeaders($this->headers())
                ->timeout(12)
                ->get($this->baseUrl . '/' . $this->phoneNumberId(), [
                    'fields' => $fields,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message') ?? 'Failed to retrieve phone number health.',
                'data' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to retrieve phone number health', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Fetch all WhatsApp message templates registered under the WABA.
     */
    public function getMessageTemplates(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.', 'data' => []];
        }

        $wabaId = $this->wabaId();
        if (empty($wabaId)) {
            return ['success' => false, 'message' => 'WhatsApp Business Account (WABA) ID is not configured.', 'data' => []];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get($this->baseUrl . '/' . $wabaId . '/message_templates', [
                    'fields' => 'id,name,status,category,language,components,quality_score,rejected_reason',
                    'limit' => 100,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json('data') ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message') ?? 'Failed to fetch message templates from Meta.',
                'data' => [],
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp fetch templates exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Submit a new message template to Meta for review and approval.
     */
    public function createMessageTemplate(array $payload): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $wabaId = $this->wabaId();
        if (empty($wabaId)) {
            return ['success' => false, 'message' => 'WhatsApp Business Account (WABA) ID is not configured.'];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->post($this->baseUrl . '/' . $wabaId . '/message_templates', $payload);

            $data = $response->json();
            if ($response->successful() && ! empty($data['id'])) {
                return [
                    'success' => true,
                    'template_id' => $data['id'],
                    'status' => $data['status'] ?? 'PENDING',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => $data['error']['message'] ?? 'Failed to submit template to Meta.',
                'error' => $data['error'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp template creation exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a template from Meta WABA.
     */
    public function deleteMessageTemplate(string $templateName): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured.'];
        }

        $wabaId = $this->wabaId();
        if (empty($wabaId)) {
            return ['success' => false, 'message' => 'WABA ID is not configured.'];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->delete($this->baseUrl . '/' . $wabaId . '/message_templates', [
                    'name' => $templateName,
                ]);

            if ($response->successful() && $response->json('success')) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message') ?? 'Failed to delete template from Meta.',
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp template deletion exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format phone number to clean digits with country code (defaults to India 91).
     */
    public function formatRecipientPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clean) === 10) {
            return '91' . $clean;
        }

        if (strlen($clean) === 11 && str_starts_with($clean, '0')) {
            return '91' . substr($clean, 1);
        }

        return $clean;
    }
}
