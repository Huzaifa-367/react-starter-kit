<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OnboardingProgress;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\SettingSeeder::class);
        
        // Seed roles
        Role::firstOrCreate(['name' => 'User (Free)', 'guard_name' => 'web']);
    }

    /** @test */
    public function test_new_user_can_register_with_valid_data()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
            'phone_number' => '+14155552671',
            'terms' => true,
        ]);

        $response->assertRedirect('/verify/otp?purpose=email_verify');

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'phone_number' => '+14155552671',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('User (Free)'));
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_expires_at);
        $this->assertEquals('email_verify', $user->otp_purpose);

        // Check onboarding progress was initialized
        $this->assertDatabaseHas('onboarding_progress', [
            'user_id' => $user->id,
            'step_email_verified' => false,
        ]);

        // Check password history recorded
        $this->assertDatabaseHas('password_history', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function test_registration_requires_terms_acceptance()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
            'phone_number' => '+14155552671',
            'terms' => false,
        ]);

        $response->assertSessionHasErrors(['terms']);
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    /** @test */
    public function test_duplicate_unverified_email_resends_otp_without_creating_new_user()
    {
        // Create an unverified user
        $user = User::create([
            'name' => 'Old John',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'otp_code' => Hash::make('111111'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_purpose' => 'email_verify',
        ]);

        $response = $this->post('/register', [
            'name' => 'New John',
            'email' => 'john@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
            'terms' => true,
        ]);

        // Should redirect to verify OTP for old user
        $response->assertRedirect('/verify/otp?purpose=email_verify');

        // Total user count should still be 1
        $this->assertEquals(1, User::where('email', 'john@example.com')->count());

        // Stored name should remain "Old John"
        $user->refresh();
        $this->assertEquals('Old John', $user->name);
    }

    /** @test */
    public function test_correct_otp_verifies_email()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_purpose' => 'email_verify',
        ]);

        OnboardingProgress::create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post('/verify/otp', [
            'code' => '123456',
            'purpose' => 'email_verify',
        ]);

        $response->assertRedirect('/pricing');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->otp_code);

        // Check onboarding step updated
        $this->assertTrue($user->onboarding->step_email_verified);
    }

    /** @test */
    public function test_wrong_otp_fails_and_increments_attempts()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_purpose' => 'email_verify',
        ]);

        $this->actingAs($user);

        $response = $this->post('/verify/otp', [
            'code' => '654321', // wrong code
            'purpose' => 'email_verify',
        ]);

        $response->assertSessionHasErrors(['code']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);

        // Check lockout tracking
        $attemptsKey = "otp_fail:{$user->id}";
        $this->assertEquals(1, Cache::get($attemptsKey));
    }

    /** @test */
    public function test_five_failed_otp_attempts_lockout_user()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_purpose' => 'email_verify',
        ]);

        $this->actingAs($user);

        $attemptsKey = "otp_fail:{$user->id}";
        Cache::put($attemptsKey, 4, 900); // 4 failures already

        $response = $this->post('/verify/otp', [
            'code' => '654321', // 5th failure
            'purpose' => 'email_verify',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertTrue(Cache::has("otp_lockout:{$user->id}"));

        // Subsequent attempt should block with 429 throttle message
        $response2 = $this->post('/verify/otp', [
            'code' => '123456', // correct code but locked out
            'purpose' => 'email_verify',
        ]);

        $response2->assertRedirect();
        $response2->assertSessionHasErrors(['code']);
    }

    /** @test */
    public function test_expired_otp_cannot_be_verified()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->subMinutes(1), // expired
            'otp_purpose' => 'email_verify',
        ]);

        $this->actingAs($user);

        $response = $this->post('/verify/otp', [
            'code' => '123456',
            'purpose' => 'email_verify',
        ]);

        $response->assertSessionHasErrors(['code']);
        
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function test_registration_redirects_directly_to_pricing_when_no_channels_are_enabled()
    {
        \App\Models\Setting::set('otp_default_channels', []);

        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
            'phone_number' => '+14155552671',
            'terms' => true,
        ]);

        $response->assertRedirect('/pricing');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
    }

    /** @test */
    public function test_registration_redirects_to_phone_verify_when_only_phone_verification_is_enabled()
    {
        \App\Models\Setting::set('otp_default_channels', ['sms']);

        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
            'phone_number' => '+14155552671',
            'terms' => true,
        ]);

        $response->assertRedirect('/verify/otp?purpose=phone_verify');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertEquals('phone_verify', $user->otp_purpose);
    }

    /** @test */
    public function test_email_verification_success_redirects_to_phone_verification_if_phone_is_enabled()
    {
        \App\Models\Setting::set('otp_default_channels', ['email', 'sms']);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('P@ssword123!'),
            'phone_number' => '+14155552671',
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_purpose' => 'email_verify',
            'email_verified_at' => null,
        ]);

        OnboardingProgress::create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post('/verify/otp', [
            'code' => '123456',
            'purpose' => 'email_verify',
        ]);

        $response->assertRedirect('/verify/otp?purpose=phone_verify');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
        $this->assertEquals('phone_verify', $user->otp_purpose);
    }

    /** @test */
    public function test_login_redirects_unverified_user_based_on_channels()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'phone_number' => '+14155552671',
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        // Scenario 1: Email verification enabled
        \App\Models\Setting::set('otp_default_channels', ['email']);
        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);
        $response->assertRedirect('/verify/otp?purpose=email_verify');

        // Logout
        \Illuminate\Support\Facades\Auth::logout();
        session()->flush();

        // Scenario 2: Email verification disabled, Phone verification enabled
        \App\Models\Setting::set('otp_default_channels', ['sms']);
        $response2 = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);
        $response2->assertRedirect('/verify/otp?purpose=phone_verify');

        // Logout
        \Illuminate\Support\Facades\Auth::logout();
        session()->flush();

        // Scenario 3: Both disabled
        \App\Models\Setting::set('otp_default_channels', []);
        $response3 = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);
        $response3->assertRedirect('/pricing');
    }
}
