<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cart extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'session_id',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function itemsCount(): int
    {
        return (int) $this->items()->count();
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum(\DB::raw('unit_price * quantity'));
    }
}