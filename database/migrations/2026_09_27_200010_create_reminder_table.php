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
        Schema::create('reminder', function (Blueprint $table) {
            $table->id('id_reminder');
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->string('nama');
            $table->date('tanggal');
            $table->time('waktu');
            $table->text('keterangan')->nullable();
            $table->string('pengulangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder');
    }
};
