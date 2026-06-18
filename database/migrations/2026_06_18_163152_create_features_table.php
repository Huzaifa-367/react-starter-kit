<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('subscriptionify.tables.features', 'features');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->enum('type', ['boolean', 'consumable', 'limit']);
                $table->string('description', 500)->nullable();
                $table->string('default_value', 50)->nullable();
                $table->enum('resettable_period', ['none', 'day', 'week', 'month', 'year'])->default('none');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('subscriptionify.tables.features', 'features'));
    }
};
