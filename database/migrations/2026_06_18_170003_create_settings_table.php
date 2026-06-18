<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->index();
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'secret'])->default('string');
            $table->string('label', 200)->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['group', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
