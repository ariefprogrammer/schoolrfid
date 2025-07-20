<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TutupPresensiKeluar extends Command
{
    protected $signature = 'presensi:tutup-siswa-keluar';
    protected $description = 'Menambahkan data unassign untuk siswa yang tidak presensi keluar hari ini';

    public function handle()
    {
        $today = Carbon::today();

        // Cek apakah ada presensi masuk hari ini
        $adaPresensiMasuk = DB::table('tbl_absensi')
            ->whereDate('date_time', $today)
            ->where('jenis', 'masuk')
            ->whereIn('status', ['hadir', 'terlambat'])
            ->exists();

        if (!$adaPresensiMasuk) {
            $this->info("🚫 Tidak ditemukan presensi masuk pada hari ini. Kemungkinan sekolah libur, cron job tutup presensi keluar tidak dijalankan.");
            return Command::SUCCESS;
        }
        
        $count = 0;

        // Ambil semua siswa
        $siswas = DB::table('tbl_siswa')->get();

        foreach ($siswas as $siswa) {
            // Cek apakah siswa presensi masuk hari ini (bukan alpa)
            $presensiMasuk = DB::table('tbl_absensi')
                ->where('rfid', $siswa->rfid)
                ->whereDate('date_time', $today)
                ->where('jenis', 'masuk')
                ->whereIn('status', ['hadir', 'terlambat']) // abaikan alpa
                ->exists();

            // Cek apakah dia sudah presensi keluar
            $presensiKeluar = DB::table('tbl_absensi')
                ->where('rfid', $siswa->rfid)
                ->whereDate('date_time', $today)
                ->where('jenis', 'keluar')
                ->exists();

            if ($presensiMasuk && !$presensiKeluar) {
                // Jika presensi masuk tapi belum presensi keluar → tambahkan data unassign
                DB::table('tbl_absensi')->insert([
                    'rfid' => $siswa->rfid,
                    'id_kelas' => $siswa->id_kelas,
                    'jenis' => 'keluar',
                    'status' => 'unassign',
                    'keterangan' => 'Tanpa keterangan',
                    'date_time' => $today,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("✅ Presensi keluar ditutup. {$count} siswa berhasil dicatat sebagai 'unassign'.");
        } else {
            $this->info("✅ Tidak ada siswa yang perlu dicatat sebagai 'unassign'.");
        }
    }
}