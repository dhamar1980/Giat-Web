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
        Schema::create('pragi', function (Blueprint $table) {
            $table->id('id_pragi');
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->text('pertanyaan')->nullable();
            $table->text('hasil_prediksi')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->dateTime('tanggal_screening')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pragi');
    }
};
