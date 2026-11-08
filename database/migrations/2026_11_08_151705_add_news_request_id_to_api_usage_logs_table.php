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
        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->foreignId('news_request_id')->nullable()->after('media_api_credential_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('news_request_id');
        });
    }
};
