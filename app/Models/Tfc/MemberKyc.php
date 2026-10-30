<?php

namespace App\Models\Tfc;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberKyc extends Model
{
    use HasFactory;

    protected $table = 'member_kyc';

    protected $fillable = [
        'direct_selling_member_id',
        'document_type',
        'document_number',
        'document_number_last4',
        'front_image_path',
        'back_image_path',
        'pan_number',
        'pan_number_last4',
        'pan_image_path',
        'bank_name',
        'account_number',
        'account_number_last4',
        'ifsc_code',
        'account_holder_name',
        'status',
        'rejection_reason',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'document_number' => 'encrypted',
            'pan_number' => 'encrypted',
            'account_number' => 'encrypted',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(DirectSellingMember::class, 'direct_selling_member_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    public function getMaskedDocumentNumberAttribute(): string
    {
        return '•••• •••• ' . ($this->document_number_last4 ?: '••••');
    }

    public function getMaskedPanAttribute(): string
    {
        return '••••••' . ($this->pan_number_last4 ?: '••••');
    }
}
