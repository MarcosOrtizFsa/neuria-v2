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
        Schema::table('automation_results', function (Blueprint $table) {
            $table->foreignId('generated_draft_id')
                ->nullable()
                ->unique()
                ->after('automation_run_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_draft_id');
        });
    }
};
