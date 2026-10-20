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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('name');
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password')->index();
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['last_name', 'is_active', 'last_login_at', 'deleted_at']);
        });
    }
};
