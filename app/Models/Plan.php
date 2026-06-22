<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property float $price
 * @property string $currency
 * @property string $billing_period
 * @property int|null $trial_days
 * @property int|null $grace_days
 * @property int|null $sort_order
 * @property bool $is_active
 * @property string|null $stripe_product_id
 * @property string|null $stripe_price_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Feature[] $features
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'trial_days',
        'grace_days',
        'sort_order',
        'is_active',
        'stripe_price_id',
        'stripe_product_id',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'grace_days' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'feature_plan')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price === 0.00;
    }

    public function isLifetime(): bool
    {
        return $this->billing_period === 'lifetime';
    }

    public function getFeatureValue(string $slug): mixed
    {
        $feature = $this->features->firstWhere('slug', $slug);

        return $feature ? $feature->pivot->value : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

}
