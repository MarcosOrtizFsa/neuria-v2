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
        Schema::create('automation_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_result_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 2048);
            $table->string('canonical_url_hash', 64);
            $table->string('publisher')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->dateTime('consulted_at');
            $table->text('citation')->nullable();
            $table->boolean('publication_date_verified')->default(false);
            $table->timestamps();
            $table->unique(['automation_result_id', 'canonical_url_hash'], 'automation_result_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_sources');
    }
};
