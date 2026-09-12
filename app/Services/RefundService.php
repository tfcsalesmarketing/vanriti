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
    ) {
    }

    public function createFromReturn(ReturnRequest $returnRequest, User $user, float $amount, string $type = 'full', string $reason = null): Refund
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

    public function approve(Refund $refund, string $adminNote = null): Refund
    {
        $refund->update([
            'status' => 'approved',
            'admin_note' => $adminNote,
            'processed_at' => now(),
        ]);

        return $refund;
    }

    public function reject(Refund $refund, string $adminNote = null): Refund
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

    public function complete(Refund $refund, string $gatewayReference = null): Refund
    {
        if ($refund->status === 'completed') {
            return $refund;
        }

        $completedByThisCall = DB::transaction(function () use ($refund, $gatewayReference) {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->first();

            if (! $refund || $refund->status === 'completed') {
                return false;
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

            $refund->order->update([
                'payment_status' => 'refunded',
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