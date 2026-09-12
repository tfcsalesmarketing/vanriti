<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'courier',
        'tracking_number',
        'awb_number',
        'shipping_method',
        'status',
        'weight',
        'dimensions',
        'shipped_at',
        'estimated_delivery',
        'delivered_at',
        'shipping_charge',
        'notes',
        // ShipMojo fields
        'shipmojo_order_id',
        'shipmojo_reference_id',
        'lr_number',
        'courier_service',
        'shipmojo_pushed_at',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at'         => 'datetime',
            'estimated_delivery' => 'datetime',
            'delivered_at'       => 'datetime',
            'shipmojo_pushed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class)->orderByDesc('event_date');
    }

    public function isPushedToShipMojo(): bool
    {
        return ! empty($this->shipmojo_pushed_at);
    }

    public function hasAwb(): bool
    {
        return ! empty($this->awb_number);
    }
}