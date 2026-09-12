<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Refund $refund)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Refund Update - '.$this->refund->refund_number)
            ->greeting('Hello '.$notifiable->name)
            ->line('Your refund of '.format_price($this->refund->amount).' is '.str_replace('_', ' ', $this->refund->status).'.')
            ->action('View Account', url('/account'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund_status',
            'refund_number' => $this->refund->refund_number,
            'status' => $this->refund->status,
            'message' => 'Your refund of '.format_price($this->refund->amount).' is '.str_replace('_', ' ', $this->refund->status).'.',
        ];
    }
}