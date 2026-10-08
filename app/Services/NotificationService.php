<?php

namespace App\Services;

use App\Jobs\SendWhatsAppOrderConfirmation;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Notifications\AdminAlert;
use App\Notifications\OrderStatusNotification;

class NotificationService
{
    public function orderStatusChanged(Order $order, string $status): void
    {
        // Guest orders have no account to notify (status still reaches the
        // customer through the signed tracking link / admin-driven channels).
        $order->user?->notify(new OrderStatusNotification($order, $status));
    }

    public function orderPlaced(Order $order): void
    {
        $this->orderStatusChanged($order, 'pending');

        // A Razorpay order is not confirmed until the payment settles, so its
        // WhatsApp waits for paymentSuccessful(); a failed payment must never
        // be told the order is confirmed.
        if ($order->payment_method !== 'razorpay') {
            SendWhatsAppOrderConfirmation::dispatchSync($order);
        }
    }

    public function paymentSuccessful(Order $order): void
    {
        $this->orderStatusChanged($order, 'payment_confirmed');

        SendWhatsAppOrderConfirmation::dispatchSync($order);
    }

    public function paymentFailed(Order $order): void
    {
        $this->orderStatusChanged($order, 'payment_failed');
    }

    public function returnRequested(ReturnRequest $returnRequest): void
    {
        $returnRequest->user->notify(new ReturnStatusNotification($returnRequest));
    }

    public function returnStatusChanged(ReturnRequest $returnRequest, string $status): void
    {
        $returnRequest->user->notify(new ReturnStatusNotification($returnRequest, $status));
    }

    public function refundStatusChanged(Refund $refund): void
    {
        // Refunds on guest orders carry no user to notify.
        $refund->user?->notify(new RefundStatusNotification($refund));
    }

    public function notifyAdmins(string $title, string $message, array $channels = ['database']): void
    {
        foreach (Admin::where('status', 'active')->get() as $admin) {
            $admin->notify(new AdminAlert($title, $message));
        }
    }
}
