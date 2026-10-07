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
        // 1. Table: pasien
        Schema::create('pasien', function (Blueprint $table) {
            $table->char('id_pasien', 36)->primary();
            $table->string('nama');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('jenis_kelamin', 10)->nullable(); // enum: L, P
            $table->string('nik', 20)->unique()->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->string('foto_profile')->nullable();
            $table->timestamps();

            $table->foreign('id_pasien')->references('id')->on('user')->cascadeOnDelete();
        });

        // 2. Table: dokter
        Schema::create('dokter', function (Blueprint $table) {
            $table->char('id_dokter', 36)->primary();
            $table->string('nama');
            $table->string('spesialisasi');
            $table->string('institusi')->nullable();
            $table->string('no_str')->unique()->nullable();
            $table->string('no_sip')->unique()->nullable();
            $table->string('no_hp')->nullable();
            $table->decimal('tarif_konsultasi', 12, 2)->default(0);
            $table->string('foto_profile')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->foreign('id_dokter')->references('id')->on('user')->cascadeOnDelete();
        });

        // 3. Table: apotek
        Schema::create('apotek', function (Blueprint $table) {
            $table->char('id_apotek', 36)->primary();
            $table->string('nama_apotek');
            $table->string('penanggung_jawab')->nullable();
            $table->string('no_sipa_sia')->unique()->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('lokasi_lat_long')->nullable();
            $table->boolean('status_layanan')->default(true);
            $table->string('foto_profile')->nullable();
            $table->timestamps();

            $table->foreign('id_apotek')->references('id')->on('user')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apotek');
        Schema::dropIfExists('dokter');
        Schema::dropIfExists('pasien');
    }
};
