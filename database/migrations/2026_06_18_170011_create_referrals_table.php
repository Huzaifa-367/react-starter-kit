<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->enum('status', ['pending', 'converted', 'rewarded'])->default('pending')->index();
            $table->enum('reward_type', ['discount', 'credit', 'none'])->default('none');
            $table->decimal('reward_value', 10, 2)->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('referred_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
