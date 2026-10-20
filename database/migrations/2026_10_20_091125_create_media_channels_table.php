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
        Schema::create('media_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30)->index();
            $table->string('url')->nullable();
            $table->string('external_identifier')->nullable();
            $table->string('status', 20)->default('ACTIVO')->index();
            $table->json('editorial_overrides')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_channels');
    }
};
