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
        Schema::create('fireflies_webhook_payloads', function (Blueprint $table) {
            $table->id();
            $table->string('fireflies_meeting_id')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->json('payload');
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fireflies_webhook_payloads');
    }
};
