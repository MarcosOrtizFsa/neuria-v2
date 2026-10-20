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
        Schema::create('research_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_research_id')->constrained('news_research')->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 2048);
            $table->string('url_hash', 64);
            $table->string('publisher')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->dateTime('consulted_at');
            $table->text('relevant_summary')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->unique(['news_research_id', 'url_hash'], 'research_source_url_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_sources');
    }
};
