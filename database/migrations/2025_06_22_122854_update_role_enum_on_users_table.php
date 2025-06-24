<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- LANGKAH 1: Tangani Data yang Ada ---
        // Jika ada pengguna dengan role 'tendik', ubah mereka menjadi 'guru_piket'
        // (Atau pilih 'admin' sesuai kebutuhan bisnis Anda.
        // Jika Anda ingin menghapus pengguna 'tendik', gunakan DB::table('users')->where('role', 'tendik')->delete(); )
        DB::table('users')
            ->where('role', 'tendik')
            ->update(['role' => 'guru_piket']);

        // --- LANGKAH 2: Ubah Definisi Kolom ENUM ---
        // Menggunakan DB::statement lebih aman untuk mengubah ENUM di MySQL
        // Pastikan NOT NULL dan DEFAULT sesuai dengan definisi kolom sebelumnya.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'guru_piket') NOT NULL DEFAULT 'guru_piket'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // --- LANGKAH 1 (Opsional, untuk rollback data): ---
        // Jika Anda ingin mengubah 'guru_piket' kembali ke 'tendik' (jika memungkinkan)
        // Ini TIDAK akan mengembalikan pengguna yang awalnya 'tendik' secara spesifik,
        // karena kita tidak memiliki catatan siapa saja yang diubah.
        // Baris ini akan mengubah SEMUA 'guru_piket' menjadi 'tendik' saat rollback.
        // Jika ini tidak diinginkan, HAPUS BARIS INI.
        DB::table('users')
            ->where('role', 'guru_piket')
            ->update(['role' => 'tendik']); // HATI-HATI! Ini akan mengubah SEMUA 'guru_piket'

        // --- LANGKAH 2: Revert Definisi Kolom ENUM ---
        // Mengembalikan ENUM ke definisi sebelumnya
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'tendik', 'guru_piket') NOT NULL DEFAULT 'tendik'");
    }
};
