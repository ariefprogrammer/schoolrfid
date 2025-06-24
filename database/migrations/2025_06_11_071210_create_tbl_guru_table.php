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
        Schema::create('tbl_guru', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // Kode guru, unik
            $table->string('nip')->unique(); // NIP (Nomor Induk Pegawai), unik
            $table->string('nama_guru');
            $table->string('email')->unique(); // Email untuk login, unik
            $table->string('password'); // Password untuk login
            $table->string('rfid')->nullable()->unique(); // RFID guru, bisa kosong, harus unik
            $table->rememberToken(); // Untuk fitur "remember me"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_guru');
    }
};
