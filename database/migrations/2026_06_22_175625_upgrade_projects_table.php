<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('active'); // active, on_hold, completed
            $table->string('category')->default('Development');
            $table->boolean('is_archived')->default(false);
            $table->date('deadline')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['status', 'category', 'is_archived', 'deadline']);
        });
    }
};
