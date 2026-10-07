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
        Schema::create('user', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role', 30)->default('pasien'); // enum: pasien, dokter, apotek, admin
            $table->string('firebase_uid')->nullable()->unique();
            $table->string('auth_provider', 30)->default('local'); // enum: local, google, firebase
            $table->rememberToken();
            $table->timestamps();
        });

        // View users -> user for Laravel default conventions if needed
        DB::statement('CREATE OR REPLACE VIEW "users" AS SELECT * FROM "user";');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "users";');
        Schema::dropIfExists('user');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
