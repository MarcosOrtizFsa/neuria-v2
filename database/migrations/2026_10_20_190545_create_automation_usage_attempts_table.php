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
        Schema::create('automation_usage_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt');
            $table->string('operation', 40);
            $table->string('model');
            $table->string('provider_response_id')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('tool_calls')->default(0);
            $table->unsignedBigInteger('technical_cost_minor')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('cost_condition', 20)->default('NO_CONCILIADA');
            $table->string('status', 30);
            $table->timestamps();
            $table->unique(['automation_run_id', 'attempt', 'operation'], 'automation_usage_attempt_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_usage_attempts');
    }
};
