<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    protected $fillable = [
        'refund_number',
        'return_request_id',
        'order_id',
        'payment_id',
        'user_id',
        'amount',
        'type',
        'status',
        'method',
        'gateway_reference',
        'reason',
        'admin_note',
        'requested_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class);
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['requested', 'under_review', 'approved', 'processing']);
    }
}