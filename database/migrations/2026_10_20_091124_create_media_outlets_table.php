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
        Schema::create('media_outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('public_identifier')->unique();
            $table->string('main_url')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('type', 40)->index();
            $table->string('status', 20)->default('ACTIVO')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_outlets');
    }
};
