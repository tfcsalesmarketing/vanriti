<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Analytics\EcommerceDataService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundService
{
    /**
     * Cache key prefix for refund analytics staged for delivery to the
     * customer's next storefront render. Suffixed with the refund user's id.
     */
    public const PENDING_REFUND_CACHE_KEY = 'pending_refund_analytics_';

    public function __construct(
        protected PaymentService $paymentService,
        protected EcommerceDataService $ecommerce,
    ) {}

    public function createFromReturn(ReturnRequest $returnRequest, User $user, float $amount, string $type = 'full', ?string $reason = null): Refund
    {
        $order = $returnRequest->order;

        return DB::transaction(function () use ($returnRequest, $user, $amount, $type, $reason, $order) {
            $refund = Refund::create([
                'refund_number' => 'REF-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
                'return_request_id' => $returnRequest->id,
                'order_id' => $order->id,
                'payment_id' => $order->payments()->where('status', 'paid')->first()?->id,
                'user_id' => $user->id,
                'amount' => round($amount, 2),
                'type' => $type,
                'status' => 'requested',
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            return $refund;
        });
    }

    /**
     * Create a refund that is not tied to a return request, used when a paid
     * order is cancelled. The customer is refunded what they actually paid.
     */
    public function createForOrder(Order $order, ?User $user, float $amount, string $type = 'full', ?string $reason = null): Refund
    {
        return DB::transaction(function () use ($order, $user, $amount, $type, $reason) {
            return Refund::create([
                'refund_number' => 'REF-'.now()->format('Y').'-'.strtoupper(Str::random(6)),
                'return_request_id' => null,
                'order_id' => $order->id,
                'payment_id' => $order->payments()->where('status', 'paid')->first()?->id,
                'user_id' => $user?->id ?? $order->user_id,
                'amount' => round($amount, 2),
                'type' => $type,
                'status' => 'requested',
                'reason' => $reason,
                'requested_at' => now(),
            ]);
        });
    }

    public function approve(Refund $refund, ?string $adminNote = null): Refund
    {
        $refund->update([
            'status' => 'approved',
            'admin_note' => $adminNote,
            'processed_at' => now(),
        ]);

        return $refund;
    }

    public function reject(Refund $refund, ?string $adminNote = null): Refund
    {
        $refund->update([
            'status' => 'rejected',
            'admin_note' => $adminNote,
            'processed_at' => now(),
        ]);

        return $refund;
    }

    public function process(Refund $refund): Refund
    {
        $refund->update(['status' => 'processing']);

        return $refund;
    }

    public function complete(Refund $refund, ?string $gatewayReference = null): Refund
    {
        if ($refund->status === 'completed') {
            return $refund;
        }

        $completedByThisCall = DB::transaction(function () use ($refund, $gatewayReference) {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->first();

            if (! $refund || $refund->status === 'completed') {
                return false;
            }

            // A refund may only be paid out once it has been approved and sent
            // to the gateway. Completing a refund that is still 'requested', or
            // one that was already 'rejected', would send money back for a refund
            // the business never authorised (or explicitly refused).
            if (! in_array($refund->status, ['approved', 'processing'], true)) {
                throw new \RuntimeException('Only an approved refund can be completed. This refund is currently '.$refund->status.'.');
            }

            $order = $refund->order;

            if (! $order) {
                throw new \RuntimeException('Refund is not linked to an order.');
            }

            // Never refund more than the customer actually paid. The cap is
            // cumulative across every completed refund on the order, so a
            // sequence of partial refunds can never exceed the paid total.
            $paid = (float) $order->payments()->where('status', 'paid')->sum('amount');

            if ($paid <= 0) {
                $paid = (float) $order->amount_paid;
            }

            $alreadyRefunded = (float) $order->refunds()
                ->where('status', 'completed')
                ->where('id', '!=', $refund->id)
                ->sum('amount');

            if (round($alreadyRefunded + (float) $refund->amount, 2) > round($paid, 2) + 0.01) {
                throw new \RuntimeException('Refund would exceed the amount paid for this order.');
            }

            $payment = $refund->payment;

            if ($payment) {
                try {
                    $gateway = $this->paymentService->gatewayFor($payment->gateway ?? $payment->method);
                    $result = $gateway->refund($payment, $refund->amount, $gatewayReference);
                    $gatewayReference = $result['reference'] ?? $gatewayReference;
                } catch (\Throwable $e) {
                    // Record as failed but keep manual option open
                    RefundTransaction::create([
                        'refund_id' => $refund->id,
                        'status' => 'failed',
                        'amount' => $refund->amount,
                        'payload' => ['error' => $e->getMessage()],
                    ]);
                    throw $e;
                }
            } elseif ($order->payment_method === 'razorpay') {
                // An online order must be refunded through the gateway once it
                // has a captured payment; a missing payment means the money
                // cannot be returned automatically and needs manual handling.
                throw new \RuntimeException('No captured payment is available to refund for this order.');
            }

            $refund->update([
                'status' => 'completed',
                'gateway_reference' => $gatewayReference,
                'processed_at' => now(),
            ]);

            RefundTransaction::create([
                'refund_id' => $refund->id,
                'transaction_id' => $gatewayReference,
                'status' => 'success',
                'amount' => $refund->amount,
            ]);

            $totalRefunded = round($alreadyRefunded + (float) $refund->amount, 2);

            $order->update([
                'payment_status' => $totalRefunded + 0.01 >= round($paid, 2) ? 'refunded' : 'partially_refunded',
            ]);

            return true;
        });

        $refund->refresh();

        if ($completedByThisCall) {
            $this->stageRefundAnalytics($refund);
        }

        return $refund;
    }

    /**
     * Stage the refund analytics payload for delivery on the customer's next
     * storefront render. Called only after the completion transaction has
     * committed, so a failed or rolled-back completion never stages analytics.
     *
     * A plain session flash cannot deliver the event across the admin ->
     * customer browser boundary, so the payload is staged in the cache keyed
     * by the refunding user and pulled exactly once by the storefront layout.
     */
    protected function stageRefundAnalytics(Refund $refund): void
    {
        if (! $refund->user_id) {
            return;
        }

        $key = self::PENDING_REFUND_CACHE_KEY.$refund->user_id;
        $pending = (array) cache()->get($key, []);
        $pending[$refund->id] = $this->ecommerce->refund($refund);

        cache()->put($key, $pending, now()->addDays(7));
    }
}
