<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('subscriptionify.tables.subscriptions', 'subscriptions');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->morphs('subscribable');
                $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
                $table->string('name', 50)->default('main');
                $table->enum('status', ['active', 'trialing', 'grace', 'canceled', 'expired', 'paused'])->default('active');
                $table->string('stripe_id', 255)->nullable()->unique();
                $table->string('stripe_status', 50)->nullable();
                $table->string('stripe_price_id', 100)->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('billing_starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('cancels_at')->nullable();
                $table->timestamp('grace_ends_at')->nullable();
                $table->timestamp('paused_at')->nullable();
                $table->boolean('auto_renew')->default(true);
                $table->timestamp('canceled_at')->nullable();
                $table->bigInteger('previous_plan_id')->nullable();
                $table->string('coupon_id', 100)->nullable();
                $table->timestamp('payment_failed_at')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->timestamp('next_retry_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'name']);
                $table->index(['status', 'ends_at']);
                $table->index(['status', 'trial_ends_at']);
                $table->index(['status', 'grace_ends_at']);
                $table->index(['auto_renew', 'ends_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptionify.tables.subscriptions', 'subscriptions'));
    }
};
