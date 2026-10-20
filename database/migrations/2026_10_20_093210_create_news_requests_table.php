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
        Schema::create('news_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('context')->nullable();
            $table->longText('specific_instructions')->nullable();
            $table->string('category')->nullable();
            $table->string('priority', 20)->default('NORMAL');
            $table->dateTime('event_at')->nullable();
            $table->boolean('web_search_enabled')->default(true);
            $table->json('initial_urls')->nullable();
            $table->text('research_notes')->nullable();
            $table->string('status', 40)->default('BORRADOR')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_requests');
    }
};
