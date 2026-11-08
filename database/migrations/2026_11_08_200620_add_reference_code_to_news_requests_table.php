<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news_requests', function (Blueprint $table) {
            $table->string('reference_code', 15)->nullable()->unique()->after('public_id');
        });

        DB::table('news_requests')->select(['id', 'public_id'])->orderBy('id')->chunkById(200, function ($requests): void {
            foreach ($requests as $request) {
                $source = $request->public_id ?: 'legacy-news-request-'.$request->id;
                DB::table('news_requests')->where('id', $request->id)->update([
                    'reference_code' => 'NR-'.strtoupper(substr(hash('sha256', $source), 0, 12)),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_requests', function (Blueprint $table) {
            $table->dropUnique(['reference_code']);
            $table->dropColumn('reference_code');
        });
    }
};
