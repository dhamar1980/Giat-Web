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
        Schema::create('edukasi_kesehatan', function (Blueprint $table) {
            $table->id('id_edukasi');
            $table->string('judul');
            $table->longText('isi_edukasi');
            $table->string('kategori')->nullable();
            $table->string('gambar')->nullable();
            $table->dateTime('tanggal_publish')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edukasi_kesehatan');
    }
};
