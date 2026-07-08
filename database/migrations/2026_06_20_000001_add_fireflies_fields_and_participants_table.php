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
        // 1. Make team_id nullable and add fireflies_meeting_id to meetings table
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->change();
            $table->string('fireflies_meeting_id')->nullable()->unique()->index()->after('id');
        });

        // 2. Add fireflies_transcript_id to meeting_transcripts
        Schema::table('meeting_transcripts', function (Blueprint $table) {
            $table->string('fireflies_transcript_id')->nullable()->index()->after('id');
        });

        // 3. Add fireflies_action_item_id to meeting_action_items
        Schema::table('meeting_action_items', function (Blueprint $table) {
            $table->string('fireflies_action_item_id')->nullable()->index()->after('id');
        });

        // 4. Create meeting_participants table
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->string('fireflies_participant_id')->nullable()->index();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');

        Schema::table('meeting_action_items', function (Blueprint $table) {
            $table->dropColumn('fireflies_action_item_id');
        });

        Schema::table('meeting_transcripts', function (Blueprint $table) {
            $table->dropColumn('fireflies_transcript_id');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('fireflies_meeting_id');
            $table->foreignId('team_id')->nullable(false)->change();
        });
    }
};
