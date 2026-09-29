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
        // 1. Buat tabel notifikasi jika belum ada
        if (! Schema::hasTable('notifikasi')) {
            Schema::create('notifikasi', function (Blueprint $table) {
                $table->id('id_notifikasi');
                $table->unsignedBigInteger('id_user')->nullable(); // ID pasien, dokter, atau apotek
                $table->string('role')->default('pasien'); // 'pasien', 'dokter', 'apotek', 'all'
                $table->string('judul');
                $table->text('pesan');
                $table->string('tipe')->default('umum'); // 'konsultasi', 'reminder', 'pesanan', 'resep', 'pragi', 'umum'
                $table->json('data')->nullable(); // metadata tambahan seperti id_konsultasi, id_pesanan, dll.
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['id_user', 'role']);
                $table->index('is_read');
            });
        }

        // 2. Tambah kolom is_active pada reminder jika belum ada
        if (Schema::hasTable('reminder') && ! Schema::hasColumn('reminder', 'is_active')) {
            Schema::table('reminder', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('pengulangan');
            });
        }

        // 3. Tambah kolom status_layanan pada apotek jika belum ada
        if (Schema::hasTable('apotek') && ! Schema::hasColumn('apotek', 'status_layanan')) {
            Schema::table('apotek', function (Blueprint $table) {
                $table->string('status_layanan')->default('buka')->after('area_layanan'); // 'buka' atau 'tutup'
            });
        }

        // 4. Tambah kolom pelacakan (resi, kurir, timeline) pada pembelian jika belum ada
        if (Schema::hasTable('pembelian')) {
            Schema::table('pembelian', function (Blueprint $table) {
                if (! Schema::hasColumn('pembelian', 'nomor_resi')) {
                    $table->string('nomor_resi')->nullable()->after('status_pesanan');
                }
                if (! Schema::hasColumn('pembelian', 'kurir')) {
                    $table->string('kurir')->nullable()->after('nomor_resi');
                }
                if (! Schema::hasColumn('pembelian', 'status_lacak')) {
                    $table->json('status_lacak')->nullable()->after('kurir');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pembelian')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('pembelian', 'nomor_resi')) $columns[] = 'nomor_resi';
                if (Schema::hasColumn('pembelian', 'kurir')) $columns[] = 'kurir';
                if (Schema::hasColumn('pembelian', 'status_lacak')) $columns[] = 'status_lacak';
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('apotek') && Schema::hasColumn('apotek', 'status_layanan')) {
            Schema::table('apotek', function (Blueprint $table) {
                $table->dropColumn('status_layanan');
            });
        }

        if (Schema::hasTable('reminder') && Schema::hasColumn('reminder', 'is_active')) {
            Schema::table('reminder', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        Schema::dropIfExists('notifikasi');
    }
};
