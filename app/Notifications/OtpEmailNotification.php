<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpEmailNotification extends Notification
{
    /**
     * @param  string  $code  The plain 6-digit code. Kept public so tests can
     *                        read it back off the faked notification instead of
     *                        trying to reverse the stored hash.
     */
    public function __construct(
        public string $code,
        public string $purpose = 'reset_password',
        public int|float $expireMinutes = 10,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $reset = $this->purpose === 'reset_password';

        return (new MailMessage)
            ->subject($reset
                ? 'Your '.store_name().' verification code'
                : 'Your '.store_name().' one-time code')
            ->view('emails.auth.otp-code', [
                'code' => $this->code,
                'store' => store_name(),
                'logo' => store_logo_url(),
                'reset' => $reset,
                'expireMinutes' => (int) $this->expireMinutes,
                'requestUrl' => route('password.request'),
                'loginUrl' => route('login'),
                'supportEmail' => config('mail.from.address'),
            ]);
    }
}
