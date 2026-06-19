<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationDispatcher;
use GreenApi\RestApi\GreenApiClient;
use GreenApi\RestApi\tools\Sending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Mockery;
use stdClass;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingSeeder::class);
    }

    public function test_email_sent_when_email_is_configured()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        Mail::assertQueued(\App\Mail\InvitationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_email_not_sent_when_email_not_configured_for_smtp()
    {
        Mail::fake();

        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => '']);
        Setting::set('mail_host', '');

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        Mail::assertNotQueued(\App\Mail\InvitationMail::class);
    }

    public function test_whatsapp_sent_only_when_green_api_configured()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse(NotificationDispatcher::isWhatsappConfigured());

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        $this->assertDatabaseMissing('notification_logs', [
            'channel' => 'whatsapp',
        ]);

        Setting::set('green_api_id_instance', '123456');
        Setting::set('green_api_token_instance', 'token123');

        $this->assertTrue(NotificationDispatcher::isWhatsappConfigured());

        $mockClient = Mockery::mock(GreenApiClient::class);
        $mockSending = Mockery::mock(Sending::class);
        $mockResponse = new stdClass();
        $mockResponse->code = 200;
        $mockResponse->data = (object) ['idMessage' => 'msg123'];
        $mockResponse->error = null;

        $mockSending->shouldReceive('sendMessage')
            ->once()
            ->andReturn($mockResponse);
        $mockClient->sending = $mockSending;
        $this->app->instance(GreenApiClient::class, $mockClient);

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);
    }

    public function test_sms_sent_only_when_twilio_configured()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '1234567890',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse(NotificationDispatcher::isSmsConfigured());

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        $this->assertDatabaseMissing('notification_logs', [
            'channel' => 'sms',
        ]);

        Setting::create([
            'key' => 'twilio_account_sid',
            'value' => 'sid123',
            'group' => 'twilio',
            'type' => 'string',
            'label' => 'Twilio Account SID',
        ]);
        Setting::create([
            'key' => 'twilio_auth_token',
            'value' => 'token123',
            'group' => 'twilio',
            'type' => 'secret',
            'is_encrypted' => true,
        ]);
        Setting::create([
            'key' => 'twilio_from_number',
            'value' => '+1234567890',
            'group' => 'twilio',
            'type' => 'string',
            'label' => 'Twilio From Number',
        ]);
        Setting::flush();

        $this->assertTrue(NotificationDispatcher::isSmsConfigured());

        NotificationDispatcher::dispatch($user, 'invitation_sent', [
            'invite_link' => 'http://localhost/register?token=123',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'status' => 'failed',
        ]);
    }
}
