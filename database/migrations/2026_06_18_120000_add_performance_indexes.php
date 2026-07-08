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
        Schema::table('users', function (Blueprint $table) {
            $table->index('manager_id');
            $table->index('created_at');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('assigned_to');
            $table->index('team_id');
            $table->index('created_at');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('developer_activities', function (Blueprint $table) {
            $table->index('repository');
            $table->index('created_at');
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->index('generated_at');
            $table->index('created_at');
        });

        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['team_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('developer_activities', function (Blueprint $table) {
            $table->dropIndex(['repository']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('performance_reports', function (Blueprint $table) {
            $table->dropIndex(['generated_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
