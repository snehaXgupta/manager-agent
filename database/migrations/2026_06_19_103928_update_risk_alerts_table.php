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
        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->double('confidence_score')->default(0.85)->after('metrics_json');
            $table->text('manager_notes')->nullable()->after('confidence_score');
            $table->string('follow_up_action')->nullable()->after('manager_notes');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->dropIndex(['is_resolved']);
            $table->dropColumn(['confidence_score', 'manager_notes', 'follow_up_action']);
        });
    }
};
