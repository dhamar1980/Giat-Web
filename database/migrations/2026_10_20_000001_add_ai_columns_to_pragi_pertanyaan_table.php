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
        Schema::table('pragi_pertanyaan', function (Blueprint $table) {
            $table->string('kode_field', 50)->nullable()->after('id');
            $table->text('pertanyaan_en')->nullable()->after('pertanyaan');
            $table->string('tipe_input', 30)->default('binary')->after('kategori');
            $table->json('opsi_jawaban')->nullable()->after('tipe_input');
            $table->integer('bobot')->default(1)->after('urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pragi_pertanyaan', function (Blueprint $table) {
            $table->dropColumn([
                'kode_field',
                'pertanyaan_en',
                'tipe_input',
                'opsi_jawaban',
                'bobot',
            ]);
        });
    }
};
