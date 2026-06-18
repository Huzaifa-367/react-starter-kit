<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['stripe'])->default('stripe')->index();
            $table->string('event_id', 100)->unique();
            $table->string('event_type', 100)->index();
            $table->json('payload');
            $table->boolean('processed')->default(false)->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
