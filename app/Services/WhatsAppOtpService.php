<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;
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

    public function isEnabled(): bool
    {
        return (bool) setting('whatsapp_otp_enabled', false)
            && ! empty(setting('whatsapp_phone_number_id', ''))
            && ! empty(secret_setting('whatsapp_access_token', ''))
            && ! empty(setting('whatsapp_template_name', ''));
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
            $phone = Str::of($phone)->replace([' ', '-'], '')->trim()->toString();
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

            return $this->failure('WhatsApp OTP request failed: '.$e->getMessage());
        }
    }

    /**
     * Verify a submitted code against the latest live OTP for the phone/purpose.
     *
     * @return array<string, mixed>
     */
    public function verifyOtp(string $phone, string $code, string $purpose = 'login'): array
    {
        $phone = Str::of($phone)->replace([' ', '-'], '')->trim()->toString();

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
     * dynamic URL button that carries the signed "track my order" link).
     *
     * Best-effort: any failure logs a warning and must never block the order.
     *
     * @return array{result: string, message: string}
     */
    public function sendOrderConfirmation(string $phone, string $name, string $orderNumber, string $deliveryDate, string $trackUrl): array
    {
        if (! $this->isEnabled()) {
            return $this->failure('WhatsApp is not enabled or configured yet.');
        }

        $template = (string) setting('whatsapp_order_template_name', 'order_confirmation');

        if ($template === '') {
            return $this->failure('WhatsApp order confirmation template is not configured yet.');
        }

        $phone = $this->toE164($phone);

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
                                        ['type' => 'text', 'text' => $name],
                                        ['type' => 'text', 'text' => $orderNumber],
                                        // Dates are passed as plain text (ISO) in the body.
                                        ['type' => 'text', 'text' => $deliveryDate],
                                    ],
                                ],
                                [
                                    // The template's single URL button ("Track My Order")
                                    // points at the signed, login-free order page.
                                    'type' => 'button',
                                    'sub_type' => 'url',
                                    'index' => '0',
                                    'parameters' => [
                                        ['type' => 'url', 'url' => $trackUrl],
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

    protected function failure(string $message): array
    {
        return ['result' => '0', 'message' => $message];
    }
}
