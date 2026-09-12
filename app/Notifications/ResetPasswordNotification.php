<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $fullName = is_object($notifiable) ? $notifiable->name ?? null : null;
        $firstName = $fullName !== null && trim($fullName) !== ''
            ? explode(' ', trim($fullName))[0]
            : '';

        return (new MailMessage)
            ->subject('Reset Your '.store_name().' Password')
            ->view('emails.auth.reset-password', [
                'name' => $firstName,
                'email' => $notifiable->getEmailForPasswordReset(),
                'url' => $this->resetUrl($notifiable),
                'expireMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                'store' => store_name(),
                'logo' => store_logo_url(),
                'loginUrl' => route('login'),
                'supportEmail' => config('mail.from.address'),
            ]);
    }
}