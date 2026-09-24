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

    public function __construct(public Order $order, public string $status) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'shipments']);
        $shipment = $order->shipments()->latest()->first();

        $firstName = is_object($notifiable) && isset($notifiable->name) && trim((string) $notifiable->name) !== ''
            ? explode(' ', trim((string) $notifiable->name))[0]
            : 'there';

        return (new MailMessage)
            ->subject($this->subjectLine())
            ->view('emails.orders.status', [
                'name' => $firstName,
                'email' => is_object($notifiable) ? $notifiable->getEmailForPasswordReset() : null,
                'store' => store_name(),
                'logo' => store_logo_url(),
                'supportEmail' => config('mail.from.address'),
                'order' => $order,
                'shipment' => $shipment,
                'status' => $this->status,
                'title' => $this->emailTitle(),
                'intro' => $this->emailIntro(),
                'note' => $this->emailNote(),
                'ctaText' => $this->ctaText(),
                'ctaUrl' => route('account.order', $order),
            ]);
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
            'pending' => 'Order Placed - ',
            'confirmed' => 'Order Confirmed - ',
            'processing' => 'Order Processing - ',
            'packed' => 'Ready to Dispatch - ',
            'shipped' => 'Order Shipped - ',
            'out_for_delivery' => 'Out for Delivery - ',
            'delivered' => 'Order Delivered - ',
            'cancelled' => 'Order Cancelled - ',
            'failed' => 'Order Failed - ',
            'payment_confirmed' => 'Payment Successful - ',
            'payment_failed' => 'Payment Failed - ',
            default => 'Order Update - ',
        }.$this->order->order_number;
    }

    protected function emailTitle(): string
    {
        return match ($this->status) {
            'pending' => 'Order Received',
            'confirmed' => 'Order Confirmed',
            'processing' => 'Your Order is Being Prepared',
            'packed' => 'Ready to Dispatch',
            'shipped' => 'On Its Way!',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Order Delivered',
            'cancelled' => 'Order Cancelled',
            'failed' => 'Order Failed',
            'payment_confirmed' => 'Payment Successful',
            'payment_failed' => 'Payment Failed',
            default => 'Order Update',
        };
    }

    protected function emailIntro(): string
    {
        return match ($this->status) {
            'pending' => 'Thank you for shopping with '.store_name().'. Your order has been received and is awaiting confirmation. We will notify you as soon as it is confirmed.',
            'confirmed' => 'Great news! Your order has been confirmed and our team has started preparing it for you.',
            'processing' => 'Your order is being prepared with care at our warehouse. It will be dispatched soon.',
            'packed' => 'Your order has been packed and is ready to be handed over to our courier partner. It is all set to make its way to you.',
            'shipped' => 'Your order has been shipped! Your tracking details are below so you can follow it every step of the way.',
            'out_for_delivery' => 'Your order is out for delivery! Please keep an eye out — your package will arrive soon.',
            'delivered' => 'Your order has been delivered. We hope you love it as much as we loved making it. Enjoy!',
            'cancelled' => 'Your order has been cancelled as requested. If you were charged, a refund will be initiated to the original payment method.',
            'failed' => 'We could not complete your order. If you were charged, a refund has been initiated to the original payment method.',
            'payment_confirmed' => 'Your payment has been received successfully. We are now hustling to prepare your order.',
            'payment_failed' => 'We could not process your payment for this order. Your items are safe in your cart whenever you are ready to complete the purchase.',
            default => 'There has been an update on your order.',
        };
    }

    protected function emailNote(): ?string
    {
        return match ($this->status) {
            'pending' => 'For Cash on Delivery orders, no advance payment is needed. You can pay when your order arrives.',
            'shipped', 'out_for_delivery' => 'Delivery timelines may vary slightly during sale periods. Rest assured, we are tracking it too.',
            default => null,
        };
    }

    protected function ctaText(): string
    {
        return in_array($this->status, ['shipped', 'out_for_delivery', 'delivered'], true)
            ? 'Track My Order'
            : 'View Order';
    }

    protected function message(): string
    {
        return match ($this->status) {
            'pending' => 'Your order has been placed and is awaiting confirmation.',
            'confirmed' => 'Your order has been confirmed.',
            'processing' => 'Your order is being processed.',
            'packed' => 'Your order is packed and ready to dispatch.',
            'shipped' => 'Your order has been shipped.',
            'out_for_delivery' => 'Your order is out for delivery.',
            'delivered' => 'Your order has been delivered. Enjoy!',
            'cancelled' => 'Your order has been cancelled.',
            'failed' => 'Your order could not be completed.',
            'payment_confirmed' => 'Your payment was successful.',
            'payment_failed' => 'Your payment failed. Please retry.',
            default => 'Your order status has been updated.',
        };
    }
}
