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
        Schema::create('news_request_destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('media_channel_id')->constrained()->restrictOnDelete();
            $table->foreignId('editorial_profile_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->default('PENDIENTE')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['news_request_id', 'media_channel_id'], 'news_destination_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_request_destinations');
    }
};
