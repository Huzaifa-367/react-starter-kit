<?php

namespace App\Traits;

use App\Models\Feature;
use App\Models\OnboardingProgress;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Support\Facades\Cache;

trait HasSubscriptions
{
    // ─── Read Methods (Cache-First) ───────────────────────────────────────────

    public function getActiveSubscription(): ?Subscription
    {
        $data = Cache::remember("user:{$this->id}:subscription", 3600, function () {
            $sub = $this->subscriptions()
                ->with(['plan.features'])
                ->latest()
                ->first();
            if (!$sub) {
                return null;
            }

            return [
                'attributes' => $sub->getAttributes(),
                'plan' => $sub->plan ? [
                    'attributes' => $sub->plan->getAttributes(),
                    'features' => $sub->plan->features->map(fn($f) => [
                        'attributes' => $f->getAttributes(),
                        'pivot' => $f->pivot->getAttributes(),
                    ])->toArray(),
                ] : null,
            ];
        });

        if (!$data) {
            return null;
        }

        $sub = new Subscription();
        $sub->forceFill($data['attributes']);
        $sub->exists = true;

        if (isset($data['plan']) && $data['plan']) {
            $plan = new Plan();
            $plan->forceFill($data['plan']['attributes']);
            $plan->exists = true;

            $features = collect($data['plan']['features'])->map(function ($fData) {
                $feature = new Feature();
                $feature->forceFill($fData['attributes']);
                $feature->exists = true;

                $pivot = new \Illuminate\Database\Eloquent\Relations\Pivot();
                $pivot->forceFill($fData['pivot']);
                $pivot->exists = true;
                $feature->setRelation('pivot', $pivot);

                return $feature;
            });

            $plan->setRelation('features', $features);
            $sub->setRelation('plan', $plan);
        }

        return $sub;
    }

    public function hasValidSubscription(): bool
    {
        $sub = $this->getActiveSubscription();
        return $sub && $sub->isValid();
    }

    public function getFeatureLimit(string $slug): int|string
    {
        $limits = Cache::remember("user:{$this->id}:feature_limits", 3600, function () {
            $sub = $this->getActiveSubscription();
            if (!$sub || !$sub->plan) return [];
            return $sub->plan->features->pluck('pivot.value', 'slug')->toArray();
        });

        return $limits[$slug] ?? 0;
    }

    public function getFeatureUsage(string $slug): int
    {
        $usages = Cache::remember("user:{$this->id}:feature_usages", 900, function () {
            $sub = $this->getActiveSubscription();
            if (!$sub) return [];
            return $sub->usages->pluck('used', 'feature_slug')->toArray();
        });

        return isset($usages[$slug]) ? (int) $usages[$slug] : 0;
    }

    public function getFeatureRemaining(string $slug): int|string
    {
        $limit = $this->getFeatureLimit($slug);
        if ($limit === 'unlimited') {
            return 'unlimited';
        }

        return max(0, (int) $limit - $this->getFeatureUsage($slug));
    }

    public function canUseFeature(string $slug): bool
    {
        $limit = $this->getFeatureLimit($slug);
        if ($limit === 'unlimited') {
            return true;
        }

        if ($limit === 'false' || $limit === false || $limit === '0' || $limit === 0) {
            return false;
        }

        if ($limit === 'true' || $limit === true) {
            return true;
        }

        return $this->getFeatureUsage($slug) < (int) $limit;
    }

    // ─── Write Methods (DB + Cache Flush) ─────────────────────────────────────

    public function consumeFeature(string $slug, int $amount = 1): void
    {
        $sub = $this->getActiveSubscription();
        if (!$sub) return;

        SubscriptionUsage::where('subscription_id', $sub->id)
            ->where('feature_slug', $slug)
            ->increment('used', $amount);

        Cache::forget("user:{$this->id}:feature_usages");

        // Update onboarding step_first_project if applicable
        if (class_exists(OnboardingProgress::class)) {
            OnboardingProgress::where('user_id', $this->id)
                ->where('step_first_project', false)
                ->update(['step_first_project' => true]);
        }
    }

    // ─── Cache Management ─────────────────────────────────────────────────────

    public function flushSubscriptionCache(): void
    {
        Cache::forget("user:{$this->id}:subscription");
        Cache::forget("user:{$this->id}:feature_limits");
        Cache::forget("user:{$this->id}:feature_usages");
    }
}
