<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('enabled_globally')->default(false);
            $table->json('enabled_for_plans')->nullable();
            $table->json('enabled_for_roles')->nullable();
            $table->json('enabled_for_users')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
