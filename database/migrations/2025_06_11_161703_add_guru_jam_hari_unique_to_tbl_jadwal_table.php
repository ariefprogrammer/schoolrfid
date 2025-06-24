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
        Schema::table('tbl_jadwal', function (Blueprint $table) {
            // Tambahkan unique constraint baru untuk guru
            // Ini mencegah satu guru mengajar di lebih dari satu kelas pada jam dan hari yang sama
            $table->unique(['id_hari', 'id_jam', 'id_guru'], 'jadwal_unique_per_guru_per_jam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_jadwal', function (Blueprint $table) {
            // Hapus unique constraint jika di-rollback
            $table->dropUnique('jadwal_unique_per_guru_per_jam');
        });
    }
};
