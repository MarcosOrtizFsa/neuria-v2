<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->foreignId('media_outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_api_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('records_returned')->default(0);
            $table->unsignedInteger('duration_ms');
            $table->string('result', 30);
            $table->char('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['media_outlet_id', 'created_at'], 'api_usage_media_date');
            $table->index(['endpoint', 'created_at'], 'api_usage_endpoint_date');
            $table->index(['status_code', 'created_at'], 'api_usage_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
