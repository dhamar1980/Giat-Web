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
        // 1. Table Pasien
        Schema::table('pasien', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->index()->after('email');
            $table->string('auth_provider')->default('local')->after('firebase_uid');
            $table->string('password')->nullable()->change();
        });

        // 2. Table Dokter
        Schema::table('dokter', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->index()->after('email');
            $table->string('auth_provider')->default('local')->after('firebase_uid');
            $table->string('password')->nullable()->change();
        });

        // 3. Table Apotek
        Schema::table('apotek', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->index()->after('email');
            $table->string('auth_provider')->default('local')->after('firebase_uid');
            $table->string('password')->nullable()->change();
        });

        // 4. Table Users
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->index()->after('email');
            $table->string('auth_provider')->default('local')->after('firebase_uid');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });

        Schema::table('dokter', function (Blueprint $table) {
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });

        Schema::table('apotek', function (Blueprint $table) {
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });
    }
};
