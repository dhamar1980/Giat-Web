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
        Schema::create('dokter', function (Blueprint $table) {
            $table->id('id_dokter');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->string('no_sip')->nullable();
            $table->string('no_str')->nullable();
            $table->string('spesialisasi')->nullable();
            $table->string('institusi')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat_praktik')->nullable();
            $table->string('foto_profil')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokter');
    }
};
