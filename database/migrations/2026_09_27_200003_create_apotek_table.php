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
        Schema::create('apotek', function (Blueprint $table) {
            $table->id('id_apotek');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('no_sip')->nullable();
            $table->string('jam_operasional')->nullable();
            $table->text('lokasi_apotek')->nullable();
            $table->string('area_layanan')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apotek');
    }
};
