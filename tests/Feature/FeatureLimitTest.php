<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FeatureLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Plan $plan;
    protected Feature $feature;
    protected Subscription $sub;
    protected SubscriptionUsage $usage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Feature Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $this->feature = Feature::create([
            'name' => 'Projects Limit',
            'slug' => 'projects',
            'type' => 'consumable',
            'resettable_period' => 'none',
        ]);

        $this->plan = Plan::create([
            'name' => 'Starter Pack',
            'slug' => 'starter',
            'price' => 5.00,
            'currency' => 'USD',
            'billing_period' => 'month',
            'trial_days' => 0,
            'grace_days' => 0,
            'is_active' => true,
        ]);
        $this->plan->features()->attach($this->feature->id, ['value' => '2']);

        $this->sub = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'name' => 'main',
            'status' => 'active',
        ]);

        $this->usage = SubscriptionUsage::create([
            'subscription_id' => $this->sub->id,
            'feature_slug' => 'projects',
            'used' => 0,
        ]);

        // Clear user cache
        $this->user->flushSubscriptionCache();

        // Define a test route using the check feature middleware
        Route::middleware(['web', 'auth', 'feature:projects'])->get('/test-feature-gate', function () {
            return 'passed';
        });
    }

    /** @test */
    public function can_use_feature_when_under_limit()
    {
        $this->assertTrue($this->user->canUseFeature('projects'));
        $this->assertEquals(2, $this->user->getFeatureRemaining('projects'));
    }

    /** @test */
    public function consume_feature_increments_used_count_and_clears_cache()
    {
        $this->user->consumeFeature('projects', 1);

        $this->assertDatabaseHas('subscription_usages', [
            'id' => $this->usage->id,
            'used' => 1,
        ]);

        // Assert cached values reflect the update
        $this->assertEquals(1, $this->user->getFeatureUsage('projects'));
        $this->assertEquals(1, $this->user->getFeatureRemaining('projects'));
    }

    /** @test */
    public function cannot_use_feature_when_limit_reached()
    {
        $this->user->consumeFeature('projects', 2);

        $this->assertFalse($this->user->canUseFeature('projects'));
        $this->assertEquals(0, $this->user->getFeatureRemaining('projects'));
    }

    /** @test */
    public function feature_gate_middleware_allows_access_under_limit()
    {
        $this->actingAs($this->user);

        $response = $this->get('/test-feature-gate');

        $response->assertStatus(200);
        $response->assertSee('passed');
    }

    /** @test */
    public function feature_gate_middleware_blocks_access_when_limit_exceeded()
    {
        $this->actingAs($this->user);

        // Exhaust limit
        $this->user->consumeFeature('projects', 2);

        $response = $this->get('/test-feature-gate');

        $response->assertStatus(403);
    }
}
