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
     * ShipMojo deployments sign callbacks differently, so several mechanisms
     * are supported:
     *  - "token" / "secret" / "key" in the query string or JSON payload
     *  - "public-key" / "public_key" / "x-api-key" / "api-key" request header
     *  - Authorization: Bearer <token>
     *  - HMAC-SHA256 signature of the raw body (x-shipmojo-signature /
     *    x-webhook-signature / x-signature)
     *
     * Fails closed: when neither secret nor public key is configured the callback
     * is rejected so an unconfigured store never mutates orders or shipments.
     */
    protected function authenticate(Request $request): bool
    {
        $secret = (string) secret_setting('shipmojo_webhook_secret', '');
        $publicKeySetting = (string) secret_setting('shipmojo_public_key', '');

        // If no explicit webhook secret is defined, allow authenticating against the ShipMojo Public Key
        if ($secret === '' && $publicKeySetting !== '') {
            $secret = $publicKeySetting;
        }

        if ($secret === '' && $publicKeySetting === '') {
            Log::warning('ShipMojo webhook received but no secret or public key configured; rejecting request.');

            return false;
        }

        $validTokens = array_values(array_filter(array_unique([$secret, $publicKeySetting])));

        // 1. Check Query Parameter or JSON/POST body token (e.g. ?token=... / ?secret=...)
        $inputToken = (string) $request->input('token',
            $request->input('webhook_secret',
                $request->input('secret',
                    $request->input('key',
                        $request->input('public_key',
                            $request->input('api_key', ''))))));

        if ($inputToken !== '') {
            foreach ($validTokens as $expected) {
                if (hash_equals($expected, $inputToken)) {
                    return true;
                }
            }
        }

        // 2. Check Request Headers (public-key, public_key, x-api-key, api-key, Bearer token)
        $headerToken = (string) ($request->header('public-key')
            ?? $request->header('public_key')
            ?? $request->header('x-api-key')
            ?? $request->header('api-key')
            ?? $request->bearerToken()
            ?? '');

        if ($headerToken !== '') {
            foreach ($validTokens as $expected) {
                if (hash_equals($expected, $headerToken)) {
                    return true;
                }
            }
        }

        // 3. Check HMAC-SHA256 signature header if provided
        $signatureHeader = $request->header('x-shipmojo-signature')
            ?? $request->header('x-webhook-signature')
            ?? $request->header('x-signature');

        if ($signatureHeader) {
            foreach ($validTokens as $expected) {
                $computed = hash_hmac('sha256', (string) $request->getContent(), $expected);

                if (hash_equals($computed, (string) $signatureHeader)) {
                    return true;
                }
            }
        }

        return false;
    }
}