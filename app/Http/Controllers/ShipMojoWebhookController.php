<?php

namespace App\Http\Controllers;

use App\Services\ShipMojoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server ShipMojo webhook receiver.
 *
 * ShipMojo pushes order-status updates to this endpoint whenever a shipment
 * moves through the fulfilment pipeline (courier assigned, picked up, in
 * transit, out for delivery, delivered, cancelled / RTO). Each callback is
 * applied idempotently: replays resolve to the same state and never send a
 * duplicate customer notification.
 */
class ShipMojoWebhookController extends Controller
{
    public function handle(Request $request, ShipMojoService $shipmojo): JsonResponse
    {
        if (! $this->authenticate($request)) {
            Log::warning('ShipMojo webhook rejected: missing or invalid credentials.');

            return response()->json(['result' => '0', 'message' => 'Unauthorized'], 401);
        }

        if (! (bool) setting('shipmojo_webhook_enabled', false)) {
            return response()->json(['result' => '0', 'message' => 'Webhook disabled'], 200);
        }

        $payload = $request->json()->all();
        Log::info('ShipMojo webhook payload received', ['payload' => $payload]);

        try {
            $result = $shipmojo->applyWebhook($payload);

            if (($result['result'] ?? '0') === '1') {
                return response()->json(['result' => '1', 'status' => 'ok']);
            }

            return response()->json([
                'result' => '0',
                'message' => $result['message'] ?? 'Could not apply webhook',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('ShipMojo webhook processing failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['result' => '0', 'message' => 'Internal error'], 500);
        }
    }

    /**
     * Verify the callback actually came from ShipMojo.
     *
     * ShipMojo deployments sign callbacks differently, so a few common
     * mechanisms are accepted (whichever matches the configured secret):
     *  - "public-key" request header equal to the secret
     *  - HMAC-SHA256 signature of the raw body (x-shipmojo-signature /
     *    x-webhook-signature / x-signature)
     *  - a "webhook_secret" field inside the JSON body
     *
     * Fails closed: when no secret is configured the callback is rejected so
     * an unconfigured store never mutates orders or shipments from an
     * unauthenticated HTTP request.
     */
    protected function authenticate(Request $request): bool
    {
        $secret = (string) secret_setting('shipmojo_webhook_secret', '');
        $publicKeySetting = (string) secret_setting('shipmojo_public_key', '');

        // If no explicit webhook secret is defined, allow authenticating against the ShipMojo Public Key
        if ($secret === '' && $publicKeySetting !== '') {
            $secret = $publicKeySetting;
        }

        if ($secret === '') {
            Log::warning('ShipMojo webhook received but no secret or public key configured; rejecting request.');

            return false;
        }

        $publicKey = (string) $request->header('public-key', '');

        if ($publicKey !== '' && (hash_equals($secret, $publicKey) || ($publicKeySetting !== '' && hash_equals($publicKeySetting, $publicKey)))) {
            return true;
        }

        $signatureHeader = $request->header('x-shipmojo-signature')
            ?? $request->header('x-webhook-signature')
            ?? $request->header('x-signature');

        if ($signatureHeader) {
            $computed = hash_hmac('sha256', (string) $request->getContent(), $secret);

            if (hash_equals($computed, (string) $signatureHeader)) {
                return true;
            }
        }

        $bodySecret = (string) $request->input('webhook_secret', $request->input('secret', ''));

        return $bodySecret !== '' && (hash_equals($secret, $bodySecret) || ($publicKeySetting !== '' && hash_equals($publicKeySetting, $bodySecret)));
    }
}