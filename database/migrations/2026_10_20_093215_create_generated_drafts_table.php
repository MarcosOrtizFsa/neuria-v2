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
        Schema::create('generated_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_request_destination_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('news_research_id')->constrained('news_research')->restrictOnDelete();
            $table->foreignId('editorial_profile_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->json('source_references')->nullable();
            $table->json('editorial_warnings')->nullable();
            $table->string('status', 40)->default('PENDIENTE_REVISION')->index();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_drafts');
    }
};
