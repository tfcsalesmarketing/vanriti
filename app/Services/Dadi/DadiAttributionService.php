<?php

namespace App\Services\Dadi;

use App\Models\DadiConversation;
use App\Models\DadiRecommendationEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Thin, privacy-conscious attribution layer that sits beside (never replaces)
 * the existing ecommerce analytics pipeline.
 *
 *   - impressions are created at render time, deduplicated per conversation +
 *     product, so reload / back-forward never double-count.
 *   - click / add_to_cart / buy_now events are appended with their own opaque
 *     references for backend-verified attribution.
 *   - purchase events are created by recordPurchaseForOrder (best-effort,
 *     try/catch; order outcome is never affected).
 *   - guest→user reconciliation backfills session_id rows on login.
 *
 * The impression reference embedded in the card is the stable, opaque
 * identifier the frontend uses for click tracking; it is validated against
 * the conversation's ownership before any downstream event is recorded.
 */
class DadiAttributionService
{
    /**
     * Get or create a deduped impression row for the given conversation +
     * product combination. Reloads and back-forward navigation are safe
     * because the unique dedupe_key prevents duplicate rows.
     */
    public function impressionFor(DadiConversation $conversation, Product $product): DadiRecommendationEvent
    {
        if (! config('dadi.attribution.enabled', true)) {
            return new DadiRecommendationEvent([
                'reference' => Str::random(26),
                'action' => DadiRecommendationEvent::ACTION_IMPRESSION,
            ]);
        }

        $key = "imp:{$conversation->getKey()}:{$product->getKey()}";

        return DadiRecommendationEvent::firstOrCreate(
            ['dedupe_key' => $key],
            [
                'reference' => Str::random(26),
                'conversation_id' => $conversation->getKey(),
                'product_id' => $product->getKey(),
                'action' => DadiRecommendationEvent::ACTION_IMPRESSION,
                'session_id' => $conversation->session_id,
                'user_id' => $conversation->user_id,
            ],
        );
    }

    /**
     * Record a click / add_to_cart / buy_now event linked to an impression
     * reference. Ownership of the reference is verified against the identity;
     * if verification fails the event is silently ignored.
     *
     * @param  string  $action  One of click, add_to_cart, buy_now
     * @param  User|null  $user  Current authenticated user (nullable for guests)
     * @param  string|null  $sessionId  Current session id (nullable for authed users)
     * @param  Product|null  $product  The product the event relates to. When null the
     *                                 product is resolved from the impression reference.
     * @param  string|null  $reference  The impression reference to link against
     */
    public function record(
        string $action,
        ?User $user,
        ?string $sessionId,
        ?Product $product = null,
        ?string $reference = null,
        ?int $cartId = null,
        ?int $cartItemId = null,
        ?string $guestCartKey = null,
    ): void {
        if (! config('dadi.attribution.enabled', true)) {
            return;
        }

        $impression = $this->resolveImpression($reference, $user, $sessionId);

        if (! $impression) {
            return;
        }

        $product ??= $impression->product;

        if (! $product instanceof Product) {
            return;
        }

        DadiRecommendationEvent::create([
            'reference' => Str::random(26),
            'conversation_id' => $impression->conversation_id,
            'product_id' => $product->getKey(),
            'action' => $action,
            'session_id' => $sessionId,
            'guest_cart_key' => $guestCartKey,
            'user_id' => $user?->id,
            'cart_id' => $cartId,
            'cart_item_id' => $cartItemId,
        ]);
    }

    /**
     * Batch-get or create deduped impression rows for the given conversation
     * and product set. Used by renderStream to avoid N individual firstOrCreate
     * calls per page render.
     *
     * @param  array<int,int>  $productIds
     * @return array<int,DadiRecommendationEvent>
     */
    public function impressionsFor(DadiConversation $conversation, array $productIds): array
    {
        $productIds = array_values(array_filter(array_unique(array_map('intval', $productIds)), fn ($id) => $id > 0));

        if ($productIds === []) {
            return [];
        }

        if (! config('dadi.attribution.enabled', true)) {
            $map = [];
            foreach ($productIds as $id) {
                $map[$id] = new DadiRecommendationEvent([
                    'reference' => Str::random(26),
                    'action' => DadiRecommendationEvent::ACTION_IMPRESSION,
                ]);
            }

            return $map;
        }

        $keys = [];
        foreach ($productIds as $id) {
            $keys[$id] = "imp:{$conversation->getKey()}:{$id}";
        }

        $existing = DadiRecommendationEvent::query()
            ->whereIn('dedupe_key', $keys)
            ->get()
            ->keyBy('product_id');

        $missing = [];
        $now = now();

        foreach ($productIds as $id) {
            if (! isset($existing[$id])) {
                $missing[] = [
                    'reference' => Str::random(26),
                    'conversation_id' => $conversation->getKey(),
                    'product_id' => $id,
                    'action' => DadiRecommendationEvent::ACTION_IMPRESSION,
                    'dedupe_key' => $keys[$id],
                    'session_id' => $conversation->session_id,
                    'user_id' => $conversation->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($missing !== []) {
            DadiRecommendationEvent::query()->insertOrIgnore($missing);

            $fresh = DadiRecommendationEvent::query()
                ->whereIn('dedupe_key', array_column($missing, 'dedupe_key'))
                ->get()
                ->keyBy('product_id');

            foreach ($fresh as $id => $event) {
                $existing[$id] = $event;
            }
        }

        $map = [];
        foreach ($productIds as $id) {
            if (isset($existing[$id])) {
                $map[$id] = $existing[$id];
            } else {
                // Fallback (race where both insert and reload failed; will be
                // caught by the single-record path in toCard if needed).
                $map[$id] = new DadiRecommendationEvent([
                    'reference' => Str::random(26),
                    'action' => DadiRecommendationEvent::ACTION_IMPRESSION,
                ]);
            }
        }

        return $map;
    }

    /**
     * Record purchase attribution for every order item. Best-effort:
     * failures are caught and never affect the order outcome.
     *
     * Idempotent: duplicate invocations do not create extra purchase rows.
     */
    public function recordPurchaseForOrder(Order $order): void
    {
        if (! config('dadi.attribution.enabled', true)) {
            return;
        }

        try {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                // Find the most recent add_to_cart / buy_now event for this
                // product owned by the order's user.
                $candidate = DadiRecommendationEvent::query()
                    ->where('user_id', $order->user_id)
                    ->where('product_id', $item->product_id)
                    ->whereIn('action', [
                        DadiRecommendationEvent::ACTION_ADD_TO_CART,
                        DadiRecommendationEvent::ACTION_BUY_NOW,
                    ])
                    ->orderByDesc('created_at')
                    ->first();

                if (! $candidate || ! $candidate->conversation_id) {
                    continue;
                }

                // Idempotent: unique (order_number, product_id) prevents
                // duplicates if this hook is ever invoked more than once.
                DadiRecommendationEvent::query()->firstOrCreate(
                    [
                        'order_number' => $order->order_number,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'reference' => Str::random(26),
                        'conversation_id' => $candidate->conversation_id,
                        'action' => DadiRecommendationEvent::ACTION_PURCHASE,
                        'session_id' => null,
                        'user_id' => $order->user_id,
                    ],
                );
            }
        } catch (\Throwable) {
            // Best-effort; must never affect the order.
        }
    }

    /**
     * Backfill user_id on recommendation events created during a guest session
     * when the guest logs in and their cart is merged.
     *
     * The stable guest-cart key carried on the cart.add / buy_now requests is
     * the primary identifier (it survives session regeneration); the framework
     * session id is matched as a fallback so page-level impressions and clicks
     * reconcile too.
     */
    public function reconcileSessionToUser(string $guestCartKey, int $userId, ?string $sessionId = null): void
    {
        if (! config('dadi.attribution.enabled', true)) {
            return;
        }

        DadiRecommendationEvent::query()
            ->whereNull('user_id')
            ->where(function ($query) use ($guestCartKey, $sessionId) {
                $query->where('guest_cart_key', $guestCartKey);

                if ($sessionId) {
                    $query->orWhere('session_id', $sessionId);
                }
            })
            ->update(['user_id' => $userId]);
    }

    /**
     * Resolve the impression that owns the given reference, and verify the
     * identity matches. Returns null when the reference is unknown or the
     * identity does not match (forged / another user's reference).
     */
    protected function resolveImpression(
        ?string $reference,
        ?User $user,
        ?string $sessionId,
    ): ?DadiRecommendationEvent {
        if (! $reference) {
            return null;
        }

        $impression = DadiRecommendationEvent::query()
            ->where('reference', $reference)
            ->where('action', DadiRecommendationEvent::ACTION_IMPRESSION)
            ->first();

        if (! $impression || ! $impression->conversation) {
            return null;
        }

        if (! $this->ownedBy($impression->conversation, $user, $sessionId)) {
            return null;
        }

        return $impression;
    }

    /**
     * Verify that the conversation is owned by the given identity.
     */
    protected function ownedBy(DadiConversation $conversation, ?User $user, ?string $sessionId): bool
    {
        if ($user && (int) $conversation->user_id === (int) $user->id) {
            return true;
        }

        if (
            $conversation->session_id
            && $sessionId
            && $conversation->session_id === $sessionId
        ) {
            return true;
        }

        return false;
    }
}
