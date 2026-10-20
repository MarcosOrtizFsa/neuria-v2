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
        Schema::create('editorial_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('editorial_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('type', 20);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['editorial_profile_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_sources');
    }
};
