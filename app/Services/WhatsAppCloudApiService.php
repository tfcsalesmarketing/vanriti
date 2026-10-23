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

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $langCode],
                'components' => $components,
            ],
        ];

        return $this->postMessage($payload);
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

            return [
                'success' => false,
                'message' => $data['error']['message'] ?? 'Failed to deliver WhatsApp message.',
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
