<?php

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Auth\Events\Login;

class LinkPostPurchaseGuestOrders
{
    /**
     * Post-purchase account creation.
     *
     * The guest success page (via checkout.create-account) stages the raw
     * access tokens of THIS browser's guest orders in the session. When the
     * visitor completes a registration (or logs in) in the same browser,
     * link only those session-held orders to the account.
     *
     * Security: the flag can only contain raw tokens that were shown to this
     * browser for orders it placed, and each one is re-verified against the
     * stored SHA-256 hash before the order is claimed — a forged or foreign
     * order id can never be linked.
     */
    public function handle(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $flag = session('post_purchase_link');

        if (! is_array($flag) || $flag === []) {
            return;
        }

        foreach ($flag as $orderId => $rawToken) {
            $order = Order::find((int) $orderId);

            if (! $order || ! $order->isGuest()) {
                continue;
            }

            if (! is_string($rawToken) || $rawToken === '' || ! $order->guestTokenValid($rawToken)) {
                continue;
            }

            $order->update(['user_id' => $event->user->id]);
        }

        session()->forget('post_purchase_link');
    }
}
