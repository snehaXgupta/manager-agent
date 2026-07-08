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
        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->string('risk_level'); // 'low', 'medium', 'high'
            $table->string('risk_type');  // 'burnout', 'deadline', 'engagement', 'performance'
            $table->text('reason');
            $table->json('metrics_json')->nullable();
            $table->timestamp('detected_at');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_alerts');
    }
};
