<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StockNotificationService
{
    public function __construct(
        protected WhatsAppCloudApiService $whatsApp,
    ) {}

    /**
     * Subscribe user or guest to back-in-stock alerts.
     */
    public function subscribe(array $data, ?User $user = null): array
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $variantId = ! empty($data['product_variant_id']) ? (int) $data['product_variant_id'] : null;
        $phone = preg_replace('/[^0-9]/', '', (string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        if (empty($phone) && empty($email)) {
            return [
                'success' => false,
                'message' => 'Please provide a valid WhatsApp mobile number or email address.',
            ];
        }

        // Check if an existing pending request already exists
        $existing = StockNotification::query()
            ->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->where('status', 'pending')
            ->where(function ($q) use ($phone, $email) {
                if ($phone) {
                    $q->where('phone', $phone);
                }
                if ($email) {
                    $q->orWhere('email', $email);
                }
            })
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'You are already registered! We will notify you immediately once this item is back in stock.',
            ];
        }

        StockNotification::create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'user_id' => $user?->id,
            'phone' => $phone ?: null,
            'email' => $email ?: null,
            'status' => 'pending',
            'channel' => ! empty($phone) ? 'whatsapp' : 'email',
        ]);

        return [
            'success' => true,
            'message' => 'Thank you! We will message you on WhatsApp the moment this luxury edition is back in stock.',
        ];
    }

    /**
     * Notify subscribers when variant stock is restocked.
     */
    public function notifySubscribersForVariant(ProductVariant $variant): int
    {
        if ($variant->stock <= 0) {
            return 0;
        }

        $notifications = StockNotification::query()
            ->pending()
            ->where('product_variant_id', $variant->id)
            ->with(['product', 'variant'])
            ->get();

        return $this->dispatchNotifications($notifications, $variant->product, $variant);
    }

    /**
     * Notify subscribers when a product is restocked.
     */
    public function notifySubscribersForProduct(Product $product): int
    {
        $notifications = StockNotification::query()
            ->pending()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->with(['product'])
            ->get();

        return $this->dispatchNotifications($notifications, $product, null);
    }

    /**
     * Send alerts and update notification records.
     */
    protected function dispatchNotifications($notifications, Product $product, ?ProductVariant $variant): int
    {
        $count = 0;
        $productUrl = route('product.show', $product->slug);
        $itemName = $product->name . ($variant && $variant->name ? ' (' . $variant->name . ')' : '');

        $templateName = (string) setting('whatsapp_template_back_in_stock', 'product_back_in_stock');
        $useTemplate = (bool) setting('whatsapp_template_back_in_stock_enabled', false);

        foreach ($notifications as $notification) {
            if (! empty($notification->phone)) {
                $text = "🌿 Great news from Vanriti!\n\n*{$itemName}* is back in stock! Only limited pieces available.\n\nTap below to order now before it sells out again:\n{$productUrl}";

                try {
                    $res = null;

                    if ($useTemplate && $templateName !== '') {
                        $components = [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $itemName],
                                    ['type' => 'text', 'text' => $productUrl],
                                ],
                            ],
                        ];
                        $res = $this->whatsApp->sendTemplateMessage($notification->phone, $templateName, 'en', $components);
                    }

                    if (empty($res['success'])) {
                        $res = $this->whatsApp->sendTextMessage($notification->phone, $text);
                    }

                    if ($res['success'] ?? false) {
                        $notification->update([
                            'status' => 'notified',
                            'notified_at' => now(),
                        ]);
                        $count++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Stock notification dispatch failed', [
                        'phone' => $notification->phone,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $notification->update([
                    'status' => 'notified',
                    'notified_at' => now(),
                ]);
                $count++;
            }
        }

        return $count;
    }
}
