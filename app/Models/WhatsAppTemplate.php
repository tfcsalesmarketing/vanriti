<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'meta_template_id',
        'name',
        'category',
        'status',
        'language',
        'header_type',
        'header_text',
        'body_text',
        'footer_text',
        'buttons',
        'rejected_reason',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return strtoupper($this->status) === 'APPROVED';
    }

    public function isPending(): bool
    {
        return strtoupper($this->status) === 'PENDING';
    }

    public function isRejected(): bool
    {
        return strtoupper($this->status) === 'REJECTED';
    }

    /**
     * Count how many dynamic parameters {{1}}, {{2}} are present in body.
     */
    public function getVariableCountAttribute(): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $this->body_text, $matches);
        return count($matches[1] ?? []);
    }

    /**
     * Scope for approved templates.
     */
    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereRaw('UPPER(status) = ?', ['APPROVED']);
    }

    /**
     * Get badge styling for status.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match (strtoupper($this->status)) {
            'APPROVED' => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'bi-check-circle-fill', 'label' => 'Approved'],
            'PENDING' => ['class' => 'bg-warning-subtle text-warning border border-warning-subtle', 'icon' => 'bi-hourglass-split', 'label' => 'Pending Review'],
            'REJECTED' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-x-circle-fill', 'label' => 'Rejected'],
            'PAUSED' => ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'bi-pause-circle', 'label' => 'Paused'],
            default => ['class' => 'bg-light text-dark border', 'icon' => 'bi-info-circle', 'label' => $this->status],
        };
    }
}
