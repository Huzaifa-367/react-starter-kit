<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 30)->nullable()->index();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('otp_code', 255)->nullable();
            $table->timestamp('otp_expires_at')->nullable()->index();
            $table->string('otp_purpose', 30)->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspended_reason', 500)->nullable();
            $table->string('stripe_id', 255)->nullable()->index();
            $table->string('pm_type', 50)->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('last_login_at')->nullable()->index();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->string('referral_code', 20)->nullable()->unique();
            $table->string('locale', 10)->default('en');
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version_accepted', 20)->nullable();
            $table->timestamp('email_bounced_at')->nullable();
            $table->enum('email_bounce_type', ['soft', 'hard'])->nullable();
            $table->softDeletes()->index();

            $table->index(['is_suspended', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_suspended', 'id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone_number',
                'phone_verified_at',
                'otp_code',
                'otp_expires_at',
                'otp_purpose',
                'is_suspended',
                'suspended_at',
                'suspended_reason',
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'last_login_at',
                'last_login_ip',
                'avatar_path',
                'referral_code',
                'locale',
                'terms_accepted_at',
                'terms_version_accepted',
                'email_bounced_at',
                'email_bounce_type'
            ]);
        });
    }
};
