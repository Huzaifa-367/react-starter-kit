<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Plan $plan;
    protected Feature $feature;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'User (Free)', 'guard_name' => 'web']);

        $this->user = User::create([
            'name' => 'Performance User',
            'email' => 'performance@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('User (Free)');

        $this->feature = Feature::create([
            'name' => 'API Calls',
            'slug' => 'api_calls',
            'type' => 'consumable',
            'resettable_period' => 'month',
        ]);

        $this->plan = Plan::create([
            'name' => 'Starter Pack',
            'slug' => 'starter',
            'price' => 5.00,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'trial_days' => 0,
            'grace_days' => 0,
            'is_active' => true,
        ]);
        $this->plan->features()->attach($this->feature->id, ['value' => '100']);

        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'name' => 'main',
            'status' => 'active',
        ]);

        SubscriptionUsage::create([
            'subscription_id' => $sub->id,
            'feature_slug' => 'api_calls',
            'used' => 10,
        ]);
    }

    /** @test */
    public function subscription_checks_are_fully_cached_and_cause_zero_database_queries()
    {
        // First call populates cache
        $this->user->hasValidSubscription();

        // Count queries in second call
        DB::enableQueryLog();
        
        $this->user->hasValidSubscription();
        $this->user->getFeatureLimit('api_calls');
        $this->user->getFeatureUsage('api_calls');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals(0, $queryCount);
    }

    /** @test */
    public function settings_get_methods_are_fully_cached_and_cause_zero_database_queries()
    {
        Setting::create([
            'key' => 'app_currency',
            'value' => 'USD',
            'group' => 'app',
            'type' => 'string',
        ]);

        // First call caches settings keys
        Setting::get('app_currency');

        // Track second call queries
        DB::enableQueryLog();

        Setting::get('app_currency');
        Setting::get('app_currency');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals(0, $queryCount);
    }
}
