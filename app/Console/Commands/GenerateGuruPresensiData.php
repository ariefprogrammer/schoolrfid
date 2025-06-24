<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jadwal;       // Import model Jadwal
use App\Models\PresensiGuru; // Import model PresensiGuru
use App\Models\Hari;         // Import model Hari (untuk mendapatkan nama hari)
use Carbon\Carbon;           // Untuk bekerja dengan tanggal dan waktu
use Illuminate\Support\Facades\DB; // Untuk transaksi database

class GenerateGuruPresensiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:generate-guru-weekly'; // Nama unik untuk command ini

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly teacher presence records from tbl_jadwal.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai generasi data presensi guru mingguan...');

        // Atur locale Carbon ke Bahasa Indonesia untuk mendapatkan nama hari yang benar
        Carbon::setLocale('id');

        // Hitung tanggal Senin (periode_awal) dan Sabtu (periode_akhir) untuk minggu ini
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        $this->info("Menggenerasi data untuk minggu: {$startOfWeek->toDateString()} sampai {$endOfWeek->toDateString()}");

        // Ambil semua jadwal dari tbl_jadwal, eager load relasi 'hari'
        $schedules = Jadwal::with('hari')->get();

        DB::beginTransaction(); // Memulai transaksi database untuk memastikan atomicity operasi
        try {
            foreach ($schedules as $schedule) {
                // Dapatkan nama hari dari jadwal (misal: 'Senin', 'Selasa')
                $scheduleDayName = $schedule->hari->hari;

                // Hitung tanggal spesifik untuk hari ini dalam minggu berjalan
                // Carbon::startOfWeek(Carbon::MONDAY) sudah membuat tanggal awal sebagai hari Senin
                $tanggalJadwal = $startOfWeek->copy(); // Salin tanggal Senin awal minggu

                // Tambahkan hari sesuai dengan nama hari jadwal
                // 'Senin' = 0 hari, 'Selasa' = 1 hari, dst.
                switch ($scheduleDayName) {
                    case 'Senin': $tanggalJadwal->addDays(0); break;
                    case 'Selasa': $tanggalJadwal->addDays(1); break;
                    case 'Rabu': $tanggalJadwal->addDays(2); break;
                    case 'Kamis': $tanggalJadwal->addDays(3); break;
                    case 'Jumat': $tanggalJadwal->addDays(4); break;
                    case 'Sabtu': $tanggalJadwal->addDays(5); break;
                    default:
                        // Jika ada nama hari yang tidak ditangani (misal: 'Minggu' jika tidak termasuk)
                        $this->warn("Melewatkan jadwal untuk hari yang tidak dikenali/ditangani: {$scheduleDayName}");
                        continue 2; // Lanjutkan ke item $schedule berikutnya
                }

                // Pastikan tanggal jadwal yang dihitung berada dalam rentang Senin-Sabtu minggu ini
                if (!$tanggalJadwal->between($startOfWeek, $endOfWeek, true)) {
                    $this->warn("Melewatkan jadwal untuk {$scheduleDayName} karena berada di luar rentang generasi minggu ini.");
                    continue;
                }

                // Cek apakah record presensi untuk slot jadwal dan tanggal spesifik ini sudah ada
                // Ini penting untuk mencegah duplikasi jika cron job dijalankan beberapa kali secara tidak sengaja dalam seminggu.
                $existingPresensi = PresensiGuru::where('tanggal_jadwal', $tanggalJadwal->toDateString())
                                                ->where('id_kelas', $schedule->id_kelas)
                                                ->where('id_hari', $schedule->id_hari)
                                                ->where('id_jam', $schedule->id_jam)
                                                ->where('id_guru', $schedule->id_guru)
                                                ->where('id_mapel', $schedule->id_mapel)
                                                ->first();

                if ($existingPresensi) {
                    $this->comment("Melewatkan (sudah ada): Jadwal ID {$schedule->id} pada {$tanggalJadwal->toDateString()}");
                    // Opsi: Anda bisa memperbarui record yang ada di sini jika diperlukan (misal: memperbarui periode_awal/akhir)
                } else {
                    // Buat record presensi baru
                    PresensiGuru::create([
                        'periode_awal' => $startOfWeek->toDateString(),
                        'periode_akhir' => $endOfWeek->toDateString(),
                        'id_kelas' => $schedule->id_kelas,
                        'id_hari' => $schedule->id_hari,
                        'id_jam' => $schedule->id_jam,
                        'id_guru' => $schedule->id_guru,
                        'id_mapel' => $schedule->id_mapel,
                        'tanggal_jadwal' => $tanggalJadwal->toDateString(),
                        'status_in' => 'unassign',  // Diisi 'unassign' seperti permintaan
                        'status_out' => 'unassign', // Diisi 'unassign' seperti permintaan
                        // jam_in dan jam_out adalah nullable, jadi akan null secara default
                    ]);
                    $this->info("Berhasil membuat: Jadwal ID {$schedule->id} untuk {$scheduleDayName} pada {$tanggalJadwal->toDateString()}");
                }
            }
            DB::commit(); // Commit transaksi jika semua operasi berhasil
            $this->info('Generasi data presensi guru mingguan selesai.');
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaksi jika terjadi error
            $this->error('Terjadi error selama generasi: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString()); // Log full trace untuk debugging
        }
    }
}