<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $status)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine())
            ->greeting('Hello '.$notifiable->name)
            ->line('Your order '.$this->order->order_number.' status has been updated.')
            ->line('Status: '.ucwords(str_replace('_', ' ', $this->status)))
            ->line('Order Total: '.format_price($this->order->grand_total))
            ->action('View Order', url('/account/orders/'.$this->order->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status,
            'message' => $this->message(),
        ];
    }

    protected function subjectLine(): string
    {
        return match ($this->status) {
            'confirmed' => 'Order Confirmed - ',
            'shipped' => 'Order Shipped - ',
            'out_for_delivery' => 'Out for Delivery - ',
            'delivered' => 'Order Delivered - ',
            'cancelled' => 'Order Cancelled - ',
            'payment_confirmed' => 'Payment Successful - ',
            'payment_failed' => 'Payment Failed - ',
            default => 'Order Update - ',
        }.$this->order->order_number;
    }

    protected function message(): string
    {
        return match ($this->status) {
            'confirmed' => 'Your order has been confirmed.',
            'processing' => 'Your order is being processed.',
            'packed' => 'Your order has been packed.',
            'shipped' => 'Your order has been shipped.',
            'out_for_delivery' => 'Your order is out for delivery.',
            'delivered' => 'Your order has been delivered. Enjoy!',
            'cancelled' => 'Your order has been cancelled.',
            'payment_confirmed' => 'Your payment was successful.',
            'payment_failed' => 'Your payment failed. Please retry.',
            default => 'Your order status has been updated.',
        };
    }
}