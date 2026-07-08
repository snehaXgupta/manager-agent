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
            $table->string('github_username')->nullable()->after('role');
            $table->string('gitlab_username')->nullable()->after('github_username');
            $table->string('bitbucket_username')->nullable()->after('gitlab_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['github_username', 'gitlab_username', 'bitbucket_username']);
        });
    }
};
