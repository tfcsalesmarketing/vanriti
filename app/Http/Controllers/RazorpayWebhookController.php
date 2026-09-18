<?php

namespace App\Http\Controllers;

use App\Jobs\PushOrderToShipMojo;
use App\Jobs\SendMetaCapiPurchase;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Analytics\ConversionService;
use App\Services\Analytics\MetaCapiService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\RazorpayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server Razorpay webhook.
 *
 * This is the authoritative confirmation path when the customer's browser never
 * returns to the callback (tab closed, network drop, wallet app switch). Every
 * branch is idempotent: a replayed event resolves to the same terminal state
 * and never charges, refunds, restores stock or records a conversion twice.
 */
class RazorpayWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected OrderService $orderService,
        protected ConversionService $conversionService,
        protected NotificationService $notifications,
        protected MetaCapiService $metaCapiService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $gateway = new RazorpayGateway;

        if (! $gateway->verifyWebhookSignature($request->getContent(), $request->header('x-razorpay-signature'))) {
            Log::warning('Razorpay webhook rejected: invalid or missing signature.');

            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = (string) $request->input('event');
        $entity = (array) $request->input('payload.payment.entity', []);

        return match ($event) {
            'payment.captured' => $this->handleCaptured($entity),
            'payment.failed' => $this->handleFailed($entity),
            default => response()->json(['status' => 'ignored']),
        };
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    protected function handleCaptured(array $entity): JsonResponse
    {
        $razorpayOrderId = (string) ($entity['order_id'] ?? '');
        $razorpayPaymentId = (string) ($entity['id'] ?? '');
        $amountPaisa = (int) ($entity['amount'] ?? 0);

        $payment = $this->resolvePayment($razorpayOrderId);

        if (! $payment) {
            return response()->json(['status' => 'payment not found'], 404);
        }

        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return response()->json(['status' => 'already processed']);
        }

        $order = $payment->order;

        if (! $order) {
            return response()->json(['status' => 'order not found'], 404);
        }

        // The webhook amount is authoritative: refuse to settle an order for a
        // different figure than the one we asked the gateway to charge.
        $expectedPaisa = (int) round((float) $payment->amount * 100);
        if ($amountPaisa > 0 && $amountPaisa !== $expectedPaisa) {
            Log::warning('Razorpay webhook amount mismatch; payment not settled.', [
                'order' => $order->order_number,
                'expected_paisa' => $expectedPaisa,
                'received_paisa' => $amountPaisa,
            ]);

            return response()->json(['status' => 'amount mismatch'], 422);
        }

        if ($razorpayPaymentId !== '') {
            // The payments table keeps the gateway order id in
            // payment_reference; record the captured payment id alongside the
            // gateway response for reconciliation.
            $payment->forceFill([
                'gateway_response' => array_merge((array) $payment->gateway_response, [
                    'webhook_payment_id' => $razorpayPaymentId,
                    'webhook_amount' => $amountPaisa,
                ]),
            ])->save();
        }

        $this->paymentService->markPaid($payment);

        $order = $order->refresh();

        if ((bool) setting('shipmojo_auto_push', false)) {
            PushOrderToShipMojo::dispatch($order);
        }

        // Idempotent by UNIQUE(event_type, order_number); a replay is a no-op.
        $this->conversionService->recordPurchase($order);

        if ($this->metaCapiService->isConfigured()) {
            SendMetaCapiPurchase::dispatch($order, route('checkout.success', $order));
        }

        $this->notifyCustomer($order, 'paymentSuccessful');

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string, mixed>  $entity
     */
    protected function handleFailed(array $entity): JsonResponse
    {
        $razorpayOrderId = (string) ($entity['order_id'] ?? '');

        $payment = $this->resolvePayment($razorpayOrderId);

        if (! $payment) {
            return response()->json(['status' => 'payment not found'], 404);
        }

        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return response()->json(['status' => 'already processed']);
        }

        $order = $payment->order;

        if (! $order) {
            return response()->json(['status' => 'order not found'], 404);
        }

        $this->paymentService->markFailed($payment, 'Payment failed at Razorpay.');

        // Cancel the order and release the reserved stock. markFailed leaves the
        // order status untouched, so a still-open order remains cancellable.
        try {
            if ($order->fresh()->isCancellable()) {
                $this->orderService->cancelOrder($order, 'Payment failed at Razorpay; stock restored.');
            }
        } catch (\Throwable $e) {
            Log::warning('Could not cancel an order after a failed Razorpay payment.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        $this->notifyCustomer($order, 'paymentFailed');

        return response()->json(['status' => 'ok']);
    }

    protected function resolvePayment(string $razorpayOrderId): ?Payment
    {
        if ($razorpayOrderId === '') {
            return null;
        }

        return Payment::query()
            ->where('payment_reference', $razorpayOrderId)
            ->where('method', 'razorpay')
            ->latest()
            ->first();
    }

    protected function notifyCustomer(Order $order, string $method): void
    {
        try {
            if ($order->user) {
                $this->notifications->{$method}($order);
            }
        } catch (\Throwable $e) {
            Log::warning('Order notification could not be delivered.', [
                'order' => $order->order_number,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
