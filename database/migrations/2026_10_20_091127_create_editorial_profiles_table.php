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
        Schema::create('editorial_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_outlet_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('language', 10)->default('es-AR');
            $table->json('identity');
            $table->json('production');
            $table->json('sources_config');
            $table->json('restrictions');
            $table->text('additional_instructions')->nullable();
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_profiles');
    }
};
