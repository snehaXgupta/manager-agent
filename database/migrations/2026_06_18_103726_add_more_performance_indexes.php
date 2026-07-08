<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->index('started_at');
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index('date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['updated_at']);
        });
    }
};
