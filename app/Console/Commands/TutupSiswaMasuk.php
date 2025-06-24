<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TutupSiswaMasuk extends Command
{
    protected $signature = 'presensi:tutup-siswa-masuk';
    protected $description = 'Menambahkan data alpa untuk siswa yang tidak presensi masuk hari ini';

    public function handle()
    {
        $today = Carbon::today();

        // Ambil semua siswa
        $siswas = DB::table('tbl_siswa')->get();

        foreach ($siswas as $siswa) {
            // Cek apakah siswa sudah absen hari ini
            $sudahAbsen = DB::table('tbl_absensi')
                ->where('rfid', $siswa->rfid)
                ->whereDate('date_time', $today)
                ->exists();

            if (!$sudahAbsen) {
                // Jika belum, tambahkan data alpa
                DB::table('tbl_absensi')->insert([
                    'rfid' => $siswa->rfid,
                    'id_kelas' => $siswa->id_kelas,
                    'jenis' => 'masuk',
                    'status' => 'alpa',
                    'keterangan' => 'Tanpa keterangan',
                    'date_time' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info("Presensi siswa ditutup. Semua siswa yang belum presensi hari ini telah dicatat sebagai Alpa.");
    }
}