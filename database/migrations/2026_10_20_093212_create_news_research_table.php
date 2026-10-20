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
        Schema::create('news_research', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 40)->default('PENDIENTE')->index();
            $table->string('model')->nullable();
            $table->string('openai_response_id')->nullable();
            $table->json('result')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['news_request_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_research');
    }
};
