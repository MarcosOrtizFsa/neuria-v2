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
        Schema::create('automation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_run_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('character_count')->default(0);
            $table->json('warnings')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_results');
    }
};
