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
        Schema::create('ai_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_request_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('processable');
            $table->uuid('idempotency_key')->unique();
            $table->string('operation', 40)->index();
            $table->string('status', 30)->default('PENDIENTE')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_job_runs');
    }
};
