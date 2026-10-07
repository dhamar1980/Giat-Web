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
        // 1. Table: apotek_jam_operasional
        Schema::create('apotek_jam_operasional', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_apotek', 36);
            $table->string('hari', 20); // enum: senin, selasa, rabu, kamis, jumat, sabtu, minggu
            $table->boolean('is_open')->default(true);
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->timestamps();

            $table->foreign('id_apotek')->references('id_apotek')->on('apotek')->cascadeOnDelete();
        });

        // 2. Table: apotek_area_layanan
        Schema::create('apotek_area_layanan', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_apotek', 36);
            $table->string('nama_area');
            $table->text('detail')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_apotek')->references('id_apotek')->on('apotek')->cascadeOnDelete();
        });

        // 3. Table: obat
        Schema::create('obat', function (Blueprint $table) {
            $table->char('id_obat', 36)->primary();
            $table->char('id_apotek', 36);
            $table->string('nama_obat');
            $table->string('kategori');
            $table->string('bentuk_sediaan')->nullable();
            $table->string('dosis')->nullable();
            $table->string('satuan_kemasan')->nullable();
            $table->integer('stok_total')->default(0);
            $table->decimal('harga_beli', 12, 2)->default(0);
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->text('aturan_pakai_umum')->nullable();
            $table->string('foto_obat')->nullable();
            $table->timestamps();

            $table->foreign('id_apotek')->references('id_apotek')->on('apotek')->cascadeOnDelete();
        });

        // 4. Table: obat_batches
        Schema::create('obat_batches', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_obat', 36);
            $table->char('id_apotek', 36);
            $table->string('no_batch')->unique();
            $table->integer('stok_batch')->default(0);
            $table->date('tanggal_kadaluwarsa');
            $table->string('status', 50)->default('tersedia'); // enum: tersedia, hampir_kadaluwarsa, kadaluwarsa, habis
            $table->timestamps();

            $table->foreign('id_obat')->references('id_obat')->on('obat')->cascadeOnDelete();
            $table->foreign('id_apotek')->references('id_apotek')->on('apotek')->cascadeOnDelete();
        });

        // Backward compatibility view for stock_obat
        DB::statement('CREATE OR REPLACE VIEW "stock_obat" AS SELECT id AS id_stock, id_obat, id_apotek AS id_apoteker, stok_batch AS jumlah_stock, tanggal_kadaluwarsa, created_at, updated_at FROM "obat_batches";');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "stock_obat";');
        Schema::dropIfExists('obat_batches');
        Schema::dropIfExists('obat');
        Schema::dropIfExists('apotek_area_layanan');
        Schema::dropIfExists('apotek_jam_operasional');
    }
};
