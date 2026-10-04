<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_jam_profil', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        DB::table('tbl_jam_profil')->insert([
            'nama' => 'Reguler',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_jam_profil');
    }
};