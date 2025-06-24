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
        Schema::create('tbl_siswa', function (Blueprint $table) {
            $table->id();
            $table->string('rfid')->nullable()->unique(); // Kolom RFID, bisa kosong, harus unik
            $table->string('nis')->unique(); // NIS (Nomor Induk Siswa), harus unik
            $table->string('nama_siswa');
            $table->foreignId('id_kelas')->constrained('tbl_kelas')->cascadeOnDelete(); // Foreign key ke tbl_kelas
            $table->string('telepon_siswa')->nullable();
            $table->string('nama_wali')->nullable();
            $table->string('telepon_wali')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_siswa');
    }
};
