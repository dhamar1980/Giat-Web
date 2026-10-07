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
        // 1. Table: pesanan_obat
        Schema::create('pesanan_obat', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('no_pesanan')->unique();
            $table->char('id_pasien', 36);
            $table->char('id_apotek', 36);
            $table->char('id_resep', 36)->nullable();
            $table->string('tipe_pesanan', 50)->default('umum'); // enum: umum, resep
            $table->string('metode_pengambilan', 50)->default('diantar'); // enum: diantar, ambil_sendiri
            $table->string('nama_penerima');
            $table->string('no_hp_penerima');
            $table->text('alamat_pengiriman')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('ongkos_kirim', 12, 2)->default(0);
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->string('metode_pembayaran', 50)->default('transfer_bank'); // enum: transfer_bank, va, cod, qris, gopay, ovo
            $table->string('status_pembayaran', 50)->default('menunggu_pembayaran'); // enum: menunggu_pembayaran, lunas, gagal
            $table->integer('progress_step')->default(1);
            $table->string('status_pesanan', 50)->default('menunggu_konfirmasi'); // enum: menunggu_konfirmasi, diproses, dikirim, siap_diambil, selesai, dibatalkan, ditolak
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
            $table->foreign('id_apotek')->references('id_apotek')->on('apotek')->cascadeOnDelete();
            $table->foreign('id_resep')->references('id')->on('resep')->nullOnDelete();
        });

        // 2. Table: pesanan_obat_trackings
        Schema::create('pesanan_obat_trackings', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pesanan', 36);
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_pesanan')->references('id')->on('pesanan_obat')->cascadeOnDelete();
        });

        // 3. Table: pesanan_obat_items
        Schema::create('pesanan_obat_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pesanan', 36);
            $table->char('id_obat', 36);
            $table->string('nama_obat');
            $table->integer('jumlah')->default(1);
            $table->decimal('harga_satuan', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_pesanan')->references('id')->on('pesanan_obat')->cascadeOnDelete();
            $table->foreign('id_obat')->references('id_obat')->on('obat')->cascadeOnDelete();
        });

        // Backward compatibility view for pembelian
        DB::statement('CREATE OR REPLACE VIEW "pembelian" AS SELECT id AS id_pembelian, id_pasien, id_apotek, id_resep, tipe_pesanan AS tipe_pembelian, status_pesanan, total_bayar AS total_harga, metode_pembayaran, status_pembayaran, alamat_pengiriman, created_at, updated_at FROM "pesanan_obat";');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "pembelian";');
        Schema::dropIfExists('pesanan_obat_items');
        Schema::dropIfExists('pesanan_obat_trackings');
        Schema::dropIfExists('pesanan_obat');
    }
};
