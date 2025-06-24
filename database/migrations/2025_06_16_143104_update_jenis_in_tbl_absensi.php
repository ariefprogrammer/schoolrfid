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
        Schema::table('tbl_absensi', function (Blueprint $table) {
            // Ubah kolom jenis dari enum lama ke enum baru
            $table->enum('jenis', ['masuk', 'keluar', 'izin'])->default('masuk')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_absensi', function (Blueprint $table) {
            // Kembali ke enum sebelumnya
            $table->enum('jenis', ['masuk', 'keluar'])->default('masuk')->change();
        });
    }
};
