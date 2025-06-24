<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_absensi', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time'); // Tanggal dan waktu presensi
            $table->string('rfid'); // RFID siswa yang melakukan presensi
            $table->foreignId('id_kelas')->constrained('tbl_kelas')->cascadeOnDelete(); // Foreign key ke tbl_kelas
            $table->enum('jenis', ['masuk', 'keluar']); // Jenis presensi: masuk atau keluar
            $table->string('status'); // Status presensi: hadir, terlambat, dll.
            $table->string('keterangan')->nullable(); // Keterangan opsional
            $table->timestamps();

            // Tambahkan index untuk rfid dan date_time untuk mempercepat pencarian
            $table->index(['rfid', 'date_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_absensi');
    }
};
