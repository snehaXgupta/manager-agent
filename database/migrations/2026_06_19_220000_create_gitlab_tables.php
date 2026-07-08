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
        // 1. Add fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_user_id')->nullable()->unique()->after('gitlab_username');
            $table->string('gitlab_email')->nullable()->after('gitlab_user_id');
        });

        // 2. Rename pivot table project_user to project_members and add gitlab_member_id
        Schema::rename('project_user', 'project_members');
        Schema::table('project_members', function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_member_id')->nullable()->after('user_id');
        });

        // 3. Create repositories table
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('gitlab_project_id')->unique();
            $table->string('repository_name');
            $table->string('repository_url');
            $table->string('visibility')->default('private');
            $table->timestamps();

            $table->index('project_id');
            $table->index('gitlab_project_id');
        });

        // 4. Create gitlab_events table
        Schema::create('gitlab_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->foreignId('repository_id')->nullable()->constrained('repositories')->onDelete('cascade');
            $table->longText('payload_json');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index('event_type');
            $table->index('project_id');
            $table->index('repository_id');
            $table->index('received_at');
        });

        // 5. Create commits table
        Schema::create('commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('repository_id')->constrained('repositories')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->string('commit_sha');
            $table->string('branch');
            $table->text('message');
            $table->integer('files_changed')->default(0);
            $table->integer('additions')->default(0);
            $table->integer('deletions')->default(0);
            $table->timestamp('committed_at');
            $table->timestamps();

            $table->unique('commit_sha');
            $table->index('project_id');
            $table->index('repository_id');
            $table->index('employee_id');
            $table->index('branch');
            $table->index('committed_at');
        });

        // 6. Create merge_requests table
        Schema::create('merge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('repository_id')->constrained('repositories')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('gitlab_mr_id'); // maps to GitLab IID
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_branch');
            $table->string('target_branch');
            $table->string('status')->default('Opened'); // Opened, Approved, Rejected, Merged
            $table->timestamps();

            $table->index('project_id');
            $table->index('repository_id');
            $table->index('employee_id');
            $table->index('gitlab_mr_id');
            $table->index('status');
        });

        // 7. Create reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_request_id')->constrained('merge_requests')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->string('status')->default('Commented'); // Approved, Changes Requested, Commented
            $table->timestamps();

            $table->index('merge_request_id');
            $table->index('reviewer_id');
            $table->index('status');
        });

        // 8. Create approvals table
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merge_request_id')->constrained('merge_requests')->onDelete('cascade');
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('approval_date');
            $table->timestamps();

            $table->index('merge_request_id');
            $table->index('approved_by');
            $table->index('approval_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('merge_requests');
        Schema::dropIfExists('commits');
        Schema::dropIfExists('gitlab_events');
        Schema::dropIfExists('repositories');

        if (Schema::hasTable('project_members')) {
            Schema::table('project_members', function (Blueprint $table) {
                $table->dropColumn('gitlab_member_id');
            });
            Schema::rename('project_members', 'project_user');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gitlab_user_id', 'gitlab_email']);
        });
    }
};
