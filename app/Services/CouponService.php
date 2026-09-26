<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon against a cart.
     *
     * @param  bool  $lock  Row-lock the coupon and re-read its usage counters.
     *                      MUST be true when called inside the order-placement
     *                      transaction so the redemption limit cannot be raced.
     * @return array{valid: bool, message: ?string, discount: float, coupon: ?Coupon}
     */
    public function validate(string $code, Cart $cart, ?User $user, bool $lock = false): array
    {
        $couponQuery = Coupon::where('code', $code);

        if ($lock) {
            $couponQuery->lockForUpdate();
        }

        $coupon = $couponQuery->first();

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

        // The global redemption limit is read under the row lock so two
        // concurrent checkouts cannot both pass the final available use.
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
        } elseif ($coupon->first_order_only) {
            // A guest can never satisfy "first order only" and cannot be counted
            // against a per-customer limit, so the offer is not offered to guests
            // rather than being silently applied to an untracked redemption.
            return ['valid' => false, 'message' => 'This offer is for first-time buyers only. Please sign in to use it.', 'discount' => 0, 'coupon' => $coupon];
        }

        $discount = $this->calculateDiscount($coupon, $cart);

        if ($discount <= 0) {
            return ['valid' => false, 'message' => 'This coupon does not apply to the items in your cart.', 'discount' => 0, 'coupon' => $coupon];
        }

        return ['valid' => true, 'message' => 'Coupon applied successfully!', 'discount' => $discount, 'coupon' => $coupon];
    }

    public function calculateDiscount(Coupon $coupon, Cart $cart): float
    {
        // A coupon restricted to specific products/categories only discounts the
        // eligible lines; the previous implementation applied it to the whole
        // cart, which let a restricted coupon erase far more than it should.
        $base = $this->discountableSubtotal($coupon, $cart);

        if ($base <= 0) {
            return 0.0;
        }

        $discount = 0.0;

        if ($coupon->discount_type === 'percentage') {
            $discount = $base * ((float) $coupon->discount_value / 100);
            if ($coupon->max_discount && $discount > (float) $coupon->max_discount) {
                $discount = (float) $coupon->max_discount;
            }
        } else {
            $discount = (float) $coupon->discount_value;
        }

        return round(min($discount, $base), 2);
    }

    /**
     * Subtotal of the cart lines this coupon is allowed to discount. When the
     * coupon has no product/category restrictions the whole cart counts.
     */
    protected function discountableSubtotal(Coupon $coupon, Cart $cart): float
    {
        $productIds = $coupon->products()->pluck('products.id');
        $categoryIds = $coupon->categories()->pluck('categories.id');

        if ($productIds->isEmpty() && $categoryIds->isEmpty()) {
            return (float) $cart->subtotal();
        }

        $eligible = $cart->items()
            ->with('product.categories')
            ->get()
            ->filter(fn ($item) => $this->itemIsEligible($item, $productIds, $categoryIds));

        return (float) $eligible->sum(fn ($item) => $item->subtotal());
    }

    /**
     * Restrictions are a union: a line qualifies if it is either an explicitly
     * listed product or a member of a listed category.
     */
    protected function itemIsEligible(mixed $item, Collection $productIds, Collection $categoryIds): bool
    {
        if ($productIds->contains($item->product_id)) {
            return true;
        }

        if ($categoryIds->isEmpty() || ! $item->product) {
            return false;
        }

        return $item->product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();
    }

    /**
     * Record a redemption. Called from inside the order transaction, so the
     * limits are re-verified under the coupon row lock that validate() took:
     * check and use are therefore a single atomic step.
     */
    public function recordUsage(Coupon $coupon, User $user, int $orderId, float $discount): void
    {
        DB::transaction(function () use ($coupon, $user, $orderId, $discount) {
            $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

            if (! $locked) {
                throw new \RuntimeException('This coupon is no longer available.');
            }

            if ($locked->usage_limit && $locked->usages()->count() >= $locked->usage_limit) {
                throw new \RuntimeException('This coupon has reached its redemption limit.');
            }

            if ($locked->per_customer_limit && $locked->usesByUser($user->id) >= $locked->per_customer_limit) {
                throw new \RuntimeException('You have already used this coupon.');
            }

            $locked->usages()->create([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'discount_amount' => $discount,
            ]);
        });
    }
}
