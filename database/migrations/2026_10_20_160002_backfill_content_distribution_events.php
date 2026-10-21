<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('content_status_histories')->whereIn('to_status', ['DISPONIBLE', 'SUSPENDIDO', 'RETIRADO'])->orderBy('id')->each(function (object $history): void {
            $draft = DB::table('generated_drafts')->where('id', $history->generated_draft_id)->first();
            $destination = $draft ? DB::table('news_request_destinations')->where('id', $draft->news_request_destination_id)->first() : null;
            if (! $draft || ! $destination || ! $draft->public_id) {
                return;
            }
            $eventType = match (true) {
                $history->to_status === 'DISPONIBLE' && $history->from_status === 'SUSPENDIDO' => 'CONTENT_REACTIVATED',
                $history->to_status === 'DISPONIBLE' => 'CONTENT_AVAILABLE',
                $history->to_status === 'SUSPENDIDO' => 'CONTENT_SUSPENDED',
                default => 'CONTENT_WITHDRAWN',
            };
            DB::table('content_distribution_events')->updateOrInsert(['content_status_history_id' => $history->id], [
                'public_id' => (string) Str::uuid(), 'generated_draft_id' => $draft->id,
                'media_outlet_id' => $destination->media_outlet_id, 'media_channel_id' => $destination->media_channel_id,
                'event_type' => $eventType, 'content_public_id' => $draft->public_id, 'occurred_at' => $history->created_at,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('content_distribution_events')->delete();
    }
};
