<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlert extends Notification
{
    use Queueable;

    public function __construct(public string $title, public string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_alert',
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}