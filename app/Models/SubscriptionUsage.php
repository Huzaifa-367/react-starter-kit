<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $subscribable_type
 * @property int $subscribable_id
 * @property int $feature_id
 * @property string $feature_slug
 * @property int $used
 * @property int $overage
 * @property \Carbon\Carbon|null $valid_until
 * @property \Carbon\Carbon|null $last_reset_at
 * @property \Carbon\Carbon|null $reset_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SubscriptionUsage extends Model
{
    use HasFactory;

    protected $table = 'subscription_usages';

    protected $fillable = [
        'subscription_id',
        'subscribable_type',
        'subscribable_id',
        'feature_id',
        'feature_slug',
        'used',
        'overage',
        'valid_until',
        'last_reset_at',
        'reset_at',
    ];

    protected $casts = [
        'used' => 'integer',
        'overage' => 'integer',
        'valid_until' => 'datetime',
        'last_reset_at' => 'datetime',
        'reset_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getRemaining(int|string $limit): int|string
    {
        if ($limit === 'unlimited') {
            return 'unlimited';
        }

        return max(0, (int) $limit - $this->used);
    }

    public function isExhausted(int|string $limit): bool
    {
        if ($limit === 'unlimited') {
            return false;
        }

        return $this->used >= (int) $limit;
    }
}
