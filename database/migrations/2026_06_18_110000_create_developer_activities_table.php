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
        Schema::create('developer_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // github, gitlab, bitbucket
            $table->string('event_type'); // commit, pr_opened, pr_merged, review_submitted
            $table->string('repository'); // e.g. company/repo
            $table->string('reference_id'); // commit SHA, PR number, etc.
            $table->json('details_json'); // additions, deletions, messages, etc.
            $table->timestamp('occurred_at'); // when the event happened externally
            $table->timestamps();

            $table->index(['user_id', 'platform', 'event_type']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_activities');
    }
};
