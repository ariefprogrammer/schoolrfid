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
        Schema::create('tbl_mapel', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // Kode mata pelajaran (misal: MTK, IPA), harus unik
            $table->string('mata_pelajaran'); // Nama mata pelajaran (misal: Matematika, Ilmu Pengetahuan Alam)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_mapel');
    }
};
