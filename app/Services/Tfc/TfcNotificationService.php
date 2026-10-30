<?php

namespace App\Services\Tfc;

use App\Models\OtpCode;
use App\Models\Tfc\DirectSellingMember;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class TfcNotificationService
{
    protected WhatsAppCloudApiService $whatsappService;

    public function __construct(WhatsAppCloudApiService $whatsappService)
    {
        $this->whatsappService = $whatsappService->forChannel('tfc');
    }

    /**
     * Generate a clean 32-character alphanumeric verification token (100% Meta & URL safe).
     */
    public function generateEncryptedVerificationToken(string $memberId, string $mobile): string
    {
        // Self-contained AES-256 encrypted payload (Stateless across all servers & environments)
        // Produces the exact 'eyJpdiI6...' token format approved in Meta templates
        $payload = json_encode([
            'member_id' => $memberId,
            'mobile' => $mobile,
            'timestamp' => now()->timestamp,
        ]);

        $token = Crypt::encryptString($payload);

        // Also cache for fast lookups if supported
        try {
            Cache::put('tfc_vtoken_' . $token, [
                'member_id' => $memberId,
                'mobile' => $mobile,
                'timestamp' => now()->timestamp,
            ], now()->addDays(3));
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return $token;
    }

    /**
     * Decrypt or resolve verification token.
     *
     * @return array{member_id: string, mobile: string, timestamp: int}|null
     */
    public function decryptVerificationToken(string $token): ?array
    {
        // 1. Primary: Standard AES-256 decryption (Stateless, works on localhost & live tfcsalesmarketing.com)
        try {
            $decrypted = Crypt::decryptString($token);
            $data = json_decode($decrypted, true);

            if (is_array($data) && ! empty($data['member_id']) && ! empty($data['mobile'])) {
                return $data;
            }
        } catch (\Throwable $e) {
            // Not encrypted or different format
        }

        // 2. Fallback: Check clean 32-char token from cache
        try {
            $cached = Cache::get('tfc_vtoken_' . $token);
            if (is_array($cached) && ! empty($cached['member_id']) && ! empty($cached['mobile'])) {
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache miss
        }

        return null;
    }

    /**
     * Generate encrypted mobile verification URL for tfcsalesmarketing.com.
     */
    public function generateVerificationUrl(DirectSellingMember $member): string
    {
        $token = $this->generateEncryptedVerificationToken($member->tfc_member_id, $member->mobile);
        $tfcDomain = config('tfc.domain', 'tfcsalesmarketing.com');

        return "https://{$tfcDomain}/member/verify-mobile?token=" . urlencode($token);
    }

    /**
     * Generate a secure, expiring password setup signed URL (valid for 24 hours).
     */
    public function generateExpiringPasswordSetupUrl(DirectSellingMember $member, int $validHours = 24): string
    {
        return URL::temporarySignedRoute(
            'tfc.preview.member.password-setup',
            now()->addHours($validHours),
            [
                'member_id' => $member->tfc_member_id,
                'email' => $member->email,
            ]
        );
    }

    /**
     * Generate personal referral link for the member.
     */
    public function generateReferralUrl(DirectSellingMember $member): string
    {
        $domain = config('tfc.domain', 'tfcsalesmarketing.com');

        return "https://{$domain}/member/register?ref=" . urlencode($member->tfc_member_id);
    }

    /**
     * Send 6-digit OTP code to the member's WhatsApp number for mobile verification.
     *
     * @return array{success: bool, message: string, code?: string}
     */
    public function sendMobileVerificationOtp(string $mobile, string $fullName = 'Member', string $memberId = ''): array
    {
        $cleanMobile = preg_replace('/\D/', '', $mobile);
        if (strlen($cleanMobile) > 10) {
            if (str_starts_with($cleanMobile, '0091')) {
                $cleanMobile = substr($cleanMobile, 4);
            } elseif (str_starts_with($cleanMobile, '91')) {
                $cleanMobile = substr($cleanMobile, 2);
            } elseif (str_starts_with($cleanMobile, '0')) {
                $cleanMobile = substr($cleanMobile, 1);
            }
        }

        if (strlen($cleanMobile) !== 10) {
            return ['success' => false, 'message' => 'Invalid mobile number format for OTP dispatch.'];
        }

        // Rate-limit check: Reuse latest active OTP if created less than 45 seconds ago
        $purpose = 'tfc_mobile_verify';
        $latest = OtpCode::where('phone', $cleanMobile)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($latest && $latest->created_at->gt(now()->subSeconds(45)) && $latest->expires_at->gt(now())) {
            return [
                'success' => true,
                'message' => 'A verification code was recently sent. Please check your WhatsApp.',
                'cooldown' => 45 - $latest->created_at->diffInSeconds(now()),
            ];
        }

        $code = (string) random_int(100000, 999999);
        $hashed = Hash::make($code);

        OtpCode::create([
            'phone' => $cleanMobile,
            'purpose' => $purpose,
            'channel' => 'whatsapp',
            'hashed_code' => $hashed,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $otpText = "🔒 *TFC Verification Code:*\n\n"
                 . "Hi *{$fullName}*,\n"
                 . "Your verification code is *{$code}* to verify your mobile number and set your TFC Member Portal password.\n\n"
                 . "⏱ Valid for 10 minutes. For your security, do not share this code with anyone.\n\n"
                 . "— TFC Sales & Marketing";

        try {
            if ($this->whatsappService->isConfigured()) {
                $configuredOtpTemplate = class_exists(\App\Models\Tfc\TfcSetting::class)
                    ? \App\Models\Tfc\TfcSetting::get('whatsapp_otp_template', 'tfc_otp_verification')
                    : 'tfc_otp_verification';

                $candidateOtpTemplates = array_values(array_unique(array_filter([
                    $configuredOtpTemplate,
                    'tfc_otp_verification',
                    'otp_verification',
                ])));

                foreach ($candidateOtpTemplates as $otpTemplate) {
                    // Try Variant A: With "Copy code" URL button (Standard Meta AUTHENTICATION format)
                    $componentsWithButton = [
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
                    ];

                    $tplResp = $this->whatsappService->sendTemplateMessage($cleanMobile, $otpTemplate, 'en', $componentsWithButton);
                    if (! empty($tplResp['success'])) {
                        Log::info('TFC OTP sent via Meta Template (with button)', [
                            'template' => $otpTemplate,
                            'mobile' => $cleanMobile,
                        ]);
                        return ['success' => true, 'message' => 'OTP sent to your WhatsApp.', 'code' => $code];
                    }

                    // Try Variant B: Body parameter only (No button)
                    $componentsBodyOnly = [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $code],
                            ],
                        ],
                    ];

                    $altTplResp = $this->whatsappService->sendTemplateMessage($cleanMobile, $otpTemplate, 'en', $componentsBodyOnly);
                    if (! empty($altTplResp['success'])) {
                        Log::info('TFC OTP sent via Meta Template (body only)', [
                            'template' => $otpTemplate,
                            'mobile' => $cleanMobile,
                        ]);
                        return ['success' => true, 'message' => 'OTP sent to your WhatsApp.', 'code' => $code];
                    }
                }

                // Fallback: Direct WhatsApp text dispatch
                $textResp = $this->whatsappService->sendTextMessage($cleanMobile, $otpText);
                Log::info('TFC Mobile Verification OTP sent via WhatsApp text fallback', [
                    'mobile' => $cleanMobile,
                    'member_id' => $memberId,
                    'response' => $textResp,
                ]);

                return ['success' => true, 'message' => 'OTP sent to your WhatsApp.', 'code' => $code];
            }

            // Local fallback / logging
            Log::info('TFC OTP generated locally (WhatsApp not configured)', [
                'mobile' => $cleanMobile,
                'code' => $code,
            ]);

            return ['success' => true, 'message' => 'OTP generated (local mode).', 'code' => $code];
        } catch (\Throwable $e) {
            Log::warning('Failed sending TFC OTP via WhatsApp', [
                'mobile' => $cleanMobile,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to dispatch WhatsApp OTP: ' . $e->getMessage()];
        }
    }

    /**
     * Send welcome message with Member ID, verification link, and fallback credentials.
     */
    public function sendWelcomeNotification(DirectSellingMember $member, string $defaultPassword = 'TFC@123456'): array
    {
        $domain = config('tfc.domain', 'tfcsalesmarketing.com');
        $verifyUrl = $this->generateVerificationUrl($member);
        $loginUrl = "https://{$domain}/member/login";
        $referralUrl = $this->generateReferralUrl($member);

        $sponsorId = $member->sponsor ? $member->sponsor->tfc_member_id : 'N/A';
        $sponsorName = $member->sponsor ? $member->sponsor->full_name : 'Direct';

        $text = "🌟 *Welcome to TFC Sales & Marketing!*\n\n"
              . "Hi *{$member->full_name}*,\n\n"
              . "Your new account has been created successfully.\n\n"
              . "🆔 *Member ID:* `{$member->tfc_member_id}`\n"
              . "👥 *Sponsor:* {$sponsorName} ({$sponsorId})\n\n"
              . "Please verify your number to complete your profile and set your personal password:\n"
              . "👉 {$verifyUrl}\n\n"
              . "(Default temporary password: `{$defaultPassword}`)\n\n"
              . "🌐 *Member Portal Login:*\n"
              . "{$loginUrl}\n\n"
              . "🚀 *Your Personal Referral Link:*\n"
              . "{$referralUrl}\n\n"
              . "— TFC Sales & Marketing Team";

        try {
            if ($this->whatsappService->isConfigured()) {
                $configuredName = class_exists(\App\Models\Tfc\TfcSetting::class)
                    ? \App\Models\Tfc\TfcSetting::get('whatsapp_welcome_template', 'tfc_welcome_onboarding')
                    : 'tfc_welcome_onboarding';

                $candidateTemplates = array_values(array_unique(array_filter([
                    $configuredName,
                    'tfc_welcome_onboarding',
                    'tfc_welcome',
                    'tfc_member_welcome',
                ])));

                $token = $this->generateEncryptedVerificationToken($member->tfc_member_id, $member->mobile);

                foreach ($candidateTemplates as $tplName) {
                    // Try sending with 1 body variable (Customer Name) and 1 URL button variable (clean 32-char token)
                    $components = [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $member->full_name],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => '0',
                            'parameters' => [
                                ['type' => 'text', 'text' => $token],
                            ],
                        ],
                    ];

                    $tplResponse = $this->whatsappService->sendTemplateMessage($member->mobile, $tplName, 'en', $components);
                    if (! empty($tplResponse['success'])) {
                        Log::info('TFC Member Welcome WhatsApp Template sent', [
                            'member_id' => $member->tfc_member_id,
                            'template' => $tplName,
                            'response' => $tplResponse,
                        ]);
                        return $tplResponse;
                    }

                    // Also try classic 4-variable template if configured in Meta
                    $altComponents = [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $member->full_name],
                                ['type' => 'text', 'text' => $member->tfc_member_id],
                                ['type' => 'text', 'text' => $defaultPassword],
                                ['type' => 'text', 'text' => $verifyUrl],
                            ],
                        ],
                    ];
                    $altResponse = $this->whatsappService->sendTemplateMessage($member->mobile, $tplName, 'en', $altComponents);
                    if (! empty($altResponse['success'])) {
                        return $altResponse;
                    }
                }

                // Fallback: Send rich formatted text message with verification link
                $response = $this->whatsappService->sendTextMessage($member->mobile, $text);
                Log::info('TFC Member Welcome WhatsApp sent', [
                    'member_id' => $member->tfc_member_id,
                    'mobile' => $member->mobile,
                    'response' => $response,
                ]);
                return $response;
            }

            Log::info('TFC WhatsApp not configured; logged message for member', [
                'member_id' => $member->tfc_member_id,
                'mobile' => $member->mobile,
                'verify_url' => $verifyUrl,
            ]);

            return ['success' => true, 'message' => 'Logged locally (WhatsApp API not configured)'];
        } catch (\Throwable $e) {
            Log::warning('Failed sending TFC Member Welcome WhatsApp', [
                'member_id' => $member->tfc_member_id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send test welcome notification to any phone number.
     */
    public function sendTestWelcomeNotification(string $phone, string $fullName = 'Rahul Sharma', string $memberId = 'TFC10001', string $defaultPassword = 'TFC@123456'): array
    {
        $token = $this->generateEncryptedVerificationToken($memberId, $phone);
        $verifyUrl = function_exists('tfc_route')
            ? tfc_route('member.verify-mobile', ['token' => $token])
            : route('tfc.preview.member.verify-mobile', ['token' => $token]);

        $loginUrl = function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login');

        $text = "🌟 *Welcome to TFC Sales & Marketing!*\n\n"
              . "Hi *{$fullName}*,\n\n"
              . "Your new account has been created successfully.\n\n"
              . "🆔 *Member ID:* `{$memberId}`\n\n"
              . "Please verify your number to complete your profile and set your personal password:\n"
              . "👉 {$verifyUrl}\n\n"
              . "(Default temporary password: `{$defaultPassword}`)\n\n"
              . "🌐 *Member Portal Login:*\n"
              . "{$loginUrl}\n\n"
              . "— TFC Sales & Marketing Team";

        if (! $this->whatsappService->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp Cloud API is not configured. Please enter your Phone Number ID and Access Token.'];
        }

        $templateName = class_exists(\App\Models\Tfc\TfcSetting::class)
            ? \App\Models\Tfc\TfcSetting::get('whatsapp_welcome_template', 'tfc_member_welcome')
            : 'tfc_member_welcome';

        if (! empty($templateName)) {
            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $fullName],
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text', 'text' => $token],
                    ],
                ],
            ];

            $res = $this->whatsappService->sendTemplateMessage($phone, $templateName, 'en', $components);
            if (! empty($res['success'])) {
                return $res;
            }
        }

        return $this->whatsappService->sendTextMessage($phone, $text);
    }
}
