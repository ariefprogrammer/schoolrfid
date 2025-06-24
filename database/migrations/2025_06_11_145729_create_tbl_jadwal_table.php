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
        Schema::create('tbl_jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_hari')->constrained('tbl_hari')->cascadeOnDelete(); // FK ke tbl_hari
            $table->foreignId('id_jam')->constrained('tbl_jam')->cascadeOnDelete(); // FK ke tbl_jam
            $table->foreignId('id_guru')->constrained('tbl_guru')->cascadeOnDelete(); // FK ke tbl_guru
            $table->foreignId('id_mapel')->constrained('tbl_mapel')->cascadeOnDelete(); // FK ke tbl_mapel
            $table->foreignId('id_kelas')->constrained('tbl_kelas')->cascadeOnDelete(); // FK ke tbl_kelas
            $table->timestamps();

            // Tambahkan unique constraint untuk mencegah jadwal ganda pada hari, jam, dan kelas yang sama
            // Ini memastikan satu kelas tidak memiliki dua pelajaran di jam dan hari yang sama
            $table->unique(['id_hari', 'id_jam', 'id_kelas'], 'jadwal_unique_per_kelas_per_jam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_jadwal');
    }
};
