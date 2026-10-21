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
        Schema::create('automation_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('automation_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_task_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('automation_rate_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_per_run_minor');
            $table->unsignedInteger('projected_occurrences');
            $table->unsignedBigInteger('projected_monthly_minor');
            $table->string('currency', 3);
            $table->json('commercial_policy');
            $table->timestamp('valid_until');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_quotes');
    }
};
