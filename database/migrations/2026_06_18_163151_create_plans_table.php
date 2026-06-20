<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('subscriptionify.tables.plans', 'plans');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->enum('billing_period', ['monthly', 'yearly', 'lifetime'])->default('monthly')->index();
                $table->unsignedSmallInteger('trial_days')->default(0);
                $table->unsignedSmallInteger('grace_days')->default(7);
                $table->smallInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->string('stripe_price_id', 100)->nullable();
                $table->string('stripe_product_id', 100)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptionify.tables.plans', 'plans'));
    }
};
