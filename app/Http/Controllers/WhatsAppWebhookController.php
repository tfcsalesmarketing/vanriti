<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppWebhookLog;
use App\Services\WhatsAppChatService;
use App\Services\WhatsAppCheckoutService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService,
        protected WhatsAppCheckoutService $checkoutService,
        protected WhatsAppChatService $chatService,
    ) {}

    /**
     * Meta Webhook Verification (GET).
     * Responds to Meta's challenge handshake.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->input('hub_mode', $request->input('hub.mode'));
        $verifyToken = $request->input('hub_verify_token', $request->input('hub.verify_token'));
        $challenge = $request->input('hub_challenge', $request->input('hub.challenge'));

        $configuredToken = (string) (setting('whatsapp_webhook_verify_token', '') ?: env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'vanriti_whatsapp_secret_token'));

        if ($request->has('diagnostics') && $verifyToken === $configuredToken) {
            $secret = (string) (secret_setting('whatsapp_app_secret', '') ?: env('WHATSAPP_APP_SECRET', ''));
            $recentLogs = WhatsAppWebhookLog::latest()->take(5)->get(['id', 'from_phone', 'event_type', 'created_at']);
            $recentSessions = \App\Models\WhatsAppCheckoutSession::latest()->take(5)->get(['id', 'phone', 'status', 'customer_name', 'created_at']);

            return response(json_encode([
                'api_configured' => $this->apiService->isConfigured(),
                'phone_number_id' => $this->apiService->phoneNumberId(),
                'has_access_token' => ! empty($this->apiService->accessToken()),
                'has_app_secret' => ! empty($secret),
                'recent_webhook_logs' => $recentLogs,
                'recent_checkout_sessions' => $recentSessions,
                'php_version' => PHP_VERSION,
                'app_env' => config('app.env'),
            ]), 200, ['Content-Type' => 'application/json']);
        }

        if ($request->filled('test_send') && $verifyToken === $configuredToken) {
            $to = (string) $request->input('test_send');
            $res = $this->apiService->sendTextMessage($to, "VANRITI Live Test from Hostinger: Cloud API is connected! 🌿");
            return response(json_encode($res), 200, ['Content-Type' => 'application/json']);
        }

        if ($mode === 'subscribe' && $verifyToken === $configuredToken) {
            return response((string) $challenge, 200, ['Content-Type' => 'text/plain']);
        }

        Log::warning('WhatsApp webhook verification rejected: token mismatch', [
            'received' => $verifyToken,
            'mode' => $mode,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Meta Webhook Event Processing (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $signature = $request->header('x-hub-signature-256');

        $isSigValid = $this->apiService->verifyWebhookSignature($rawContent, $signature);

        // Immediate audit log of every webhook hit
        try {
            WhatsAppWebhookLog::create([
                'message_id' => 'req_' . uniqid('', true),
                'from_phone' => (string) ($request->ip() ?: 'unknown'),
                'event_type' => $signature ? ($isSigValid ? 'sig_valid' : 'sig_invalid') : 'no_sig',
                'payload' => [
                    'has_sig' => ! empty($signature),
                    'content_len' => strlen($rawContent),
                    'body_keys' => array_keys($request->all()),
                ],
                'processed' => $isSigValid,
            ]);
        } catch (\Throwable) {}

        // Signature check
        if (! $isSigValid) {
            Log::warning('WhatsApp webhook rejected: invalid X-Hub-Signature-256');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $payload = $request->all();

        // Check if event is from whatsapp_business_account
        $entry = $payload['entry'][0] ?? null;
        if (! $entry) {
            return response()->json(['status' => 'no entry']);
        }

        $changes = $entry['changes'][0]['value'] ?? null;
        if (! $changes) {
            return response()->json(['status' => 'no change']);
        }

        // Process status delivery notifications (sent, delivered, read, failed)
        if (! empty($changes['statuses'])) {
            foreach ($changes['statuses'] as $status) {
                try {
                    $this->chatService->recordStatusUpdate($status);
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp status processing exception', ['error' => $e->getMessage()]);
                }
            }
        }

        $messages = $changes['messages'] ?? [];
        if (empty($messages)) {
            return response()->json(['status' => 'status processed']);
        }

        $senderProfileName = $changes['contacts'][0]['profile']['name'] ?? null;

        foreach ($messages as $msg) {
            $messageId = (string) ($msg['id'] ?? '');
            $from = (string) ($msg['from'] ?? '');
            $type = (string) ($msg['type'] ?? '');
            $waId = (string) ($changes['contacts'][0]['wa_id'] ?? $from);

            // Deduplication by provider message ID
            if ($messageId !== '' && WhatsAppWebhookLog::isDuplicate($messageId)) {
                Log::info('WhatsApp webhook: ignoring duplicate message', ['id' => $messageId]);
                continue;
            }

            WhatsAppWebhookLog::create([
                'message_id' => $messageId ?: ('gen_' . uniqid('', true)),
                'from_phone' => $from,
                'event_type' => $type,
                'payload' => $msg,
                'processed' => true,
            ]);

            // Record message in WhatsApp Inbox & Live Chat engine
            try {
                $this->chatService->recordInboundMessage($from, $msg, $waId, $senderProfileName);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp chat message recording exception', ['error' => $e->getMessage()]);
            }

            try {
                // Check if user clicked "Message Business" on a product card or shared an item
                $referredProduct = $msg['context']['referred_product']
                    ?? ($msg['referral']['product'] ?? ($msg['referral'] ?? null));

                $referredRetailerId = trim((string) (
                    $referredProduct['product_retailer_id']
                    ?? ($referredProduct['retailer_id'] ?? ($referredProduct['id'] ?? ''))
                ));

                if ($type === 'order') {
                    // Step 2: Receive customer's multi-product cart
                    $orderData = $msg['order'] ?? ($msg['interactive']['order'] ?? []);
                    $productItems = $orderData['product_items'] ?? [];
                    $this->checkoutService->handleIncomingCart($from, $waId, $productItems, $messageId);
                } elseif (! empty($referredRetailerId)) {
                    // Single product from Catalog ("Message Business")
                    Log::info('WhatsApp webhook: single product received from catalog', [
                        'from' => $from,
                        'retailer_id' => $referredRetailerId,
                    ]);
                    $this->checkoutService->handleIncomingCart($from, $waId, [
                        ['product_retailer_id' => $referredRetailerId, 'quantity' => 1],
                    ], $messageId);
                } elseif ($type === 'interactive') {
                    $interactive = $msg['interactive'] ?? [];
                    $buttonId = $interactive['button_reply']['id']
                        ?? ($interactive['list_reply']['id'] ?? ($interactive['nfm_reply']['response_json'] ?? null));
                    $buttonTitle = $interactive['button_reply']['title']
                        ?? ($interactive['list_reply']['title'] ?? null);

                    $this->checkoutService->processCustomerInput($from, $buttonTitle, $buttonId, $messageId);
                } elseif ($type === 'text') {
                    $text = $msg['text']['body'] ?? '';
                    $this->checkoutService->processCustomerInput($from, $text, null, $messageId);
                }
            } catch (\Throwable $e) {
                Log::error('WhatsApp message processing exception', [
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
