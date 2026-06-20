<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
