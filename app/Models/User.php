<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_verified_at',
        'avatar',
        'status',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function dadiConversations(): HasMany
    {
        return $this->hasMany(DadiConversation::class);
    }

    public function consentPreference(): HasOne
    {
        return $this->hasOne(ConsentPreference::class);
    }

    public function consentChoices(): HasMany
    {
        return $this->hasMany(ConsentChoice::class);
    }

    public function whatsappConversation(): HasOne
    {
        return $this->hasOne(WhatsAppConversation::class);
    }

    public function directSellingMember(): HasOne
    {
        return $this->hasOne(\App\Models\Tfc\DirectSellingMember::class);
    }

    public function isTfcMember(): bool
    {
        return $this->directSellingMember()->exists();
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class, 'owner_id')->where('owner_type', self::class);
    }

    public function activeCart(): HasOne
    {
        return $this->hasOne(Cart::class, 'owner_id')
            ->where('owner_type', self::class)
            ->latestOfMany();
    }

    public function getCleanPhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', (string) $this->phone);
        if (strlen($clean) === 10) {
            return '91' . $clean;
        }

        return $clean;
    }

    public function getWhatsAppDirectUrl(?string $message = null): ?string
    {
        $clean = $this->getCleanPhone();
        if (! $clean) {
            return null;
        }

        $text = $message ?: "Hi {$this->name}, this is Vanriti support.";

        return "https://wa.me/{$clean}?text=" . rawurlencode($text);
    }

    public function getCustomerSegment(): array
    {
        $ordersCount = $this->orders()->whereNotIn('order_status', ['cancelled'])->count();
        $totalSpent = (float) $this->orders()->whereNotIn('order_status', ['cancelled'])->sum('grand_total');

        if ($totalSpent >= 2500 || $ordersCount >= 3) {
            return [
                'key' => 'vip',
                'label' => 'VIP Champion',
                'badge' => 'primary',
                'icon' => 'bi-stars',
                'description' => 'High lifetime value shopper',
            ];
        }

        if ($ordersCount >= 2) {
            return [
                'key' => 'repeat',
                'label' => 'Repeat Buyer',
                'badge' => 'info',
                'icon' => 'bi-arrow-repeat',
                'description' => 'Multi-order returning customer',
            ];
        }

        if ($ordersCount === 1) {
            return [
                'key' => 'first_time',
                'label' => 'First-Time Buyer',
                'badge' => 'success',
                'icon' => 'bi-bag-check',
                'description' => 'Single order completed',
            ];
        }

        return [
            'key' => 'prospect',
            'label' => 'Prospect / Lead',
            'badge' => 'secondary',
            'icon' => 'bi-person-badge',
            'description' => 'Registered, no confirmed orders',
        ];
    }

    public function getLastActiveAt(): ?\Carbon\Carbon
    {
        return $this->last_login_at
            ?? $this->orders()->latest('created_at')->value('created_at')
            ?? $this->updated_at
            ?? $this->created_at;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Accounts registered with a mobile number only have no email; the
     * password reset token store keys on this value, so fall back to the
     * phone so phone-only users can use the OTP-verified reset flow.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email ?: $this->phone;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
