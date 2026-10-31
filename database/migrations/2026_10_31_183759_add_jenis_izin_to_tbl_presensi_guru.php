<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->string('jenis_izin', 20)->nullable()->after('total_upah');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->dropColumn('jenis_izin');
        });
    }
};
