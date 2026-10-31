<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->unsignedInteger('durasi_terlambat')->default(0)->after('status_out');
            $table->unsignedInteger('durasi_bolos')->default(0)->after('durasi_terlambat');
            $table->unsignedInteger('upah_dasar')->default(0)->after('durasi_bolos');
            $table->unsignedInteger('potongan_terlambat')->default(0)->after('upah_dasar');
            $table->unsignedInteger('potongan_bolos')->default(0)->after('potongan_terlambat');
            $table->integer('total_upah')->default(0)->after('potongan_bolos');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->dropColumn([
                'durasi_terlambat', 'durasi_bolos', 'upah_dasar',
                'potongan_terlambat', 'potongan_bolos', 'total_upah',
            ]);
        });
    }
};
