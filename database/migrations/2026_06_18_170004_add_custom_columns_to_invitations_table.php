<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('invite-only.table', 'invitations');

        Schema::table($tableName, function (Blueprint $table) {
            $table->text('message')->nullable();
        });
    }

    public function down(): void
    {
        $tableName = config('invite-only.table', 'invitations');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }
};
