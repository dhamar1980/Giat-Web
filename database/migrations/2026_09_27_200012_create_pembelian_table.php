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
        Schema::create('pembelian', function (Blueprint $table) {
            $table->id('id_pembelian');
            $table->foreignId('id_pasien')->constrained('pasien', 'id_pasien')->cascadeOnDelete();
            $table->foreignId('id_obat')->nullable()->constrained('obat', 'id_obat')->nullOnDelete();
            $table->foreignId('id_resep')->nullable()->constrained('resep_obat', 'id_resep')->nullOnDelete();
            $table->string('status_pembayaran')->default('menunggu_pembayaran');
            $table->decimal('total_harga', 12, 2);
            $table->dateTime('tanggal_pembelian')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian');
    }
};
