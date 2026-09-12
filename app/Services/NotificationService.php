<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function orderStatusChanged(Order $order, string $status): void
    {
        $order->user->notify(new OrderStatusNotification($order, $status));
    }

    public function orderPlaced(Order $order): void
    {
        $this->orderStatusChanged($order, 'pending');
    }

    public function paymentSuccessful(Order $order): void
    {
        $this->orderStatusChanged($order, 'payment_confirmed');
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
        $refund->user->notify(new RefundStatusNotification($refund));
    }

    public function notifyAdmins(string $title, string $message, array $channels = ['database']): void
    {
        foreach (Admin::where('status', 'active')->get() as $admin) {
            $admin->notify(new \App\Notifications\AdminAlert($title, $message));
        }
    }
}