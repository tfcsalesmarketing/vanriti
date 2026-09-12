<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;

class CouponService
{
    /**
     * @return array{valid: bool, message: ?string, discount: float, coupon: ?Coupon}
     */
    public function validate(string $code, Cart $cart, ?User $user): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code.', 'discount' => 0, 'coupon' => null];
        }

        if (! $coupon->is_active) {
            return ['valid' => false, 'message' => 'This coupon is not active.', 'discount' => 0, 'coupon' => $coupon];
        }

        if (! $coupon->isUsable()) {
            return ['valid' => false, 'message' => 'This coupon has expired or reached its usage limit.', 'discount' => 0, 'coupon' => $coupon];
        }

        if ($coupon->min_cart_value > 0 && $cart->subtotal() < (float) $coupon->min_cart_value) {
            return ['valid' => false, 'message' => 'Add more items to use this coupon (minimum '.format_price($coupon->min_cart_value).').', 'discount' => 0, 'coupon' => $coupon];
        }

        if ($coupon->usage_limit && $coupon->usages()->count() >= $coupon->usage_limit) {
            return ['valid' => false, 'message' => 'This coupon has reached its redemption limit.', 'discount' => 0, 'coupon' => $coupon];
        }

        if ($user) {
            if ($coupon->per_customer_limit && $coupon->usesByUser($user->id) >= $coupon->per_customer_limit) {
                return ['valid' => false, 'message' => 'You have already used this coupon.', 'discount' => 0, 'coupon' => $coupon];
            }

            if ($coupon->first_order_only) {
                $hasPriorOrder = $user->orders()->whereNotIn('order_status', ['cancelled', 'failed'])->exists();
                if ($hasPriorOrder) {
                    return ['valid' => false, 'message' => 'This coupon is valid for first-time buyers only.', 'discount' => 0, 'coupon' => $coupon];
                }
            }
        } else {
            if ($coupon->per_customer_limit === 0 && ! $coupon->first_order_only) {
                // guests allowed for general coupons
            }
        }

        $discount = $this->calculateDiscount($coupon, $cart);

        return ['valid' => true, 'message' => 'Coupon applied successfully!', 'discount' => $discount, 'coupon' => $coupon];
    }

    public function calculateDiscount(Coupon $coupon, Cart $cart): float
    {
        $subtotal = $cart->subtotal();
        $discount = 0.0;

        if ($coupon->discount_type === 'percentage') {
            $discount = $subtotal * ((float) $coupon->discount_value / 100);
            if ($coupon->max_discount && $discount > (float) $coupon->max_discount) {
                $discount = (float) $coupon->max_discount;
            }
        } else {
            $discount = (float) $coupon->discount_value;
        }

        return round(min($discount, $subtotal), 2);
    }

    public function recordUsage(Coupon $coupon, User $user, int $orderId, float $discount): void
    {
        $coupon->usages()->create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'discount_amount' => $discount,
        ]);
    }
}