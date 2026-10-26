<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Penanda per mapel
        Schema::table('tbl_mapel', function (Blueprint $table) {
            $table->boolean('boleh_gabung')->default(false);
            $table->boolean('boleh_paralel')->default(false);
        });

        // 2) Label kelompok pada sesi (mis. "Islam", "Kristen")
        Schema::table('tbl_jadwal', function (Blueprint $table) {
            $table->string('nama_kelompok')->nullable()->after('id_mapel');
        });

        // 3) Pivot sesi <-> kelas
        Schema::create('tbl_jadwal_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_jadwal')->constrained('tbl_jadwal')->cascadeOnDelete();
            $table->foreignId('id_kelas')->constrained('tbl_kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_jadwal', 'id_kelas'], 'jadwal_kelas_unique');
        });

        // 4) Pindahkan data lama: tiap jadwal lama jadi 1 baris pivot
        DB::statement(
            'INSERT INTO tbl_jadwal_kelas (id_jadwal, id_kelas, created_at, updated_at)
            SELECT id, id_kelas, NOW(), NOW() FROM tbl_jadwal'
        );

        // 5) Lepas aturan "satu kelas satu pelajaran per jam".
        //    Aturan guru (jadwal_unique_per_guru_per_jam) TETAP dipertahankan.
        Schema::table('tbl_jadwal', function (Blueprint $table) {
            $table->dropForeign('tbl_jadwal_id_kelas_foreign');
            $table->dropUnique('jadwal_unique_per_kelas_per_jam');
        });

        DB::statement('ALTER TABLE tbl_jadwal MODIFY id_kelas BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        throw new RuntimeException('Tidak bisa di-rollback otomatis. Restore dari backup.');
    }
};
