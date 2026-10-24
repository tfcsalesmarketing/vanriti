<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\WhatsAppBotRule;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    public function __construct(
        protected WhatsAppCloudApiService $cloudApi
    ) {}

    /**
     * Inspect incoming message text and execute any matching chatbot rule.
     * Returns true if bot intercepted and handled the message.
     */
    public function handleInbound(WhatsAppConversation $conversation, string $incomingText): bool
    {
        $incomingText = trim($incomingText);
        if ($incomingText === '') {
            return false;
        }

        // Fetch active rules ordered by priority
        $rules = WhatsAppBotRule::where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->matches($incomingText)) {
                continue;
            }

            // Check cooldown
            $cacheKey = "wa_bot_rule_{$rule->id}_{$conversation->phone}";
            if ($rule->cooldown_minutes > 0 && Cache::has($cacheKey)) {
                // In cooldown, skip rule
                continue;
            }

            // Execute rule
            $handled = $this->executeRule($rule, $conversation, $incomingText);

            if ($handled) {
                $rule->increment('hit_count');

                if ($rule->cooldown_minutes > 0) {
                    Cache::put($cacheKey, true, now()->addMinutes($rule->cooldown_minutes));
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Execute the selected bot rule.
     */
    protected function executeRule(WhatsAppBotRule $rule, WhatsAppConversation $conversation, string $incomingText): bool
    {
        $name = $conversation->contact_name ?: 'Customer';
        $phone = $conversation->phone;

        try {
            switch ($rule->action_type) {
                case 'dynamic_order_tracking':
                    return $this->handleOrderTracking($conversation, $name);

                case 'interactive_menu':
                    return $this->handleInteractiveMenu($rule, $conversation, $name);

                case 'catalog':
                    return $this->handleCatalogShowcase($conversation, $name);

                case 'coupon':
                    return $this->handleCouponOffer($rule, $conversation, $name);

                case 'text':
                default:
                    $body = str_replace(
                        ['{name}', '{phone}', '{site_url}'],
                        [$name, $phone, url('/')],
                        $rule->response_text ?: 'Hello {name}, thank you for contacting Vanriti! 🌿'
                    );

                    $res = $this->cloudApi->sendTextMessage($phone, $body);
                    if ($res['success']) {
                        $this->recordBotMessage($conversation, 'text', $body, $res['message_id'] ?? null);
                        return true;
                    }
                    return false;
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp bot execution exception', ['error' => $e->getMessage(), 'rule_id' => $rule->id]);
            return false;
        }
    }

    /**
     * Dynamic order lookup and status response.
     */
    protected function handleOrderTracking(WhatsAppConversation $conversation, string $name): bool
    {
        $rawDigits = preg_replace('/[^0-9]/', '', $conversation->phone);
        $last10 = substr($rawDigits, -10);

        $order = Order::where(function ($q) use ($last10) {
            $q->where('billing_mobile', 'like', "%{$last10}")
              ->orWhere('shipping_mobile', 'like', "%{$last10}");
        })
            ->with(['shipments' => fn ($q) => $q->latest()])
            ->latest()
            ->first();

        if ($order) {
            $statusLabel = strtoupper(str_replace('_', ' ', $order->order_status));
            $shipment = $order->shipments->first();

            $trackingLine = '';
            if ($shipment && $shipment->tracking_number) {
                $courier = $shipment->courier ?: 'Express Carrier';
                $trackingLine = "\n🚚 *Courier:* {$courier}\n📋 *AWB / Tracking:* `{$shipment->tracking_number}`";
            }

            $reply = "📦 *Order Status for #{$order->order_number}*\n\n"
                . "Hello {$name},\n"
                . "Status: *{$statusLabel}*\n"
                . "Order Total: *₹" . number_format($order->grand_total, 2) . "*\n"
                . "Payment: *" . strtoupper($order->payment_status) . "*{$trackingLine}\n\n"
                . "View live order progress:\n" . url('/orders/' . $order->order_number) . "\n\n"
                . "🌿 If you need to speak with our support team, reply to this message anytime!";
        } else {
            $reply = "Hello {$name}! 🌿\n\n"
                . "We couldn't locate any active order linked to this mobile number (*+91 {$last10}*).\n\n"
                . "If you placed an order using another number or have your Order ID (e.g. *#ORD-12345*), please reply with your Order Number and our team will check it right away!";
        }

        $res = $this->cloudApi->sendTextMessage($conversation->phone, $reply);
        if ($res['success']) {
            $this->recordBotMessage($conversation, 'text', $reply, $res['message_id'] ?? null);
            return true;
        }

        return false;
    }

    /**
     * Send interactive buttons menu.
     */
    protected function handleInteractiveMenu(WhatsAppBotRule $rule, WhatsAppConversation $conversation, string $name): bool
    {
        $body = str_replace(
            ['{name}', '{site_url}'],
            [$name, url('/')],
            $rule->response_text ?: "Hello {$name}! Welcome to VANRITI 🌿\nHow can we help you today?"
        );

        $buttons = $rule->buttons ?: [
            ['id' => 'btn_catalog', 'title' => 'Browse Products'],
            ['id' => 'btn_track', 'title' => 'Track Order'],
            ['id' => 'btn_support', 'title' => 'Talk to Care'],
        ];

        $res = $this->cloudApi->sendInteractiveButtonMessage(
            $conversation->phone,
            $body,
            $buttons,
            'VANRITI Ayurveda',
            'Select an option below'
        );

        if ($res['success']) {
            $this->recordBotMessage($conversation, 'interactive', $body, $res['message_id'] ?? null, ['buttons' => $buttons]);
            return true;
        }

        return false;
    }

    /**
     * Send catalogue / store link.
     */
    protected function handleCatalogShowcase(WhatsAppConversation $conversation, string $name): bool
    {
        $res = $this->cloudApi->sendCatalogueMessage(
            $conversation->phone,
            "Hello {$name}! 🌿 Explore our pure Ayurvedic wellness and natural skincare collection."
        );

        if ($res['success']) {
            $this->recordBotMessage($conversation, 'text', "Sent Ayurvedic Product Collection Catalogue 🌿", $res['message_id'] ?? null);
            return true;
        }

        return false;
    }

    /**
     * Send active discount coupon.
     */
    protected function handleCouponOffer(WhatsAppBotRule $rule, WhatsAppConversation $conversation, string $name): bool
    {
        $coupon = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->first();

        $couponCode = $coupon ? $coupon->code : 'VANRITI10';
        $discountDesc = $coupon ? ($coupon->type === 'percent' ? "{$coupon->value}% OFF" : "₹{$coupon->value} OFF") : 'Flat 10% OFF';

        $body = "🎉 *Special Offer Just for You, {$name}!* 🌿\n\n"
            . "Enjoy *{$discountDesc}* on pure Ayurvedic skincare and wellness products!\n\n"
            . "Use Coupon Code: *{$couponCode}*\n"
            . "Shop now at:\n" . url('/shop') . "\n\n"
            . "Offer valid for a limited time at checkout!";

        $res = $this->cloudApi->sendTextMessage($conversation->phone, $body);
        if ($res['success']) {
            $this->recordBotMessage($conversation, 'text', $body, $res['message_id'] ?? null);
            return true;
        }

        return false;
    }

    /**
     * Record bot outbound message in database.
     */
    protected function recordBotMessage(
        WhatsAppConversation $conversation,
        string $type,
        string $body,
        ?string $metaMessageId,
        array $metadata = []
    ): WhatsAppMessage {
        $metadata['is_bot'] = true;
        $metadata['sender_title'] = 'VANRITI Bot';

        $msg = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => $type,
            'body' => $body,
            'meta_message_id' => $metaMessageId,
            'status' => 'sent',
            'metadata' => $metadata,
            'sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_snippet' => \Illuminate\Support\Str::limit($body, 90),
            'last_message_direction' => 'outbound',
        ]);

        return $msg;
    }
}
