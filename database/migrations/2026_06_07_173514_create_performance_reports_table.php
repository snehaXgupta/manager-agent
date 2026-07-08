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
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->string('report_type'); // 'daily', 'weekly', 'monthly'
            $table->date('period_start');
            $table->date('period_end');
            $table->json('metrics_json');
            $table->json('ai_insights_json')->nullable();
            $table->decimal('manager_score', 5, 2);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
    }
};
