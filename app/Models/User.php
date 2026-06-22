<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $phone_number
 * @property Carbon|null $phone_verified_at
 * @property string|null $otp_code
 * @property Carbon|null $otp_expires_at
 * @property string|null $otp_purpose
 * @property bool $is_suspended
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_reason
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $avatar_path
 * @property string|null $referral_code
 * @property string $locale
 * @property Carbon|null $terms_accepted_at
 * @property string|null $terms_version_accepted
 * @property Carbon|null $email_bounced_at
 * @property string|null $email_bounce_type
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'email', 'password', 'phone_number', 'stripe_id', 'pm_type', 'pm_last_four',
    'is_suspended', 'suspended_at', 'suspended_reason', 'otp_code', 'otp_expires_at',
    'otp_purpose', 'last_login_at', 'last_login_ip', 'avatar_path', 'referral_code',
    'locale', 'terms_accepted_at', 'terms_version_accepted', 'email_bounced_at', 'email_bounce_type',
    'email_verified_at', 'phone_verified_at'
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'otp_code'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, HasRoles, SoftDeletes, HasSubscriptions;

    public ?string $plain_otp = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'email_bounced_at' => 'datetime',
            'is_suspended' => 'boolean',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function fcmTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function notificationLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function userNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function sentInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function socialAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function loginHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function referralsSent(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referralReceived(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function credits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserCredit::class);
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserNote::class);
    }

    public function onboarding(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OnboardingProgress::class);
    }

    public function passwordHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(['Super Admin', 'Admin']);
    }

    public function requiresSubscription(): bool
    {
        return !$this->isStaff() && !$this->hasValidSubscription();
    }

    public function defaultAuthenticatedPath(): string
    {
        return $this->isStaff() ? '/admin/dashboard' : '/dashboard';
    }

    public function hasBouncedEmail(): bool
    {
        return $this->email_bounced_at !== null;
    }
}
