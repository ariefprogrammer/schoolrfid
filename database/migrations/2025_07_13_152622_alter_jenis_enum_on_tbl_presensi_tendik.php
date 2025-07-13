<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: ubah enum menjadi yang baru
        DB::statement("ALTER TABLE tbl_presensi_tendik MODIFY jenis ENUM('masuk', 'keluar', 'izin') NOT NULL");
    }

    public function down(): void
    {
        // Kembalikan ke enum lama
        DB::statement("ALTER TABLE tbl_presensi_tendik MODIFY jenis ENUM('masuk', 'keluar') NOT NULL");
    }
};
