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
        // 1. Teams Table
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Team User Pivot Table
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['team_id', 'user_id']);
        });

        // 3. Meetings Table
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at');
            $table->text('meeting_notes')->nullable();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Modify Tasks Table to add team_id and make assigned_to nullable
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
            $table->foreignId('assigned_to')->nullable(false)->change();
        });

        Schema::dropIfExists('meetings');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
