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
        // 1. Table: pragi_pertanyaan
        Schema::create('pragi_pertanyaan', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->text('pertanyaan');
            $table->string('kategori')->nullable();
            $table->integer('urutan')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Table: pragi_skrining
        Schema::create('pragi_skrining', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pasien', 36);
            $table->integer('total_skor');
            $table->string('kategori_risiko', 50); // enum: rendah, sedang, tinggi
            $table->text('rekomendasi')->nullable();
            $table->dateTime('tanggal_skrining');
            $table->timestamps();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
        });

        // 3. Table: pragi_jawaban_detail
        Schema::create('pragi_jawaban_detail', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_skrining', 36);
            $table->char('id_pertanyaan', 36);
            $table->string('jawaban');
            $table->timestamps();

            $table->foreign('id_skrining')->references('id')->on('pragi_skrining')->cascadeOnDelete();
            $table->foreign('id_pertanyaan')->references('id')->on('pragi_pertanyaan')->cascadeOnDelete();
        });

        // 4. Table: pragi_chats
        Schema::create('pragi_chats', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pasien', 36);
            $table->string('role_sender', 20); // enum: user, assistant
            $table->text('pesan');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
        });

        // 5. Table: pantau_kesehatan
        Schema::create('pantau_kesehatan', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pasien', 36);
            $table->decimal('berat_badan', 5, 2);
            $table->decimal('tinggi_badan', 5, 2);
            $table->decimal('bmi', 5, 2);
            $table->string('kategori_bmi', 50); // enum: kurang, normal, berlebih, obesitas
            $table->string('kondisi', 50); // enum: baik, perlu_perhatian, waspada, buruk
            $table->string('keluhan')->nullable();
            $table->text('detail_keluhan')->nullable();
            $table->dateTime('tanggal_pantau');
            $table->timestamps();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
        });

        // 6. Table: reminders
        Schema::create('reminders', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('id_pasien', 36);
            $table->string('tipe', 50); // enum: obat, minum_air, kontrol, aktivitas
            $table->string('judul');
            $table->string('subjudul')->nullable();
            $table->time('waktu');
            $table->date('tanggal')->nullable();
            $table->string('pengulangan', 50)->default('harian'); // enum: harian, mingguan, sekali
            $table->string('status', 50)->default('aktif'); // enum: aktif, selesai, dilewati
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_pasien')->references('id_pasien')->on('pasien')->cascadeOnDelete();
        });

        // Backward compatibility views
        DB::statement('CREATE OR REPLACE VIEW "pantau" AS SELECT id AS id_pantau, id_pasien, berat_badan AS bb, tinggi_badan AS tb, bmi AS imt, kondisi, keluhan, detail_keluhan, tanggal_pantau, created_at, updated_at FROM "pantau_kesehatan";');
        DB::statement('CREATE OR REPLACE VIEW "reminder" AS SELECT id AS id_reminder, id_pasien, tipe, judul AS nama_obat, subjudul AS dosis, waktu, tanggal, pengulangan, status, is_active, created_at, updated_at FROM "reminders";');
        DB::statement('CREATE OR REPLACE VIEW "pragi" AS SELECT id AS id_pragi, id_pasien, total_skor, kategori_risiko, rekomendasi, tanggal_skrining, created_at, updated_at FROM "pragi_skrining";');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS "pragi";');
        DB::statement('DROP VIEW IF EXISTS "reminder";');
        DB::statement('DROP VIEW IF EXISTS "pantau";');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('pantau_kesehatan');
        Schema::dropIfExists('pragi_chats');
        Schema::dropIfExists('pragi_jawaban_detail');
        Schema::dropIfExists('pragi_skrining');
        Schema::dropIfExists('pragi_pertanyaan');
    }
};
