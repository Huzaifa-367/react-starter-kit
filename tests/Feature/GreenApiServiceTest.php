<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\NotificationLog;
use App\Services\GreenApiService;
use GreenApi\RestApi\GreenApiClient;
use GreenApi\RestApi\tools\Sending;
use GreenApi\RestApi\tools\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Mockery;
use stdClass;

class GreenApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingSeeder::class);
    }

    public function test_send_message_success_logs_to_database()
    {
        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');
        Setting::set('green_api_url', 'https://api.green-api.com');

        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockSending = Mockery::mock(Sending::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) ['idMessage' => 'msg123'];
        $mockResponse->error = null;

        $mockSending->shouldReceive('sendMessage')
            ->once()
            ->with('1234567890@c.us', 'Hello World')
            ->andReturn($mockResponse);

        $mockClient->sending = $mockSending;

        $this->app->instance(GreenApiClient::class, $mockClient);

        $service = new GreenApiService();
        $result = $service->sendMessage('1234567890', 'Hello World');

        $this->assertTrue($result);
        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'whatsapp',
            'type' => 'custom',
            'recipient' => '1234567890',
            'status' => 'sent',
        ]);
    }

    public function test_send_message_failure_logs_error()
    {
        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');

        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockSending = Mockery::mock(Sending::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 400;
        $mockResponse->data = null;
        $mockResponse->error = (object) ['message' => 'Invalid instance'];

        $mockSending->shouldReceive('sendMessage')
            ->once()
            ->with('1234567890@c.us', 'Hello World')
            ->andReturn($mockResponse);

        $mockClient->sending = $mockSending;

        $this->app->instance(GreenApiClient::class, $mockClient);

        $service = new GreenApiService();
        $result = $service->sendMessage('1234567890', 'Hello World');

        $this->assertFalse($result);
        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'whatsapp',
            'type' => 'custom',
            'recipient' => '1234567890',
            'status' => 'failed',
        ]);
    }

    public function test_sync_session_settings_success()
    {
        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');

        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockAccount = Mockery::mock(Account::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) [
            'phone' => '79001234567',
            'avatar' => 'https://pps.whatsapp.net/v/avatar.jpg',
        ];
        $mockResponse->error = null;

        $mockAccount->shouldReceive('getWaSettings')
            ->once()
            ->andReturn($mockResponse);

        $mockClient->account = $mockAccount;

        $this->app->instance(GreenApiClient::class, $mockClient);

        $service = new GreenApiService();
        $result = $service->syncSessionSettings();

        $this->assertTrue($result);
        $this->assertEquals('79001234567', Setting::get('green_api_phone'));
        $this->assertEquals('https://pps.whatsapp.net/v/avatar.jpg', Setting::get('green_api_avatar'));
    }

    public function test_settings_update_triggers_auto_sync()
    {
        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockAccount = Mockery::mock(Account::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) [
            'phone' => '79991112233',
            'avatar' => 'https://pps.whatsapp.net/v/new_avatar.jpg',
        ];
        $mockResponse->error = null;

        $mockAccount->shouldReceive('getWaSettings')
            ->once()
            ->andReturn($mockResponse);

        $mockClient->account = $mockAccount;

        $this->app->instance(GreenApiClient::class, $mockClient);

        // Authenticate admin user
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)
            ->post('/admin/settings', [
                'settings' => [
                    'green_api_id_instance' => 'new_id',
                    'green_api_token_instance' => 'new_token',
                ]
            ]);

        $response->assertRedirect();
        $this->assertEquals('new_id', Setting::get('green_api_id_instance'));
        $this->assertEquals('new_token', Setting::get('green_api_token_instance'));
        $this->assertEquals('79991112233', Setting::get('green_api_phone'));
        $this->assertEquals('https://pps.whatsapp.net/v/new_avatar.jpg', Setting::get('green_api_avatar'));
    }

    public function test_settings_sync_whatsapp_route()
    {
        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockAccount = Mockery::mock(Account::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) [
            'phone' => '79991112233',
            'avatar' => 'https://pps.whatsapp.net/v/new_avatar.jpg',
        ];
        $mockResponse->error = null;

        $mockAccount->shouldReceive('getWaSettings')
            ->once()
            ->andReturn($mockResponse);

        $mockClient->account = $mockAccount;

        $this->app->instance(GreenApiClient::class, $mockClient);

        // Authenticate admin user
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');

        $response = $this->actingAs($admin)
            ->post('/admin/settings/sync-whatsapp');

        $response->assertRedirect();
        $this->assertEquals('79991112233', Setting::get('green_api_phone'));
        $this->assertEquals('https://pps.whatsapp.net/v/new_avatar.jpg', Setting::get('green_api_avatar'));
    }

    public function test_settings_send_test_email()
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function ($text, $callback) {
                return str_contains(strtolower($text), 'smtp credentials are valid');
            });

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)
            ->post('/admin/settings/test-email', [
                'email' => 'test-recipient@example.com',
            ]);

        $response->assertRedirect();
    }

    public function test_settings_send_test_whatsapp()
    {
        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockSending = Mockery::mock(Sending::class);

        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) ['idMessage' => 'msg123'];
        $mockResponse->error = null;

        $mockSending->shouldReceive('sendMessage')
            ->once()
            ->with('1234567890@c.us', 'This is a test WhatsApp message from SaaS App. Your Green API credentials are valid!')
            ->andReturn($mockResponse);

        $mockClient->sending = $mockSending;

        $this->app->instance(GreenApiClient::class, $mockClient);

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');

        $response = $this->actingAs($admin)
            ->post('/admin/settings/test-whatsapp', [
                'phone' => '1234567890',
            ]);

        $response->assertRedirect();
    }
}
