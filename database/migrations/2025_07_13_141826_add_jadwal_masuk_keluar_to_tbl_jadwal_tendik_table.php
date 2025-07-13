<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_jadwal_tendik', function (Blueprint $table) {
            $table->time('jadwal_masuk')->nullable()->after('hari_id');
            $table->time('jadwal_keluar')->nullable()->after('jadwal_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_jadwal_tendik', function (Blueprint $table) {
            $table->dropColumn(['jadwal_masuk', 'jadwal_keluar']);
        });
    }
};
