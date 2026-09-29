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
        Schema::create('resep_obat', function (Blueprint $table) {
            $table->id('id_resep');
            $table->foreignId('id_dokter')->constrained('dokter', 'id_dokter')->cascadeOnDelete();
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->foreignId('id_obat')->constrained('obat', 'id_obat')->cascadeOnDelete();
            $table->foreignId('id_apoteker')->nullable()->constrained('apotek', 'id_apotek')->nullOnDelete();
            $table->string('dosis');
            $table->date('tanggal_resep');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resep_obat');
    }
};
