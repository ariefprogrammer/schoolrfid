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
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->boolean('paid')->default(false)->after('tanggal_jadwal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_presensi_guru', function (Blueprint $table) {
            $table->dropColumn('paid');
        });
    }
};
