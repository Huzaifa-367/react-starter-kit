<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Features
        $features = [
            [
                'name' => 'Projects Limit',
                'slug' => 'projects',
                'type' => 'consumable',
                'description' => 'Maximum number of projects that can be created',
                'default_value' => '0',
                'resettable_period' => 'none',
            ],
            [
                'name' => 'API Calls',
                'slug' => 'api_calls',
                'type' => 'consumable',
                'description' => 'Monthly API requests allowed',
                'default_value' => '0',
                'resettable_period' => 'month',
            ],
            [
                'name' => 'Premium Support',
                'slug' => 'premium_support',
                'type' => 'boolean',
                'description' => 'Access to priority customer support',
                'default_value' => 'false',
                'resettable_period' => 'none',
            ],
            [
                'name' => 'Team Members',
                'slug' => 'team_members',
                'type' => 'limit',
                'description' => 'Number of team members you can invite',
                'default_value' => '1',
                'resettable_period' => 'none',
            ],
        ];

        $seededFeatures = [];
        foreach ($features as $featureData) {
            $seededFeatures[$featureData['slug']] = Feature::firstOrCreate(
                ['slug' => $featureData['slug']],
                $featureData
            );
        }

        // 2. Seed Plans
        $plans = [
            [
                'name' => 'Free Starter',
                'slug' => 'free-starter',
                'description' => 'Essential features to get you started',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'trial_days' => 0,
                'grace_days' => 0,
                'sort_order' => 1,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '1',
                    'api_calls' => '100',
                    'premium_support' => 'false',
                    'team_members' => '1',
                ]
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro-monthly',
                'description' => 'Advanced capabilities for professionals',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'trial_days' => 7,
                'grace_days' => 5,
                'sort_order' => 2,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '10',
                    'api_calls' => '10000',
                    'premium_support' => 'true',
                    'team_members' => '5',
                ]
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro-yearly',
                'description' => 'Save with our professional annual plan',
                'price' => 179.99,
                'currency' => 'USD',
                'billing_period' => 'yearly',
                'trial_days' => 14,
                'grace_days' => 7,
                'sort_order' => 3,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '10',
                    'api_calls' => '10000',
                    'premium_support' => 'true',
                    'team_members' => '5',
                ]
            ],
            [
                'name' => 'Enterprise Monthly',
                'slug' => 'enterprise-monthly',
                'description' => 'Power and scale for large organizations',
                'price' => 79.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'trial_days' => 14,
                'grace_days' => 7,
                'sort_order' => 4,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '100',
                    'api_calls' => 'unlimited',
                    'premium_support' => 'true',
                    'team_members' => 'unlimited',
                ]
            ],
            [
                'name' => 'Enterprise Yearly',
                'slug' => 'enterprise-yearly',
                'description' => 'Enterprise grade at our best rate',
                'price' => 719.99,
                'currency' => 'USD',
                'billing_period' => 'yearly',
                'trial_days' => 14,
                'grace_days' => 10,
                'sort_order' => 5,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '100',
                    'api_calls' => 'unlimited',
                    'premium_support' => 'true',
                    'team_members' => 'unlimited',
                ]
            ],
            [
                'name' => 'Lifetime',
                'slug' => 'lifetime',
                'description' => 'Pay once, own forever',
                'price' => 399.00,
                'currency' => 'USD',
                'billing_period' => 'lifetime',
                'trial_days' => 0,
                'grace_days' => 0,
                'sort_order' => 6,
                'is_active' => true,
                'feature_values' => [
                    'projects' => '100',
                    'api_calls' => 'unlimited',
                    'premium_support' => 'true',
                    'team_members' => 'unlimited',
                ]
            ],
        ];

        foreach ($plans as $planData) {
            $featureValues = $planData['feature_values'];
            unset($planData['feature_values']);

            $plan = Plan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Sync features
            $syncData = [];
            foreach ($featureValues as $slug => $value) {
                if (isset($seededFeatures[$slug])) {
                    $syncData[$seededFeatures[$slug]->id] = ['value' => $value];
                }
            }
            $plan->features()->sync($syncData);
        }
    }
}
