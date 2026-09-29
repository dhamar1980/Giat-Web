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
        Schema::create('stock_obat', function (Blueprint $table) {
            $table->id('id_stock');
            $table->foreignId('id_obat')->constrained('obat', 'id_obat')->cascadeOnDelete();
            $table->foreignId('id_apoteker')->constrained('apotek', 'id_apotek')->cascadeOnDelete();
            $table->integer('jumlah_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_obat');
    }
};
