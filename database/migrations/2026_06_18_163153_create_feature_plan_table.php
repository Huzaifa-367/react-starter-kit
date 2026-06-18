<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('subscriptionify.tables.feature_plan', 'feature_plan');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
                $table->string('value', 100);
                $table->timestamps();
                $table->unique(['plan_id', 'feature_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptionify.tables.feature_plan', 'feature_plan'));
    }
};
