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
        // 1. Tambah field pada konsultasi (booking, payment, room video call)
        Schema::table('konsultasi', function (Blueprint $table) {
            $table->string('status_pembayaran')->default('menunggu_pembayaran')->after('status_konsultasi');
            $table->decimal('biaya', 12, 2)->default(50000)->after('status_pembayaran');
            $table->string('room_id')->nullable()->after('biaya');
            $table->dateTime('waktu_mulai')->nullable()->after('room_id');
            $table->dateTime('waktu_selesai')->nullable()->after('waktu_mulai');
        });

        // 2. Buat tabel pesan chat konsultasi antara pasien dan dokter
        Schema::create('konsultasi_pesan', function (Blueprint $table) {
            $table->id('id_pesan');
            $table->foreignId('id_konsultasi')->constrained('konsultasi', 'id_konsultasi')->cascadeOnDelete();
            $table->string('sender_type'); // 'pasien' atau 'dokter'
            $table->unsignedBigInteger('sender_id');
            $table->text('pesan');
            $table->string('tipe')->default('text'); // 'text', 'image', 'resep', 'system', 'video_call'
            $table->string('attachment')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // 3. Tambah field pada pembelian untuk Apotek (status pesanan, apotek tujuan, tipe obat umum/resep)
        Schema::table('pembelian', function (Blueprint $table) {
            $table->foreignId('id_apotek')->nullable()->after('id_resep')->constrained('apotek', 'id_apotek')->nullOnDelete();
            $table->string('tipe_pembelian')->default('umum')->after('id_apotek'); // 'umum', 'resep'
            $table->string('status_pesanan')->default('menunggu_konfirmasi')->after('status_pembayaran'); // 'menunggu_konfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'
            $table->integer('jumlah')->default(1)->after('total_harga');
            $table->text('alamat_pengiriman')->nullable()->after('jumlah');
            $table->text('catatan')->nullable()->after('alamat_pengiriman');
        });

        // 4. Tambah field pengaturan notifikasi & biaya konsultasi pada dokter
        Schema::table('dokter', function (Blueprint $table) {
            $table->decimal('biaya_konsultasi', 12, 2)->default(50000)->after('spesialisasi');
            $table->json('notifikasi_settings')->nullable()->after('foto_profil');
        });

        // 5. Tambah tipe obat pada tabel obat (obat bebas, bebas terbatas, obat keras)
        Schema::table('obat', function (Blueprint $table) {
            $table->string('tipe_obat')->default('bebas')->after('kategori'); // 'bebas', 'bebas_terbatas', 'keras'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->dropColumn('tipe_obat');
        });

        Schema::table('dokter', function (Blueprint $table) {
            $table->dropColumn(['biaya_konsultasi', 'notifikasi_settings']);
        });

        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropForeign(['id_apotek']);
            $table->dropColumn(['id_apotek', 'tipe_pembelian', 'status_pesanan', 'jumlah', 'alamat_pengiriman', 'catatan']);
        });

        Schema::dropIfExists('konsultasi_pesan');

        Schema::table('konsultasi', function (Blueprint $table) {
            $table->dropColumn(['status_pembayaran', 'biaya', 'room_id', 'waktu_mulai', 'waktu_selesai']);
        });
    }
};
