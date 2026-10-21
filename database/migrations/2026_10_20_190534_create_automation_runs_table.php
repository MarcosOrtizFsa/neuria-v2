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
        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('automation_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('automation_task_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('automation_quote_id')->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_for')->nullable();
            $table->string('trigger', 20);
            $table->string('status', 40)->default('PENDIENTE');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('window_from')->nullable();
            $table->timestamp('window_to')->nullable();
            $table->string('provider_request_id')->nullable();
            $table->string('provider_response_id')->nullable();
            $table->text('sanitized_error')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamps();
            $table->unique(['automation_task_id', 'scheduled_for', 'trigger'], 'automation_scheduled_run_unique');
            $table->index(['organization_id', 'status']);
            $table->index(['status', 'dispatched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
