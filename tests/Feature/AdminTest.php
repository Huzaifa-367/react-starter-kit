<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'User (Free)', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('Super Admin');

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->customer->assignRole('User (Free)');
    }

    /** @test */
    public function administrator_can_suspend_user_and_kill_active_sessions()
    {
        $this->actingAs($this->admin);

        // Seed some session database entry for the customer
        DB::table('sessions')->insert([
            'id' => 'customer_session_123',
            'user_id' => $this->customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla',
            'payload' => 'abc',
            'last_activity' => time(),
        ]);

        $response = $this->post("/admin/users/{$this->customer->id}/suspend", [
            'reason' => 'Violation of Spam Policy',
        ]);

        $response->assertRedirect();
        
        $this->customer->refresh();
        $this->assertTrue($this->customer->is_suspended);
        $this->assertEquals('Violation of Spam Policy', $this->customer->suspended_reason);

        // Session table check: should be cleared for suspended user
        $this->assertDatabaseMissing('sessions', [
            'user_id' => $this->customer->id
        ]);

        // Audit log exists
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'user.suspended',
            'subject_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function administrator_can_unsuspend_user()
    {
        $this->customer->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => 'Spam',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/admin/users/{$this->customer->id}/unsuspend");

        $response->assertRedirect();

        $this->customer->refresh();
        $this->assertFalse($this->customer->is_suspended);
        $this->assertNull($this->customer->suspended_reason);

        // Audit log exists
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'user.unsuspended',
            'subject_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function administrator_can_impersonate_user_and_stop_impersonation()
    {
        $this->actingAs($this->admin);

        // Start impersonation
        $response = $this->post("/admin/users/{$this->customer->id}/impersonate");

        $response->assertRedirect();
        
        // Assert session parameters are set
        $this->assertEquals($this->admin->id, session('impersonating_admin_id'));
        $this->assertEquals($this->customer->id, auth()->id());

        // Stop impersonation
        $responseStop = $this->post("/admin/impersonation/stop");

        $responseStop->assertRedirect();

        $this->assertNull(session('impersonating_admin_id'));
        $this->assertEquals($this->admin->id, auth()->id());
    }

    /** @test */
    public function administrative_settings_saves_and_encrypts_secrets()
    {
        $this->actingAs($this->admin);

        $setting = Setting::create([
            'key' => 'mail_password',
            'value' => null,
            'group' => 'smtp',
            'type' => 'secret',
            'is_encrypted' => true,
        ]);

        $response = $this->post('/admin/settings', [
            'settings' => [
                'mail_password' => 'super-secret-smtp-password',
            ],
        ]);

        $response->assertRedirect();

        // Check value in Database is encrypted
        $rawValue = DB::table('settings')->where('key', 'mail_password')->first()->value;
        $this->assertNotEquals('super-secret-smtp-password', $rawValue);

        // Check retrieved value is decrypted
        Setting::flush(); // Flush cache
        $this->assertEquals('super-secret-smtp-password', Setting::get('mail_password'));
    }
}
