<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Notifications\OtpEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Email OTP delivery — the mail-side twin of WhatsAppOtpService.
 *
 * The forgot-password flow lets a customer enter an email address OR a mobile
 * number. WhatsApp only reaches the second case, so a phone-only account (and
 * any account reached while WhatsApp is disabled) needs codes that land in an
 * inbox. Codes are stored in the same otp_codes table with channel = "email"
 * so both channels share expiry, attempt counting and the used_at burn.
 */
class EmailOtpService
{
    /** Max verify attempts before the code is burnt, hard cap. */
    protected int $maxAttempts = 5;

    /**
     * Send a 6-digit OTP to the given email address.
     *
     * @return array<string, mixed>
     */
    public function sendOtp(string $email, string $purpose = 'reset_password'): array
    {
        $email = $this->normalize($email);

        if ($email === null) {
            return $this->failure('Please enter a valid email address.');
        }

        // Cooldown: reuse the in-flight OTP instead of re-sending mail, so a
        // held-down button cannot flood an inbox.
        $remaining = $this->cooldownRemaining($email, $purpose);

        if ($remaining > 0) {
            return $this->failure('Please wait for the cooldown to expire before requesting a new code.');
        }

        try {
            $code = (string) random_int(100000, 999999);

            OtpCode::create([
                'phone' => null,
                'email' => $email,
                'purpose' => $purpose,
                'channel' => 'email',
                'hashed_code' => Hash::make($code),
                'expires_at' => now()->addSeconds($this->ttlSeconds()),
            ]);

            Notification::route('mail', $email)->notify(new OtpEmailNotification(
                $code,
                $purpose,
                $this->ttlSeconds() / 60,
            ));
        } catch (\Throwable $e) {
            Log::warning('Email OTP send exception.', [
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('We could not send a code by email right now. Please try again.');
        }

        return ['result' => '1', 'message' => 'OTP sent to your email.', 'data' => ['email' => $email]];
    }

    /**
     * Verify a submitted code against the latest live email OTP.
     *
     * @return array<string, mixed>
     */
    public function verifyOtp(string $email, string $code, string $purpose = 'reset_password'): array
    {
        $email = $this->normalize($email);

        if ($email === null) {
            return $this->failure('Please enter a valid email address.');
        }

        $otp = OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('channel', 'email')
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

        return ['result' => '1', 'message' => 'OTP verified.', 'data' => ['email' => $email]];
    }

    /**
     * Track how long before the next send is allowed for the email (seconds).
     */
    public function cooldownRemaining(string $email, string $purpose = 'reset_password'): int
    {
        $email = $this->normalize($email);

        if ($email === null) {
            return 0;
        }

        $latest = OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());
        $remaining = $elapsed <= 60 ? 60 - $elapsed : 0;

        return max(0, $remaining);
    }

    protected function ttlSeconds(): int
    {
        return max(60, (int) setting('email_otp_ttl_seconds', 600));
    }

    protected function normalize(string $email): ?string
    {
        $email = Str::lower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function failure(string $message): array
    {
        return ['result' => '0', 'message' => $message];
    }
}
