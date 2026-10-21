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
        Schema::create('automation_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->restrictOnDelete();
            $table->string('currency', 3)->default('ARS');
            $table->unsignedBigInteger('monthly_limit_minor');
            $table->unsignedBigInteger('reserved_minor')->default(0);
            $table->unsignedBigInteger('settled_minor')->default(0);
            $table->date('period_start');
            $table->unsignedInteger('active_task_limit')->default(10);
            $table->unsignedInteger('daily_run_limit')->default(20);
            $table->unsignedInteger('concurrency_limit')->default(2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_budgets');
    }
};
