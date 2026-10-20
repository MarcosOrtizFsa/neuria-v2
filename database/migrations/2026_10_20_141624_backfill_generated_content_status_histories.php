<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('generated_drafts')
            ->leftJoin('content_status_histories', 'content_status_histories.generated_draft_id', '=', 'generated_drafts.id')
            ->whereNull('content_status_histories.id')
            ->select(['generated_drafts.id', 'generated_drafts.status', 'generated_drafts.available_at', 'generated_drafts.updated_at'])
            ->orderBy('generated_drafts.id')
            ->each(function (object $draft): void {
                DB::table('content_status_histories')->insert([
                    'generated_draft_id' => $draft->id,
                    'from_status' => 'PENDIENTE_REVISION',
                    'to_status' => $draft->status,
                    'reason' => 'Migración al modelo de disponibilidad automática de Fase 5.',
                    'created_at' => $draft->available_at ?? $draft->updated_at,
                    'updated_at' => $draft->available_at ?? $draft->updated_at,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('content_status_histories')->where('reason', 'Migración al modelo de disponibilidad automática de Fase 5.')->delete();
    }
};
