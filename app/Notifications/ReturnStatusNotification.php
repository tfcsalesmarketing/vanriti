<?php

namespace App\Notifications;

use App\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReturnStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReturnRequest $returnRequest, public string $status = 'requested')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Return Update - '.$this->returnRequest->return_number)
            ->greeting('Hello '.$notifiable->name)
            ->line('Your return request '.$this->returnRequest->return_number.' has been updated.')
            ->line('Status: '.ucwords(str_replace('_', ' ', $this->status)))
            ->action('View Return', url('/account'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'return_status',
            'return_number' => $this->returnRequest->return_number,
            'status' => $this->status,
            'message' => 'Your return request has been marked as '.str_replace('_', ' ', $this->status).'.',
        ];
    }
}