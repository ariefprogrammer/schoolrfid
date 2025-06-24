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
        // Menghapus indeks unik dari kolom 'kelas'
        Schema::table('tbl_kelas', function (Blueprint $table) {
            // Pastikan nama indeksnya benar. Laravel secara default menamainya 'nama_tabel_nama_kolom_unique'
            // Anda mungkin perlu memeriksa database Anda atau log migrasi sebelumnya untuk nama indeks yang tepat.
            // Jika Anda menggunakan MySQL/PostgreSQL, Anda bisa mencoba 'tbl_kelas_kelas_unique'
            $table->dropUnique(['kelas']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Menambahkan kembali indeks unik jika migrasi di-rollback
        Schema::table('tbl_kelas', function (Blueprint $table) {
            $table->string('kelas')->unique()->change();
        });
    }
};
