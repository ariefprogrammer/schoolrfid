<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_jenis_tunjangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('tipe', 10);                       // bulanan | harian
            $table->unsignedInteger('nominal')->default(0);   // nominal standar
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_tunjangan_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_guru')->constrained('tbl_guru')->cascadeOnDelete();
            $table->foreignId('id_jenis')->constrained('tbl_jenis_tunjangan')->cascadeOnDelete();
            $table->unsignedInteger('nominal')->nullable();   // kosong = pakai nominal standar
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_kehadiran_harian_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_guru')->constrained('tbl_guru')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 10)->default('hadir');    // hadir | batal
            $table->string('sumber', 10)->default('otomatis'); // otomatis | manual
            $table->dateTime('jam_datang')->nullable();
            $table->text('alasan')->nullable();
            $table->unsignedBigInteger('diinput_oleh')->nullable();
            $table->timestamps();

            $table->unique(['id_guru', 'tanggal']);
        });

        // Buku tunjangan. Tanpa foreign key ke guru agar riwayat pembayaran tidak ikut terhapus.
        Schema::create('tbl_tunjangan_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_guru')->index();
            $table->unsignedBigInteger('id_jenis')->index();
            $table->date('periode')->index();   // tanggal 1 pada bulan terkait
            $table->date('tanggal');            // harian: tanggal hadir; bulanan: sama dengan periode
            $table->unsignedInteger('nominal'); // nilai beku saat dihitung
            $table->boolean('paid')->default(false);
            $table->boolean('disesuaikan')->default(false); // true = nominal diubah admin, sinkron tidak menimpa
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['id_guru', 'id_jenis', 'tanggal'], 'uniq_tunjangan_transaksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tunjangan_transaksi');
        Schema::dropIfExists('tbl_kehadiran_harian_guru');
        Schema::dropIfExists('tbl_tunjangan_guru');
        Schema::dropIfExists('tbl_jenis_tunjangan');
    }
};