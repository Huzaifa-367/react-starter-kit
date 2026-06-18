<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $table = 'feature_flags';

    protected $fillable = [
        'key',
        'description',
        'enabled_globally',
        'enabled_for_plans',
        'enabled_for_roles',
        'enabled_for_users',
    ];

    protected $casts = [
        'enabled_globally' => 'boolean',
        'enabled_for_plans' => 'json',
        'enabled_for_roles' => 'json',
        'enabled_for_users' => 'json',
    ];

    /**
     * Check if this feature flag is enabled for the given user.
     */
    public function isEnabled(?User $user = null): bool
    {
        return (new \App\Services\FeatureFlagService())->isEnabled($this->key, $user);
    }
}

