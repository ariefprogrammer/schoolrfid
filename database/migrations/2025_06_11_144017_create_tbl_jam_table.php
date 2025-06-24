<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_jam', function (Blueprint $table) {
            $table->id();
            $table->integer('ke')->unique(); // Angka urutan jam pelajaran (misal: 1, 2, 3), harus unik
            $table->time('jam_mulai'); // Waktu mulai jam pelajaran
            $table->time('jam_selesai'); // Waktu selesai jam pelajaran
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_jam');
    }
};
