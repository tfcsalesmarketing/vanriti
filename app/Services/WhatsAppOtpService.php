<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsApp (Meta Cloud API) OTP delivery.
 *
 * Mirrors the ShipMojo service conventions: credentials live in the settings
 * table (public value via setting(), secrets via secret_setting()), and the
 * feature is gated behind isEnabled() so nothing half-works.
 *
 * OTP text is sent through a Meta-approved authentication template with a
 * single {{1}} variable for the code. Outside a customer's open 24-hour
 * WhatsApp session, free-form messages will not be delivered, so an approved
 * template is mandatory.
 */
class WhatsAppOtpService
{
    protected string $baseUrl = 'https://graph.facebook.com/v21.0';

    /** Max verify attempts before the code is burnt, hard cap. */
    protected int $maxAttempts = 5;

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.secret_setting('whatsapp_access_token', ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Transport-level check: a Meta phone number id and access token exist.
     * Shared by every message type so no single feature can break the rest.
     */
    public function isConfigured(): bool
    {
        return ! empty(setting('whatsapp_phone_number_id', ''))
            && ! empty(secret_setting('whatsapp_access_token', ''));
    }

    /**
     * OTP messages. The OTP switch and the OTP template are deliberately not
     * shared with other message types: they used to gate order confirmations
     * too, which silently dropped them whenever OTP was off or unconfigured.
     */
    public function isEnabled(): bool
    {
        return $this->isConfigured()
            && (bool) setting('whatsapp_otp_enabled', false)
            && ! empty(setting('whatsapp_template_name', ''));
    }

    /**
     * Order-confirmation messages, independent of the OTP switch.
     */
    public function ordersEnabled(): bool
    {
        return $this->isConfigured()
            && (bool) setting('whatsapp_orders_enabled', true);
    }

    /**
     * Abandoned checkout recovery messages, independent of the OTP switch.
     */
    public function recoveryEnabled(): bool
    {
        return $this->isConfigured()
            && (bool) setting('whatsapp_recovery_enabled', true);
    }

    /**
     * Send a 6-digit OTP to the given WhatsApp number.
     *
     * @return array<string, mixed>
     */
    public function sendOtp(string $phone, string $purpose = 'login'): array
    {
        if (! $this->isEnabled()) {
            return $this->failure('WhatsApp OTP is not enabled or configured yet.');
        }

        try {
            $code = (string) random_int(100000, 999999);
            $phone = canonical_phone($phone) ?? '';

            if ($phone === '') {
                return $this->failure('Please enter a valid 10-digit mobile number.');
            }
            $tooEarly = false;

            // Cooldown: reuse the latest stored OTP within 60s if it is still
            // valid, so users can't hammer the WhatsApp API on "resend".
            $latest = OtpCode::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->latest('id')
                ->first();

            if (
                $latest
                && $latest->expires_at->gt(now()->addSeconds(55))
                && $latest->created_at->gt(now()->subSeconds(60))
            ) {
                return $this->failure('Please wait for the cooldown to expire before requesting a new code.');
            }

            $hashed = Hash::make($code);

            $otp = OtpCode::create([
                'phone' => $phone,
                'purpose' => $purpose,
                'channel' => 'whatsapp',
                'hashed_code' => $hashed,
                'expires_at' => now()->addSeconds((int) setting('whatsapp_otp_ttl_seconds', 120)),
            ]);

            // POST message with the approved authentication template.
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->post($this->baseUrl.'/'.setting('whatsapp_phone_number_id', '').'/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->toE164($phone),
                    'type' => 'template',
                    'template' => [
                        'name' => setting('whatsapp_template_name', ''),
                        'language' => ['code' => 'en'],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $code],
                                ],
                            ],
                            [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => '0',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $code],
                                ],
                            ],
                        ],
                    ],
                ]);

            $body = $response->json();

            if ($response->failed() || ! empty($body['error'] ?? null)) {
                Log::warning('WhatsApp OTP send failed.', [
                    'phone' => $phone,
                    'purpose' => $purpose,
                    'status' => $response->status(),
                    'body' => $body,
                ]);

                return $this->failure('WhatsApp OTP could not be sent right now. Please try again.');
            }

            return ['result' => '1', 'message' => 'OTP sent to your WhatsApp.', 'data' => ['phone' => $phone]];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp OTP send exception.', ['error' => $e->getMessage()]);

            return $this->failure('WhatsApp OTP request failed. Please try again.');
        }
    }

    /**
     * Verify a submitted code against the latest live OTP for the phone/purpose.
     *
     * @return array<string, mixed>
     */
    public function verifyOtp(string $phone, string $code, string $purpose = 'login'): array
    {
        $phone = canonical_phone($phone) ?? '';

        if ($phone === '') {
            return $this->failure('Please enter a valid 10-digit mobile number.');
        }

        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('channel', 'whatsapp')
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return $this->failure('No active OTP found. Please request a new code.');
        }

        if ($otp->attempts >= $this->maxAttempts) {
            $otp->update(['used_at' => now()]);

            return $this->failure('Too many incorrect attempts. Please request a new code.');
        }

        if ($otp->expires_at->lt(now())) {
            $otp->update(['used_at' => now()]);

            return $this->failure('This code has expired. Please request a new one.');
        }

        if (! Hash::check($code, $otp->hashed_code)) {
            $otp->increment('attempts');

            return $this->failure('The code you entered is incorrect.');
        }

        $otp->update(['used_at' => now()]);

        return ['result' => '1', 'message' => 'OTP verified.', 'data' => ['phone' => $phone]];
    }

    /**
     * Track how long before the next send is allowed for the phone (seconds).
     */
    public function cooldownRemaining(string $phone, string $purpose = 'login'): int
    {
        $latest = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $remaining = (int) $latest->created_at->diffInSeconds(now()) <= 60
            ? 60 - $latest->created_at->diffInSeconds(now())
            : 0;

        return max(0, $remaining);
    }

    protected function toE164(string $phone): string
    {
        $phone = Str::of($phone)->replace([' ', '-', '+'], '')->trim()->toString();

        return Str::startsWith($phone, '91') ? $phone : '91'.$phone;
    }

    /**
     * Send a welcome message via the customer-care approved WhatsApp template.
     * The template body has exactly one {{ variable }} - the customer's name -
     * invoking the same single-variable contract as the approved OTP template.
     *
     * @return array<string, mixed>
     */
    public function sendWelcome(string $phone, string $name = ''): array
    {
        if (! $this->isEnabled()) {
            return $this->failure('WhatsApp is not enabled or configured yet.');
        }

        $template = (string) setting('whatsapp_welcome_template_name', '');

        if ($template === '') {
            return $this->failure('WhatsApp welcome template is not configured yet.');
        }

        $phone = $this->toE164($phone);
        $name = trim($name) !== '' ? trim($name) : 'Customer';

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(20)
                ->post(
                    $this->baseUrl.'/'.setting('whatsapp_phone_number_id', '').'/messages',
                    [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $phone,
                        'type' => 'template',
                        'template' => [
                            'name' => $template,
                            'language' => ['code' => 'en'],
                            'components' => [
                                [
                                    'type' => 'body',
                                    'parameters' => [
                                        [
                                            'type' => 'text',
                                            'text' => $name,
                                            'parameter_name' => 'name',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                );

            $body = $response->json();

            if ($response->failed() || ! empty($body['error'] ?? null)) {
                Log::warning('WhatsApp welcome send failed.', [
                    'phone' => $phone,
                    'purpose' => 'welcome',
                    'status' => $response->status(),
                    'body' => $body,
                ]);

                return $this->failure('WhatsApp welcome could not be sent right now. Please try again.');
            }

            // Record in conversation stream so it shows in chat with customer
            try {
                $cleanPhone = ltrim($phone, '+');
                $conversation = \App\Models\WhatsAppConversation::firstOrCreate(
                    ['phone' => $cleanPhone],
                    [
                        'contact_name' => $name !== 'Customer' ? $name : null,
                        'status' => 'active',
                    ]
                );

                $templateModel = \App\Models\WhatsAppTemplate::where('name', $template)->first();
                $renderedBody = $templateModel && ! empty($templateModel->body_text)
                    ? str_replace(['{{1}}', '{1}'], $name, $templateModel->body_text)
                    : "🌿 Welcome to VANRITI! Hello {$name}, welcome to pure Ayurvedic skincare and wellness.";

                \App\Models\WhatsAppMessage::create([
                    'whatsapp_conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'message_type' => 'template',
                    'body' => $renderedBody,
                    'meta_message_id' => $body['messages'][0]['id'] ?? null,
                    'status' => 'sent',
                    'metadata' => [
                        'template_name' => $template,
                        'parameters' => [$name],
                        'is_welcome' => true,
                    ],
                    'sent_at' => now(),
                ]);

                $conversation->update([
                    'last_message_at' => now(),
                    'last_message_preview' => Str::limit($renderedBody, 90),
                ]);
            } catch (\Throwable $ex) {
                Log::warning('Failed to record welcome WhatsApp message in inbox conversation', [
                    'phone' => $phone,
                    'error' => $ex->getMessage(),
                ]);
            }

            return ['result' => '1', 'message' => 'WhatsApp welcome sent.', 'data' => ['phone' => $phone]];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp welcome request failed.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('WhatsApp welcome request failed: '.$e->getMessage());
        }
    }

    /**
     * Order-confirmation WhatsApp message (approved template, three body
     * variables: customer name, order number, estimated delivery date; plus a
     * dynamic URL button that carries the dashboard order link).
     *
     * The button URL is supplied in full by the caller, so the approved
     * template's button only needs a single {{1}} placeholder.
     *
     * Best-effort: any failure logs a warning and must never block the order.
     *
     * @return array{result: string, message: string}
     */
    public function sendOrderConfirmation(string $phone, string $name, string $orderNumber, string $deliveryDate, string $orderUrl): array
    {
        if (! $this->ordersEnabled()) {
            return $this->failure('WhatsApp order confirmation is not enabled or configured yet.');
        }

        $template = (string) setting('whatsapp_order_template_name', 'order_confirmation');

        if ($template === '') {
            return $this->failure('WhatsApp order confirmation template is not configured yet.');
        }

        $phone = $this->toE164($phone);

        $orderUrlSuffix = ltrim((string) parse_url($orderUrl, PHP_URL_PATH), '/');
        if ($query = parse_url($orderUrl, PHP_URL_QUERY)) {
            $orderUrlSuffix .= '?' . $query;
        }
        if ($orderUrlSuffix === '') {
            $orderUrlSuffix = 'account/orders';
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(8)
                ->post(
                    $this->baseUrl.'/'.setting('whatsapp_phone_number_id', '').'/messages',
                    [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $phone,
                        'type' => 'template',
                        'template' => [
                            'name' => $template,
                            'language' => ['code' => 'en'],
                            'components' => [
                                [
                                    'type' => 'body',
                                    'parameters' => [
                                        ['type' => 'text', 'text' => $name],
                                        ['type' => 'text', 'text' => $orderNumber],
                                        // Dates are passed as plain text (ISO) in the body.
                                        ['type' => 'text', 'text' => $deliveryDate],
                                    ],
                                ],
                                [
                                    // The template's single URL button is a bare
                                    // {{1}} placeholder, so the full link comes
                                    // from us: the customer's dashboard order page.
                                    'type' => 'button',
                                    'sub_type' => 'url',
                                    'index' => '0',
                                    'parameters' => [
                                        ['type' => 'url', 'url' => $orderUrl],
                                    ],
                                ],
                            ],
                        ],
                    ]
                );

            $body = $response->json();

            if ($response->failed() || ! empty($body['error'] ?? null)) {
                Log::warning('WhatsApp order confirmation send failed.', [
                    'phone' => $phone,
                    'order' => $orderNumber,
                    'status' => $response->status(),
                    'body' => $body,
                ]);

                return $this->failure('WhatsApp order confirmation could not be sent right now. Please try again.');
            }

            return ['result' => '1', 'message' => 'WhatsApp order confirmation sent.', 'data' => ['phone' => $phone, 'order' => $orderNumber]];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp order confirmation request failed.', [
                'phone' => $phone,
                'order' => $orderNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('WhatsApp order confirmation request failed: '.$e->getMessage());
        }
    }

    /**
     * Abandoned-checkout recovery WhatsApp message (Meta approved template).
     *
     * Automatically adapts to whether the approved Meta template has:
     * - 1, 2, or 3 body parameters
     * - A dynamic URL button vs static / no button
     * - Language code 'en' vs 'en_US'
     *
     * @return array{result: string, message: string}
     */
    public function sendAbandonedCheckoutRecovery(
        string $phone,
        string $name,
        string $orderNumber,
        string $totalAmount,
        string $checkoutUrl,
        ?string $productName = null,
        ?string $imageUrl = null
    ): array {
        if (! $this->recoveryEnabled()) {
            return $this->failure('WhatsApp checkout recovery is not enabled or configured yet.');
        }

        $template = (string) setting('whatsapp_recovery_template_name', 'checkout_recovery');

        if ($template === '') {
            return $this->failure('WhatsApp checkout recovery template is not configured yet.');
        }

        $phone = $this->toE164($phone);
        $name = trim($name) !== '' ? trim($name) : 'Customer';
        $productName = trim((string) $productName);

        $urlSuffix = ltrim((string) parse_url($checkoutUrl, PHP_URL_PATH), '/');
        if ($query = parse_url($checkoutUrl, PHP_URL_QUERY)) {
            $urlSuffix .= '?' . $query;
        }
        if ($urlSuffix === '') {
            $urlSuffix = 'cart';
        }

        // Meta strictly requires image/jpeg or image/png for image headers (WebP is rejected by Meta)
        $hasCompatibleFormat = ! empty($imageUrl) && (str_contains(strtolower($imageUrl), '.jpg') || str_contains(strtolower($imageUrl), '.jpeg') || str_contains(strtolower($imageUrl), '.png'));
        $isPublicHttps = ! empty($imageUrl) && str_starts_with($imageUrl, 'https://') && ! str_contains($imageUrl, 'localhost') && ! str_contains($imageUrl, '127.0.0.1');

        if ($isPublicHttps && $hasCompatibleFormat) {
            $resolvedImageUrl = $imageUrl;
        } else {
            // Default high-res JPEG brand fallback (guaranteed compliant for Meta)
            $resolvedImageUrl = 'https://vanriti.com/images/logo.jpg';
        }
        $productText = $productName !== '' ? $productName : $name;

        $postTemplate = function (string $lang, array $bodyParams, bool $withHeader, bool $withButton) use ($template, $phone, $urlSuffix, $resolvedImageUrl) {
            $components = [];

            // 1. Header Media (Image)
            if ($withHeader) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'image',
                            'image' => [
                                'link' => $resolvedImageUrl,
                            ],
                        ],
                    ],
                ];
            }

            // 2. Body Parameters
            if (! empty($bodyParams)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $bodyParams,
                ];
            }

            // 3. Dynamic URL Button (only if template has dynamic url button)
            if ($withButton) {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text', 'text' => $urlSuffix],
                    ],
                ];
            }

            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->post(
                    $this->baseUrl.'/' . setting('whatsapp_phone_number_id', '') . '/messages',
                    [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $phone,
                        'type' => 'template',
                        'template' => [
                            'name' => $template,
                            'language' => ['code' => $lang],
                            'components' => $components,
                        ],
                    ]
                );

            return [$response, $response->json()];
        };

        // Candidate payload variations to match Meta template definitions
        $configsToTry = [
            // 1. Exact match for checkout_recovery: Image header, named "text" param, static URL button (no button params)
            ['withHeader' => true, 'withButton' => false, 'bodyParams' => [['type' => 'text', 'parameter_name' => 'text', 'text' => $productText]]],
            // 2. Positional single param: Image header, static URL button
            ['withHeader' => true, 'withButton' => false, 'bodyParams' => [['type' => 'text', 'text' => $productText]]],
            // 3. Named param with dynamic URL button
            ['withHeader' => true, 'withButton' => true, 'bodyParams' => [['type' => 'text', 'parameter_name' => 'text', 'text' => $productText]]],
            // 4. Positional single param with dynamic URL button
            ['withHeader' => true, 'withButton' => true, 'bodyParams' => [['type' => 'text', 'text' => $productText]]],
            // 5. Classic 3-param template: Name, Total, Order Ref with dynamic button
            ['withHeader' => false, 'withButton' => true, 'bodyParams' => [
                ['type' => 'text', 'text' => $name],
                ['type' => 'text', 'text' => $totalAmount],
                ['type' => 'text', 'text' => $orderNumber],
            ]],
            // 6. Classic 3-param template without button
            ['withHeader' => false, 'withButton' => false, 'bodyParams' => [
                ['type' => 'text', 'text' => $name],
                ['type' => 'text', 'text' => $totalAmount],
                ['type' => 'text', 'text' => $orderNumber],
            ]],
            // 7. Header image only, no body params
            ['withHeader' => true, 'withButton' => false, 'bodyParams' => []],
        ];

        $lastBody = null;

        // Try languages: 'en' first (Meta default for Indian accounts), then 'en_US'
        foreach (['en', 'en_US'] as $lang) {
            foreach ($configsToTry as $cfg) {
                [$resp, $body] = $postTemplate($lang, $cfg['bodyParams'], $cfg['withHeader'], $cfg['withButton']);
                $lastBody = $body;

                if ($resp->successful() && empty($body['error'])) {
                    return [
                        'result' => '1',
                        'message' => 'WhatsApp checkout recovery sent.',
                        'data' => ['phone' => $phone, 'order' => $orderNumber],
                    ];
                }

                $errCode = $body['error']['code'] ?? null;

                // If template does not exist in this language at all, immediately switch language
                if ($errCode === 132001) {
                    break;
                }
            }
        }

        $errMsg = $lastBody['error']['message'] ?? 'API error';
        $details = $lastBody['error']['error_data']['details'] ?? '';
        $fullMsg = $details ? "{$errMsg} ({$details})" : $errMsg;

        Log::warning('WhatsApp checkout recovery send failed.', [
            'phone' => $phone,
            'order' => $orderNumber,
            'body' => $lastBody,
        ]);

        return $this->failure('WhatsApp recovery could not be sent: ' . $fullMsg);
    }

    protected function failure(string $message): array
    {
        return ['result' => '0', 'message' => $message];
    }
}
