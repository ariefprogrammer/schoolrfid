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
        Schema::create('tbl_presensi_guru', function (Blueprint $table) {
            $table->id();
            $table->date('periode_awal'); // Tanggal Senin di minggu jadwal ini
            $table->date('periode_akhir'); // Tanggal Sabtu di minggu jadwal ini

            // Foreign keys ke komponen jadwal (harus ada not null secara default)
            $table->foreignId('id_kelas')->constrained('tbl_kelas')->cascadeOnDelete();
            $table->foreignId('id_hari')->constrained('tbl_hari')->cascadeOnDelete();
            $table->foreignId('id_jam')->constrained('tbl_jam')->cascadeOnDelete();
            $table->foreignId('id_guru')->constrained('tbl_guru')->cascadeOnDelete();
            $table->foreignId('id_mapel')->constrained('tbl_mapel')->cascadeOnDelete();

            $table->dateTime('jam_in')->nullable(); // Waktu check-in guru
            $table->string('status_in')->nullable(); // Status check-in (Hadir, Terlambat, Tidak Hadir, dll.)
            $table->dateTime('jam_out')->nullable(); // Waktu check-out guru
            $table->string('status_out')->nullable(); // Status check-out (Pulang, Bolos, dll.)

            $table->date('tanggal_jadwal'); // Tanggal spesifik jadwal di minggu ini

            $table->timestamps();

            // Unique constraint untuk mencegah duplikasi entri presensi per jadwal di tanggal tertentu
            // Ini memastikan satu slot jadwal (kelas, hari, jam, guru, mapel) hanya punya satu record di tanggal spesifik
            $table->unique([
                'tanggal_jadwal',
                'id_kelas',
                'id_hari',
                'id_jam',
                'id_guru',
                'id_mapel'
            ], 'unique_presensi_guru_per_jadwal_tgl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_presensi_guru');
    }
};
