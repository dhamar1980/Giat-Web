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
        // 1. Table: konsultasi
        Schema::create('konsultasi', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pasien', 36);
            $table->char('id_dokter', 36);
            $table->date('tanggal_konsultasi');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->text('keluhan_awal')->nullable();
            $table->string('jenis_layanan', 50)->default('chat'); // enum: chat, video_call, tatap_muka
            $table->string('status', 50)->default('menunggu_konfirmasi'); // enum: menunggu_konfirmasi, dijadwalkan, berlangsung, selesai, dibatalkan
            $table->timestamps();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
            $table->foreign('id_dokter')->references('id_dokter')->on('dokter')->cascadeOnDelete();
        });

        // 2. Table: konsultasi_pembayaran
        Schema::create('konsultasi_pembayaran', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_konsultasi', 36)->unique();
            $table->string('no_invoice')->unique();
            $table->decimal('jumlah_bayar', 12, 2);
            $table->string('metode_pembayaran', 50)->default('transfer_bank'); // enum: transfer_bank, va, qris, gopay, ovo
            $table->string('va_number')->nullable();
            $table->string('status_bayar', 50)->default('menunggu_pembayaran'); // enum: menunggu_pembayaran, lunas, kadaluwarsa, gagal
            $table->dateTime('waktu_bayar')->nullable();
            $table->timestamps();

            $table->foreign('id_konsultasi')->references('id')->on('konsultasi')->cascadeOnDelete();
        });

        // 3. Table: konsultasi_video_session
        Schema::create('konsultasi_video_session', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_konsultasi', 36)->unique();
            $table->string('room_id', 64);
            $table->text('token')->nullable();
            $table->integer('durasi_menit')->default(0);
            $table->string('status_panggilan', 50)->default('menunggu'); // enum: menunggu, berlangsung, berakhir, dilewati
            $table->timestamps();

            $table->foreign('id_konsultasi')->references('id')->on('konsultasi')->cascadeOnDelete();
        });

        // 4. Table: konsultasi_pesan
        Schema::create('konsultasi_pesan', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_konsultasi', 36);
            $table->char('id_sender', 36);
            $table->text('pesan');
            $table->string('attachment_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_konsultasi')->references('id')->on('konsultasi')->cascadeOnDelete();
            $table->foreign('id_sender')->references('id')->on('user')->cascadeOnDelete();
        });

        // 5. Table: resep
        Schema::create('resep', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('no_resep', 32)->unique();
            $table->char('id_konsultasi', 36)->nullable()->unique();
            $table->char('id_dokter', 36);
            $table->char('id_pasien', 36);
            $table->date('tanggal_resep');
            $table->text('diagnosis')->nullable();
            $table->text('catatan_dokter')->nullable();
            $table->string('status', 50)->default('aktif'); // enum: aktif, ditebus, kadaluwarsa, dibatalkan
            $table->timestamps();

            $table->foreign('id_konsultasi')->references('id')->on('konsultasi')->nullOnDelete();
            $table->foreign('id_dokter')->references('id_dokter')->on('dokter')->cascadeOnDelete();
            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
        });

        // 6. Table: resep_item
        Schema::create('resep_item', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_resep', 36);
            $table->string('nama_obat');
            $table->string('dosis')->nullable();
            $table->string('aturan_pakai')->nullable();
            $table->string('waktu_penggunaan')->nullable();
            $table->integer('jumlah')->default(1);
            $table->text('catatan_khusus')->nullable();
            $table->timestamps();

            $table->foreign('id_resep')->references('id')->on('resep')->cascadeOnDelete();
        });

        // Backward compatibility view for resep_obat
        DB::statement('CREATE OR REPLACE VIEW "resep_obat" AS SELECT id AS id_resep, id_konsultasi, id_dokter, id_pasien, tanggal_resep, diagnosis, catatan_dokter, status, created_at, updated_at FROM "resep";');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "resep_obat";');
        Schema::dropIfExists('resep_item');
        Schema::dropIfExists('resep');
        Schema::dropIfExists('konsultasi_pesan');
        Schema::dropIfExists('konsultasi_video_session');
        Schema::dropIfExists('konsultasi_pembayaran');
        Schema::dropIfExists('konsultasi');
    }
};
