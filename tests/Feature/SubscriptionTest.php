<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\WebhookLog;
use App\Services\SubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Plan $freePlan;
    protected Plan $paidPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->user = User::create([
            'name' => 'Subscriber User',
            'email' => 'subscriber@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $this->user->assignRole('User (Free)');

        // Seed features
        $feature1 = Feature::create([
            'name' => 'Projects Limit',
            'slug' => 'projects',
            'type' => 'consumable',
            'resettable_period' => 'none',
        ]);

        // Seed plans
        $this->freePlan = Plan::create([
            'name' => 'Free Starter',
            'slug' => 'free-starter',
            'price' => 0.00,
            'currency' => 'USD',
            'billing_period' => 'month',
            'trial_days' => 0,
            'grace_days' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->freePlan->features()->attach($feature1->id, ['value' => '1']);

        $this->paidPlan = Plan::create([
            'name' => 'Pro Monthly',
            'slug' => 'pro-monthly',
            'price' => 19.99,
            'currency' => 'USD',
            'billing_period' => 'month',
            'trial_days' => 0,
            'grace_days' => 5,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $this->paidPlan->features()->attach($feature1->id, ['value' => '10']);
    }

    /** @test */
    public function test_subscribing_to_free_plan_handles_local_activation_and_usages()
    {
        $this->actingAs($this->user);

        $response = $this->post('/billing/checkout', [
            'plan_id' => $this->freePlan->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect('/dashboard');

        // Verify local DB record
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->freePlan->id,
            'status' => 'active',
        ]);

        $subscription = Subscription::where('user_id', $this->user->id)->first();
        
        // Verify usages seeded
        $this->assertDatabaseHas('subscription_usages', [
            'subscription_id' => $subscription->id,
            'feature_slug' => 'projects',
            'used' => 0,
        ]);

        // Role updated
        $this->user->refresh();
        $this->assertTrue($this->user->hasRole('User (Free)'));
    }

    /** @test */
    public function test_cancel_subscription_marks_canceled_but_retains_access_until_ends_at()
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
            'name' => 'main',
            'status' => 'active',
            'ends_at' => now()->addDays(20),
            'auto_renew' => true,
        ]);

        $this->user->assignRole('User (Subscribed)');
        $this->user->removeRole('User (Free)');
        $this->user->refresh();

        $this->actingAs($this->user);

        $response = $this->post('/billing/cancel');

        $response->assertRedirect();
        
        $sub->refresh();
        $this->assertEquals('canceled', $sub->status);
        $this->assertFalse($sub->auto_renew);
        $this->assertNotNull($sub->canceled_at);
        $this->assertTrue($sub->isValid()); // Access still valid until period ends
    }

    /** @test */
    public function test_resume_subscription_restores_active_status_and_renewal()
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
            'name' => 'main',
            'status' => 'canceled',
            'ends_at' => now()->addDays(20),
            'auto_renew' => false,
            'canceled_at' => now(),
        ]);

        $this->user->assignRole('User (Subscribed)');
        $this->user->removeRole('User (Free)');
        $this->user->refresh();

        $this->actingAs($this->user);

        $response = $this->post('/billing/resume');

        $response->assertRedirect();

        $sub->refresh();
        $this->assertEquals('active', $sub->status);
        $this->assertTrue($sub->auto_renew);
        $this->assertNull($sub->canceled_at);
    }

    /** @test */
    public function test_stripe_webhook_checkout_session_completed_handles_activation()
    {
        // Assert idempotency
        $eventId = 'evt_test_checkout_completed';
        
        $payload = [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_session',
                    'subscription' => 'sub_stripe_123',
                    'metadata' => [
                        'user_id' => $this->user->id,
                        'plan_id' => $this->paidPlan->id,
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/stripe/webhook', $payload, [
            'Stripe-Signature' => 'mocked-sig' // CSRF is disabled on webhooks
        ]);

        $response->assertStatus(200);

        // Webhook log logged
        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => $eventId,
            'processed' => true,
        ]);

        // Subscription activated
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
            'stripe_id' => 'sub_stripe_123',
            'status' => 'active',
        ]);

        $this->user->refresh();
        $this->assertTrue($this->user->hasRole('User (Subscribed)'));
    }

    /** @test */
    public function test_stripe_webhook_invoice_payment_failed_enters_grace_period()
    {
        $sub = Subscription::create([
            'user_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
            'name' => 'main',
            'status' => 'active',
            'stripe_id' => 'sub_stripe_123',
            'auto_renew' => true,
        ]);

        $this->user->assignRole('User (Subscribed)');
        $this->user->removeRole('User (Free)');
        $this->user->refresh();

        $eventId = 'evt_test_payment_failed';
        $payload = [
            'id' => $eventId,
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_invoice',
                    'subscription' => 'sub_stripe_123',
                    'customer' => 'cus_stripe_123',
                ]
            ]
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertStatus(200);

        $sub->refresh();
        $this->assertEquals('grace', $sub->status);
        $this->assertNotNull($sub->grace_ends_at);
        $this->assertNotNull($sub->payment_failed_at);
    }
}
