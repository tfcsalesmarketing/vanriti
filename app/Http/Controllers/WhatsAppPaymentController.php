<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\WhatsAppCheckoutSession;
use App\Services\Payments\PaymentService;
use App\Services\Payments\RazorpayGateway;
use App\Services\WhatsAppCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WhatsAppPaymentController extends Controller
{
    public function __construct(
        protected WhatsAppCheckoutService $checkoutService,
        protected PaymentService $paymentService,
    ) {}

    /**
     * Display the secure branded VANRITI payment page.
     */
    public function show(string $token): View
    {
        $session = WhatsAppCheckoutSession::where('session_token', $token)->firstOrFail();

        if ($session->isExpired()) {
            return view('storefront.checkout.whatsapp-pay', [
                'session' => $session,
                'order' => $session->order,
                'expired' => true,
                'alreadyPaid' => false,
            ]);
        }

        $order = $session->order;

        if (! $order) {
            abort(404, 'Associated order not found.');
        }

        if ($order->payment_status === 'paid') {
            return view('storefront.checkout.whatsapp-pay', [
                'session' => $session,
                'order' => $order,
                'expired' => false,
                'alreadyPaid' => true,
            ]);
        }

        // Ensure a pending Razorpay payment record exists
        $payment = $order->payments()->where('method', 'razorpay')->latest()->first();
        if (! $payment || $payment->status === 'failed') {
            $payment = $this->paymentService->createPayment($order, 'razorpay');
        }

        $gateway = new RazorpayGateway();
        $razorpayOrderId = $payment->payment_reference;

        if (! $razorpayOrderId) {
            $rpOrder = $gateway->createOrder($order, $payment);
            $razorpayOrderId = $rpOrder['id'];
            $session->update(['razorpay_order_id' => $razorpayOrderId]);
        }

        $keyId = (string) setting('razorpay_key_id', '');

        return view('storefront.checkout.whatsapp-pay', [
            'session' => $session,
            'order' => $order,
            'payment' => $payment,
            'razorpayOrderId' => $razorpayOrderId,
            'keyId' => $keyId,
            'amountPaise' => (int) round($order->amount_due * 100),
            'expired' => false,
            'alreadyPaid' => false,
        ]);
    }

    /**
     * Process Razorpay Checkout payment callback (POST).
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        $session = WhatsAppCheckoutSession::where('session_token', $token)->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Checkout session not found.'], 404);
        }

        $order = $session->order;
        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified.',
                'order_number' => $order->order_number,
            ]);
        }

        $payment = $order->payments()->where('method', 'razorpay')->latest()->first();
        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment record not found.'], 404);
        }

        $gateway = new RazorpayGateway();
        $payload = [
            'razorpay_order_id' => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
            'razorpay_signature' => $request->input('razorpay_signature'),
        ];

        // 1. Verify HMAC cryptographic signature
        if (! $gateway->verify($payment, $payload)) {
            Log::warning('WhatsApp payment signature verification failed', [
                'session' => $session->id,
                'order' => $order->order_number,
                'payload' => $payload,
            ]);

            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => array_merge((array) $payment->gateway_response, $payload, [
                    'failure_reason' => 'Signature verification mismatch',
                ]),
            ]);

            $this->checkoutService->handlePaymentFailure($session, $order, 'Signature mismatch');

            return response()->json(['success' => false, 'message' => 'Payment signature verification failed.'], 422);
        }

        // 2. Authoritative check: fetch payment from Razorpay API
        $paymentDetails = $gateway->fetchPayment((string) $payload['razorpay_payment_id']);

        if ($paymentDetails && isset($paymentDetails['amount'])) {
            $expectedPaise = (int) round($order->amount_due * 100);
            if ((int) $paymentDetails['amount'] !== $expectedPaise) {
                Log::error('WhatsApp payment amount mismatch', [
                    'expected' => $expectedPaise,
                    'received' => $paymentDetails['amount'],
                ]);

                return response()->json(['success' => false, 'message' => 'Payment amount mismatch.'], 422);
            }
        }

        // 3. Mark payment paid and transition order status
        $payment->forceFill([
            'gateway_response' => array_merge((array) $payment->gateway_response, $payload, [
                'webhook_payment_id' => $payload['razorpay_payment_id'],
            ]),
        ])->save();

        $this->paymentService->markPaid($payment);

        $order = $order->refresh();
        $order->update([
            'order_status' => 'confirmed',
        ]);

        // 4. Send WhatsApp confirmation message
        $this->checkoutService->handlePaymentSuccess($session, $payment, $order);

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully.',
            'order_number' => $order->order_number,
        ]);
    }
}
