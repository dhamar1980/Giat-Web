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
        Schema::table('obat', function (Blueprint $table) {
            $table->string('dosis')->nullable()->after('tipe_obat');
            $table->text('efek_samping')->nullable()->after('dosis');
            $table->string('gambar')->nullable()->after('efek_samping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->dropColumn(['dosis', 'efek_samping', 'gambar']);
        });
    }
};
