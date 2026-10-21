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
        Schema::create('automation_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80);
            $table->unsignedInteger('version');
            $table->string('name');
            $table->string('task_type', 60);
            $table->unsignedBigInteger('price_per_run_minor');
            $table->string('currency', 3)->default('ARS');
            $table->json('policies');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_demo')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['code', 'version']);
            $table->index(['task_type', 'is_active', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_rates');
    }
};
