<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\MagicLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function suspended_user_cannot_login()
    {
        $user = User::create([
            'name' => 'Suspended User',
            'email' => 'suspended@example.com',
            'password' => Hash::make('password'),
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => 'Violation of terms',
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $this->assertFalse(auth()->check());
        
        // Assert login attempt is logged to login_history as failed
        $this->assertDatabaseHas('login_history', [
            'user_id' => $user->id,
            'status' => 'failed',
            'failure_reason' => 'suspended',
        ]);
    }

    /** @test */
    public function successful_login_updates_audit_metrics_and_writes_history()
    {
        $user = User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue(auth()->check());
        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('127.0.0.1', $user->last_login_ip);

        // Check history
        $this->assertDatabaseHas('login_history', [
            'user_id' => $user->id,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
        ]);
    }

    /** @test */
    public function admin_without_subscription_redirected_to_admin_dashboard()
    {
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
    }

    /** @test */
    public function admin_can_access_admin_dashboard_without_subscription()
    {
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    /** @test */
    public function user_without_valid_subscription_redirected_to_pricing()
    {
        $user = User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/pricing');
    }

    /** @test */
    public function user_with_valid_subscription_redirected_to_dashboard()
    {
        $user = User::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Free Starter',
            'slug' => 'free-starter',
            'price' => 0.00,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'trial_days' => 0,
            'grace_days' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'name' => 'main',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'active@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function magic_link_can_login_user()
    {
        $user = User::create([
            'name' => 'Magic User',
            'email' => 'magic@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $token = 'magic-token-abc123';
        MagicLink::create([
            'email' => 'magic@example.com',
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Build signed URL
        $url = URL::temporarySignedRoute(
            'magic-link.login',
            now()->addMinutes(15),
            ['token' => $token, 'email' => 'magic@example.com']
        );

        $response = $this->get($url);

        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
        $response->assertRedirect('/pricing'); // no subscription yet

        // Token should be marked as used
        $this->assertNotNull(MagicLink::where('email', 'magic@example.com')->first()->used_at);
    }

    /** @test */
    public function magic_link_cannot_be_reused()
    {
        $user = User::create([
            'name' => 'Magic User',
            'email' => 'magic@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $token = 'magic-token-abc123';
        MagicLink::create([
            'email' => 'magic@example.com',
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now()->subMinutes(1), // already used
        ]);

        $url = URL::temporarySignedRoute(
            'magic-link.login',
            now()->addMinutes(15),
            ['token' => $token, 'email' => 'magic@example.com']
        );

        $response = $this->get($url);

        $response->assertStatus(403);
        $this->assertFalse(auth()->check());
    }
}
