<?php

namespace App\Models\Tfc;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DirectSellingMember extends Model
{
    use HasFactory;

    protected $table = 'direct_selling_members';

    protected $fillable = [
        'user_id',
        'member_id',
        'sponsor_id',
        'placement_parent_id',
        'placement_position',
        'registration_date',
        'membership_status',
        'activation_status',
        'kyc_status',
        'mobile_verification_status',
        'registered_ip',
        'first_login_at',
        'last_login_at',
        // Aliases for seamless interaction
        'tfc_member_id',
        'status',
        'joined_at',
        'registration_ip',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Aliases & Accessors
     */
    public function getTfcMemberIdAttribute(): ?string
    {
        return $this->attributes['member_id'] ?? null;
    }

    public function setTfcMemberIdAttribute($value): void
    {
        $this->attributes['member_id'] = $value;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['membership_status'] ?? 'active';
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['membership_status'] = $value;
    }

    public function getJoinedAtAttribute(): ?\Carbon\Carbon
    {
        if (isset($this->attributes['registration_date'])) {
            return \Carbon\Carbon::parse($this->attributes['registration_date']);
        }
        return $this->created_at;
    }

    public function setJoinedAtAttribute($value): void
    {
        $this->attributes['registration_date'] = $value ? \Carbon\Carbon::parse($value)->toDateString() : now()->toDateString();
    }

    public function getRegistrationIpAttribute(): ?string
    {
        return $this->attributes['registered_ip'] ?? null;
    }

    public function setRegistrationIpAttribute($value): void
    {
        $this->attributes['registered_ip'] = $value;
    }

    public function getFullNameAttribute(): string
    {
        return $this->user?->name ?? 'TFC Member';
    }

    public function getMobileAttribute(): string
    {
        return $this->user?->phone ?? '';
    }

    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? '';
    }

    /**
     * Scope to find by public Member ID
     */
    public function scopeByMemberId(Builder $query, string $id): Builder
    {
        return $query->where('member_id', $id);
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_id');
    }

    public function placementParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'placement_parent_id');
    }

    public function sponsoredMembers(): HasMany
    {
        return $this->hasMany(self::class, 'sponsor_id');
    }

    public function placementChildren(): HasMany
    {
        return $this->hasMany(self::class, 'placement_parent_id');
    }

    public function orangeChild(): HasOne
    {
        return $this->hasOne(self::class, 'placement_parent_id')->where('placement_position', 'orange');
    }

    public function blueChild(): HasOne
    {
        return $this->hasOne(self::class, 'placement_parent_id')->where('placement_position', 'blue');
    }

    public function kyc(): HasOne
    {
        return $this->hasOne(MemberKyc::class, 'direct_selling_member_id')->latestOfMany();
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(MemberKyc::class, 'direct_selling_member_id');
    }

    public function treeNodes(): HasMany
    {
        return $this->hasMany(MemberTreeNode::class, 'ancestor_id');
    }

    public function ancestors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'member_tree_nodes', 'descendant_id', 'ancestor_id')
            ->withPivot(['depth', 'group_leg'])
            ->withTimestamps();
    }

    public function descendants(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'member_tree_nodes', 'ancestor_id', 'descendant_id')
            ->withPivot(['depth', 'group_leg'])
            ->withTimestamps();
    }

    public function isOrange(): bool
    {
        return $this->placement_position === 'orange';
    }

    public function isBlue(): bool
    {
        return $this->placement_position === 'blue';
    }

    public function isActive(): bool
    {
        return $this->membership_status === 'active';
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === 'approved';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->mobile;
    }

    public function getReferralUrlAttribute(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/tfc/member/register?sponsor={$this->member_id}";
    }
}
