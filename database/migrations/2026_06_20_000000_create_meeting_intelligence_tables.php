<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify meetings table
        Schema::table('meetings', function (Blueprint $table) {
            $table->date('meeting_date')->nullable()->index()->after('description');
            $table->time('meeting_time')->nullable()->after('meeting_date');
            $table->integer('duration')->default(30)->after('meeting_time');
            $table->json('participants')->nullable()->after('duration');
            $table->string('meeting_link')->nullable()->after('participants');
            $table->string('status')->default('Scheduled')->index()->after('meeting_link');
            $table->foreignId('created_by')->nullable()->after('manager_id')->constrained('users')->onDelete('set null');
        });

        // Migrate existing meeting schedules
        DB::table('meetings')->get()->each(function ($meeting) {
            if ($meeting->scheduled_at) {
                try {
                    $dt = Carbon::parse($meeting->scheduled_at);
                    DB::table('meetings')->where('id', $meeting->id)->update([
                        'meeting_date' => $dt->toDateString(),
                        'meeting_time' => $dt->toTimeString(),
                        'created_by' => $meeting->manager_id,
                    ]);
                } catch (\Exception $e) {
                    // Fail-safe
                }
            }
        });

        // 2. Create meeting_transcripts table
        Schema::create('meeting_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->unique()->constrained('meetings')->onDelete('cascade');
            $table->longText('transcript');
            $table->text('summary')->nullable();
            $table->string('sentiment')->nullable();
            $table->timestamps();
        });

        // 3. Create meeting_action_items table
        Schema::create('meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('action_item');
            $table->date('due_date')->nullable()->index();
            $table->string('priority')->default('Medium')->index(); // High, Medium, Low
            $table->string('status')->default('Pending')->index(); // Pending, In Progress, Completed
            $table->timestamps();
        });

        // 4. Create meeting_decisions table
        Schema::create('meeting_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->text('decision_text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_decisions');
        Schema::dropIfExists('meeting_action_items');
        Schema::dropIfExists('meeting_transcripts');

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['meeting_date', 'meeting_time', 'duration', 'participants', 'meeting_link', 'status', 'created_by']);
        });
    }
};
