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

        // Ambil semua sesi dari tbl_jadwal, eager load relasi 'hari' dan 'kelases'
        // (kelases = kelas-kelas yang mengikuti sesi, lewat pivot tbl_jadwal_kelas)
        $schedules = Jadwal::with(['hari', 'kelases'])->get();

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

                // Sesi tanpa kelas tidak bisa dibuatkan presensi (kolom id_kelas di presensi memerlukan kelas)
                if ($schedule->kelases->isEmpty()) {
                    $this->warn("Melewatkan Jadwal ID {$schedule->id}: sesi ini tidak punya kelas.");
                    continue;
                }

                $kelasSesi = $schedule->kelases->sortBy('id')->values();
                $labelKelas = $kelasSesi
                    ->map(fn ($k) => trim($k->kelas . ' ' . $k->nama_kelas))
                    ->join(' + ');

                // Satu record presensi per SESI per tanggal
                $existingPresensi = PresensiGuru::where('tanggal_jadwal', $tanggalJadwal->toDateString())
                    ->where('id_jadwal', $schedule->id)
                    ->first();

                if ($existingPresensi) {
                    $this->comment("Melewatkan (sudah ada): Jadwal ID {$schedule->id} pada {$tanggalJadwal->toDateString()}");
                    continue;
                }

                PresensiGuru::create([
                    'periode_awal' => $startOfWeek->toDateString(),
                    'periode_akhir' => $endOfWeek->toDateString(),
                    'id_jadwal' => $schedule->id,
                    'id_kelas' => $kelasSesi->first()->id, // data bantu: kelas pertama sesi
                    'label_kelas' => $labelKelas,
                    'id_hari' => $schedule->id_hari,
                    'id_jam' => $schedule->id_jam,
                    'id_guru' => $schedule->id_guru,
                    'id_mapel' => $schedule->id_mapel,
                    'tanggal_jadwal' => $tanggalJadwal->toDateString(),
                    'status_in' => 'unassign',
                    'status_out' => 'unassign',
                ]);

                $this->info("Berhasil membuat: Jadwal ID {$schedule->id} ({$labelKelas}) untuk {$scheduleDayName} pada {$tanggalJadwal->toDateString()}");
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