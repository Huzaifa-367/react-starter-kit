<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();

        $plan = Plan::create([
            'name' => 'Free Starter',
            'slug' => 'free-starter',
            'price' => 0.00,
            'currency' => 'USD',
            'billing_period' => 'month',
            'trial_days' => 0,
            'grace_days' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'plan_id' => $plan->id,
            'name' => 'main',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
    }
}
