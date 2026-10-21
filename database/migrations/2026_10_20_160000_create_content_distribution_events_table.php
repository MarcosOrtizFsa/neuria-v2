<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_distribution_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('generated_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_status_history_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('media_outlet_id')->constrained()->restrictOnDelete();
            $table->foreignId('media_channel_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 40);
            $table->uuid('content_public_id');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['media_outlet_id', 'id'], 'distribution_events_media_cursor');
            $table->index(['media_outlet_id', 'media_channel_id', 'id'], 'distribution_events_channel_cursor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_distribution_events');
    }
};
