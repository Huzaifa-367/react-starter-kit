<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested()
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $code = \App\Services\OtpService::getPlainOtp($user->id);

        $response = $this->get(route('password.reset.form', [
            'email' => $user->email,
            'code' => $code,
        ]));

        $response->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $code = \App\Services\OtpService::getPlainOtp($user->id);

        $response = $this->post(route('password.update'), [
            'code' => $code,
            'email' => $user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.update'), [
            'code' => '000000',
            'email' => $user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('code');
    }
}
