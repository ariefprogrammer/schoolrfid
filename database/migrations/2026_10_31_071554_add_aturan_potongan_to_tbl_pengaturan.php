<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pengaturan', function (Blueprint $table) {
            // Aturan keterlambatan
            $table->unsignedInteger('batas_terlambat')->default(0)->after('upah_perjam');
            $table->unsignedInteger('potongan_terlambat')->default(0)->after('batas_terlambat');
            $table->unsignedInteger('durasi_potongan_terlambat')->default(15)->after('potongan_terlambat');
            $table->unsignedInteger('maks_potongan_terlambat')->default(0)->after('durasi_potongan_terlambat');

            // Aturan bolos
            $table->unsignedInteger('batas_bolos')->default(0)->after('maks_potongan_terlambat');
            $table->unsignedInteger('potongan_bolos')->default(0)->after('batas_bolos');
            $table->unsignedInteger('durasi_potongan_bolos')->default(15)->after('potongan_bolos');
            $table->unsignedInteger('maks_potongan_bolos')->default(0)->after('durasi_potongan_bolos');

            // Aturan izin (persen dari honor)
            $table->unsignedTinyInteger('izin_dengan_tugas')->default(100)->after('maks_potongan_bolos');
            $table->unsignedTinyInteger('izin_tanpa_tugas')->default(0)->after('izin_dengan_tugas');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pengaturan', function (Blueprint $table) {
            $table->dropColumn([
                'batas_terlambat',
                'potongan_terlambat',
                'durasi_potongan_terlambat',
                'maks_potongan_terlambat',
                'batas_bolos',
                'potongan_bolos',
                'durasi_potongan_bolos',
                'maks_potongan_bolos',
                'izin_dengan_tugas',
                'izin_tanpa_tugas',
            ]);
        });
    }
};