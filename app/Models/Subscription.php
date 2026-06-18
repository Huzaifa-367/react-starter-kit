<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'plan_id',
        'name',
        'status',
        'stripe_id',
        'stripe_status',
        'stripe_price_id',
        'trial_ends_at',
        'billing_starts_at',
        'ends_at',
        'cancels_at',
        'grace_ends_at',
        'paused_at',
        'auto_renew',
        'canceled_at',
        'previous_plan_id',
        'coupon_id',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'billing_starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancels_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'paused_at' => 'datetime',
        'auto_renew' => 'boolean',
        'canceled_at' => 'datetime',
        'previous_plan_id' => 'integer',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isInGrace(): bool
    {
        return $this->status === 'grace' && $this->grace_ends_at && $this->grace_ends_at->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isValid(): bool
    {
        return $this->isActive() ||
               $this->isTrialing() ||
               $this->isInGrace() ||
               ($this->isCanceled() && $this->ends_at && $this->ends_at->isFuture());
    }

    public function daysRemaining(): ?int
    {
        if ($this->isLifetime()) {
            return null;
        }

        $targetDate = $this->ends_at ?: $this->trial_ends_at;

        if (!$targetDate) {
            return 0;
        }

        if ($targetDate->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($targetDate);
    }

    public function isLifetime(): bool
    {
        return $this->plan && $this->plan->billing_period === 'lifetime';
    }
}
