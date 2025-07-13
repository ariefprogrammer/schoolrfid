<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_jadwal_tendik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tendik_id')->constrained('tbl_tendik')->onDelete('cascade');
            $table->foreignId('hari_id')->constrained('tbl_hari')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tendik_id', 'hari_id']); // supaya kombinasi unik
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_jadwal_tendik');
    }
};
