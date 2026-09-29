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
        Schema::create('pantau_edukasi', function (Blueprint $table) {
            $table->foreignId('id_pantau')->constrained('pantau', 'id_pantau')->cascadeOnDelete();
            $table->foreignId('id_edukasi')->constrained('edukasi_kesehatan', 'id_edukasi')->cascadeOnDelete();
            $table->primary(['id_pantau', 'id_edukasi']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantau_edukasi');
    }
};
