<?php

namespace App\Services;

use App\Models\User;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    /**
     * Determine if a feature flag is enabled for a specific user (or globally).
     */
    public function isEnabled(string $key, ?User $user = null): bool
    {
        $flagData = Cache::remember("feature_flag:{$key}", 300, function () use ($key) {
            $flag = FeatureFlag::where('key', $key)->first();
            if (!$flag) {
                return null;
            }

            return [
                'enabled_globally' => (bool) $flag->enabled_globally,
                'enabled_for_users' => (array) ($flag->enabled_for_users ?? []),
                'enabled_for_roles' => (array) ($flag->enabled_for_roles ?? []),
                'enabled_for_plans' => (array) ($flag->enabled_for_plans ?? []),
            ];
        });

        if (!$flagData) {
            return false;
        }

        // 1. Check global flag status
        if ($flagData['enabled_globally']) {
            return true;
        }

        // If not globally enabled and no user is provided, default to false
        if (!$user) {
            return false;
        }

        // 2. Check user IDs
        if (in_array($user->id, $flagData['enabled_for_users'])) {
            return true;
        }

        // 3. Check roles
        if (!empty($flagData['enabled_for_roles'])) {
            if ($user->hasAnyRole($flagData['enabled_for_roles'])) {
                return true;
            }
        }

        // 4. Check plans (active subscription plans)
        if (!empty($flagData['enabled_for_plans'])) {
            $activeSub = $user->getActiveSubscription();
            if ($activeSub && $activeSub->isValid()) {
                $planId = $activeSub->plan_id;
                $planSlug = $activeSub->plan?->slug;

                if (in_array($planId, $flagData['enabled_for_plans']) || 
                    ($planSlug && in_array($planSlug, $flagData['enabled_for_plans']))) {
                    return true;
                }
            }
        }

        return false;
    }
}
