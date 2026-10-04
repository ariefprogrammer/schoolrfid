<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_hari', function (Blueprint $table) {
            $table->foreignId('id_jam_profil')->nullable()->after('order')
                ->constrained('tbl_jam_profil')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_hari', function (Blueprint $table) {
            $table->dropForeign(['id_jam_profil']);
            $table->dropColumn('id_jam_profil');
        });
    }
};