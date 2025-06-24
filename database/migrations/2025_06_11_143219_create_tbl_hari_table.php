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
        Schema::create('tbl_hari', function (Blueprint $table) {
            $table->id();
            $table->string('hari')->unique(); // Nama hari (misal: Senin), harus unik
            $table->integer('order')->unique(); // Urutan hari (misal: 1 untuk Senin, 2 untuk Selasa), harus unik
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_hari');
    }
};
