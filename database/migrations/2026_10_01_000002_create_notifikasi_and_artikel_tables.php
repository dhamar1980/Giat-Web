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
        // 1. Table: notifikasi
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_user', 36);
            $table->string('judul');
            $table->text('pesan');
            $table->string('kategori', 50)->default('sistem'); // enum in ERD
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_user')->references('id')->on('user')->cascadeOnDelete();
        });

        // 2. Table: edukasi_artikel
        Schema::create('edukasi_artikel', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('judul');
            $table->string('kategori');
            $table->text('konten');
            $table->string('gambar_url')->nullable();
            $table->string('penulis')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Backward compatibility view for edukasi_kesehatan
        DB::statement('CREATE OR REPLACE VIEW "edukasi_kesehatan" AS SELECT * FROM "edukasi_artikel";');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "edukasi_kesehatan";');
        Schema::dropIfExists('edukasi_artikel');
        Schema::dropIfExists('notifikasi');
    }
};
