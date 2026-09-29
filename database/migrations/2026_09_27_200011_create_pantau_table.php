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
        Schema::create('pantau', function (Blueprint $table) {
            $table->id('id_pantau');
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->foreignId('id_dokter')->nullable()->constrained('dokter', 'id_dokter')->nullOnDelete();
            $table->foreignId('id_reminder')->nullable()->constrained('reminder', 'id_reminder')->nullOnDelete();
            $table->decimal('imt', 5, 2)->nullable();
            $table->decimal('bb', 5, 2)->nullable();
            $table->decimal('tb', 5, 2)->nullable();
            $table->text('keluhan')->nullable();
            $table->text('detail_keluhan')->nullable();
            $table->string('kondisi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantau');
    }
};
