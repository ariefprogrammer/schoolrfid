<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->foreignId('id_jadwal')->nullable()->after('id_mapel')
                ->constrained('tbl_jadwal')->nullOnDelete();
            $table->string('label_kelas')->nullable()->after('id_kelas');

            $table->dropForeign('tbl_presensi_guru_id_kelas_foreign');
            $table->dropUnique('unique_presensi_guru_per_jadwal_tgl');
        });

        DB::statement('ALTER TABLE tbl_presensi_guru MODIFY id_kelas BIGINT UNSIGNED NULL');

        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->foreign('id_kelas', 'tbl_presensi_guru_id_kelas_foreign')
                ->references('id')->on('tbl_kelas')->nullOnDelete();

            $table->unique(['tanggal_jadwal', 'id_jadwal'], 'unique_presensi_guru_per_sesi_tgl');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Tidak bisa di-rollback otomatis. Restore dari backup.');
    }
};
