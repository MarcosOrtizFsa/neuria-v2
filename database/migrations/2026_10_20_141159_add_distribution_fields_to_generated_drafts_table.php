<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('generated_drafts', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->timestamp('generated_at')->nullable()->after('model');
            $table->timestamp('available_at')->nullable()->index()->after('generated_at');
            $table->timestamp('suspended_at')->nullable()->after('available_at');
            $table->timestamp('retired_at')->nullable()->after('suspended_at');
            $table->text('technical_error')->nullable()->after('retired_at');
            $table->index(['status', 'available_at', 'id'], 'draft_distribution_cursor_index');
        });

        DB::table('generated_drafts')->orderBy('id')->each(function (object $draft): void {
            DB::table('generated_drafts')->where('id', $draft->id)->update([
                'public_id' => (string) Str::uuid(),
                'status' => $draft->status === 'PENDIENTE_REVISION' ? 'DISPONIBLE' : $draft->status,
                'generated_at' => $draft->created_at,
                'available_at' => $draft->status === 'PENDIENTE_REVISION' ? $draft->updated_at : null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_drafts', function (Blueprint $table) {
            $table->dropIndex('draft_distribution_cursor_index');
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'generated_at', 'available_at', 'suspended_at', 'retired_at', 'technical_error']);
        });
    }
};
