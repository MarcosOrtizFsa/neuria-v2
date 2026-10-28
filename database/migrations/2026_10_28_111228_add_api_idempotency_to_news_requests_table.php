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
        Schema::table('news_requests', function (Blueprint $table) {
            $table->foreignId('api_media_outlet_id')->nullable()->after('requested_by')->constrained('media_outlets')->nullOnDelete();
            $table->foreignId('api_credential_id')->nullable()->after('api_media_outlet_id')->constrained('media_api_credentials')->nullOnDelete();
            $table->string('api_idempotency_key', 128)->nullable()->after('api_credential_id');
            $table->char('api_payload_hash', 64)->nullable()->after('api_idempotency_key');
            $table->unique(['api_media_outlet_id', 'api_idempotency_key'], 'news_requests_api_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_requests', function (Blueprint $table) {
            $table->dropUnique('news_requests_api_idempotency_unique');
            $table->dropConstrainedForeignId('api_credential_id');
            $table->dropConstrainedForeignId('api_media_outlet_id');
            $table->dropColumn(['api_idempotency_key', 'api_payload_hash']);
        });
    }
};
