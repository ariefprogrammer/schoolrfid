<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_presensi_tendik', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->string('rfid');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->string('status')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_presensi_tendik');
    }
};
