<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;

class ActivityLogger
{
    public function log(
        string $action,
        mixed $entity = null,
        ?string $description = null,
        mixed $oldValues = null,
        mixed $newValues = null,
        ?AuthUser $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog {
        $actor ??= auth('admin')->user() ?? auth()->user();

        $ipAddress ??= request()->ip();
        $userAgent ??= substr((string) request()->userAgent(), 0, 255);

        return ActivityLog::create([
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'entity' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function productCreated(Admin $admin, mixed $product): void
    {
        $this->log('product_created', $product, 'Product "'.$product->name.'" was created.', null, ['name' => $product->name], $admin);
    }

    public function productUpdated(Admin $admin, mixed $product, array $old, array $new): void
    {
        $this->log('product_updated', $product, 'Product "'.$product->name.'" was updated.', $old, $new, $admin);
    }

    public function productDeleted(Admin $admin, mixed $product): void
    {
        $this->log('product_deleted', $product, 'Product "'.$product->name.'" was deleted.', null, ['name' => $product->name], $admin);
    }

    public function priceChanged(Admin $admin, mixed $product, $old, $new): void
    {
        $this->log('price_changed', $product, 'Price changed for "'.$product->name.'".', ['price' => $old], ['price' => $new], $admin);
    }

    public function stockChanged(Admin $admin, mixed $stockable, $old, $new, string $reason = null): void
    {
        $this->log('stock_changed', $stockable, 'Stock updated. '.$reason, ['stock' => $old], ['stock' => $new], $admin);
    }

    public function orderStatusChanged(Admin $admin, mixed $order, $old, $new, string $description = null): void
    {
        $this->log('order_status_changed', $order, $description, ['status' => $old], ['status' => $new], $admin);
    }

    public function refundProcessed(Admin $admin, mixed $refund): void
    {
        $this->log('refund_processed', $refund, 'Refund '.$refund->refund_number.' processed.', null, ['amount' => $refund->amount], $admin);
    }

    public function couponCreated(Admin $admin, mixed $coupon): void
    {
        $this->log('coupon_created', $coupon, 'Coupon '.$coupon->code.' created.', null, ['code' => $coupon->code], $admin);
    }

    public function customerChanged(mixed $admin, User $user, string $action): void
    {
        $this->log('customer_account_'.$action, $user, 'Customer account '.$action.'.', null, ['email' => $user->email], $admin);
    }
}