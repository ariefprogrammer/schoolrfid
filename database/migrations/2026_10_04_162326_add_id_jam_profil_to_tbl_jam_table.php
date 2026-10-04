<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_jam', function (Blueprint $table) {
            $table->foreignId('id_jam_profil')->nullable()->after('id')
                ->constrained('tbl_jam_profil')->cascadeOnDelete();
        });

        $defaultId = DB::table('tbl_jam_profil')->where('is_default', true)->value('id');
        DB::table('tbl_jam')->update(['id_jam_profil' => $defaultId]);

        Schema::table('tbl_jam', function (Blueprint $table) {
            $table->unsignedBigInteger('id_jam_profil')->nullable(false)->change();
            $table->unique(['id_jam_profil', 'ke']);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_jam', function (Blueprint $table) {
            $table->dropForeign(['id_jam_profil']);
            $table->dropUnique(['id_jam_profil', 'ke']);
            $table->dropColumn('id_jam_profil');
        });
    }
};