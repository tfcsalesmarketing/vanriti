<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $fullName = is_object($notifiable) ? $notifiable->name ?? null : null;
        $firstName = $fullName !== null && trim($fullName) !== ''
            ? explode(' ', trim($fullName))[0]
            : '';

        return (new MailMessage)
            ->subject('Welcome to '.store_name())
            ->view('emails.auth.welcome', [
                'name' => $firstName,
                'email' => $notifiable->getEmailForPasswordReset(),
                'store' => store_name(),
                'logo' => store_logo_url(),
                'loginUrl' => route('login'),
                'shopUrl' => route('shop.index'),
                'supportEmail' => config('mail.from.address'),
            ]);
    }
}
