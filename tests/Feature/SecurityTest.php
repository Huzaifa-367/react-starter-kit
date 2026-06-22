<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\IpRule;
use App\Models\WebhookLog;
use App\Models\PasswordHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->user = User::create([
            'name' => 'Secure User',
            'email' => 'security@example.com',
            'password' => Hash::make('P@ssword123!'),
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('User (Free)');
    }

    /** @test */
    public function test_email_bounce_webhook_marks_user_email_as_bounced()
    {
        $payload = [
            'mail_provider' => 'mailtrap',
            'event' => 'bounce',
            'email' => 'security@example.com',
            'type' => 'hard',
        ];

        $response = $this->postJson('/webhooks/email/bounce', $payload);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertNotNull($this->user->email_bounced_at);
        $this->assertEquals('hard', $this->user->email_bounce_type);
        $this->assertTrue($this->user->hasBouncedEmail());
    }

    /** @test */
    public function test_ip_firewall_blocks_blocked_ip_rules()
    {
        // Add a block rule
        IpRule::create([
            'ip' => '198.51.100.4',
            'type' => 'block',
            'reason' => 'Threat Host',
            'is_active' => true,
        ]);

        Cache::forget('ip_rules');

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])
            ->get('/pricing');

        $response->assertStatus(403);
    }

    /** @test */
    public function test_ip_firewall_allows_active_ip_rules()
    {
        IpRule::create([
            'ip' => '198.51.100.4',
            'type' => 'allow',
            'reason' => 'Bypass Rule',
            'is_active' => true,
        ]);

        Cache::forget('ip_rules');

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])
            ->get('/pricing');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_password_history_prevents_password_reuse()
    {
        $this->actingAs($this->user);

        // Seed password history with user's current password
        PasswordHistory::create([
            'user_id' => $this->user->id,
            'password' => $this->user->password,
        ]);

        // Attempting to change password to the same password
        $response = $this->put('/settings/password', [
            'current_password' => 'P@ssword123!',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function test_webhook_idempotency_prevents_reprocessing_duplicate_event_ids()
    {
        // Log event_id as processed
        WebhookLog::create([
            'source' => 'stripe',
            'event_id' => 'evt_idempotency_123',
            'event_type' => 'invoice.payment_succeeded',
            'payload' => ['id' => 'evt_idempotency_123'],
            'processed' => true,
        ]);

        $payload = [
            'id' => 'evt_idempotency_123',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_mock_123',
                    'subscription' => 'sub_mock_123'
                ]
            ]
        ];

        // Should return 200 without raising errors or executing database updates
        $response = $this->postJson('/stripe/webhook', $payload);
        $response->assertStatus(200);
    }
}
