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
        Schema::create('konsultasi', function (Blueprint $table) {
            $table->id('id_konsultasi');
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->foreignId('id_dokter')->constrained('dokter', 'id_dokter')->cascadeOnDelete();
            $table->dateTime('tanggal_konsultasi');
            $table->string('status_konsultasi')->default('menunggu');
            $table->text('catatan_dokter')->nullable();
            $table->text('isi_konsultasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konsultasi');
    }
};
