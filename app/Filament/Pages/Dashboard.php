<?php

namespace App\Filament\Pages;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Absensi; // Import model Absensi
use Filament\Pages\Page;
use Filament\Notifications\Notification; // Opsional, untuk notifikasi jika ada error
use Carbon\Carbon; // Import Carbon untuk tanggal
use Illuminate\Support\Facades\DB; // Import DB untuk raw queries
use App\Models\PresensiGuru;

class Dashboard extends Page
{
    // Konfigurasi Halaman Filament
    protected static ?string $navigationIcon = 'heroicon-o-home'; // Icon untuk navigasi sidebar
    protected static ?string $navigationGroup = 'Dashboard'; // Kelompok navigasi (opsional)
    protected static ?string $navigationLabel = 'Dashboard'; // Label di sidebar
    protected static ?string $title = 'Ringkasan Data'; // Judul halaman
    protected static string $view = 'filament.pages.dashboard'; // View Blade yang akan digunakan
    protected static ?int $navigationSort = 0; // Urutan di navigasi sidebar

    // Properti untuk menyimpan jumlah data
    public int $totalSiswa = 0;
    public int $totalGuru = 0;
    public int $totalKelas = 0;

    // Properti untuk data grafik traffic Presensi MASUK
    public array $trafficChartLabels = [];
    public array $trafficChartData = [];

    // Properti untuk data grafik traffic Presensi KELUAR
    public array $trafficChartOutLabels = [];
    public array $trafficChartOutData = [];

    // Properti untuk data Pie Chart Status Kehadiran Hari Ini
    public array $statusPieChartLabels = [];
    public array $statusPieChartData = [];
    public array $statusPieChartColors = [];

    // Properti untuk data Jadwal Guru Hari Ini
    public array $todaySchedules = [];

    

    /**
     * Metode yang dipanggil saat komponen diinisialisasi.
     * Digunakan untuk memuat data.
     */
    public function mount(): void
    {
        try {
            $this->totalSiswa = Siswa::count();
            $this->totalGuru = Guru::count();
            $this->totalKelas = Kelas::count();

            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth(); // Tetap ada untuk reference
            $endOfMonth = Carbon::now()->endOfMonth();     // Tetap ada untuk reference

            // --- LOGIKA UNTUK GRAFIK TRAFFIC KEHADIRAN SISWA (MASUK) ---
            $trafficInData = Absensi::query()
                ->select(
                    DB::raw("DATE_FORMAT(date_time, '%H:%i') as time_minute"), // Menggunakan date_time
                    DB::raw('COUNT(*) as count') // Hitung jumlah presensi
                )
                ->whereDate('date_time', $today) // Filter untuk hari ini menggunakan date_time
                ->whereNotNull('date_time') // Pastikan ada data jam masuk di date_time
                ->where('jenis', 'masuk') // Hanya hitung presensi jenis 'masuk'
                ->groupBy('time_minute') // Kelompokkan berdasarkan menit
                ->orderBy('time_minute') // Urutkan berdasarkan waktu
                ->get();

            $inLabels = [];
            $inDataCounts = [];

            if ($trafficInData->isNotEmpty()) {
                foreach ($trafficInData as $item) {
                    $inLabels[] = $item->time_minute;
                    $inDataCounts[] = $item->count;
                }
            }
            $this->trafficChartLabels = (array) $inLabels;
            $this->trafficChartData = (array) $inDataCounts;
            // --- AKHIR LOGIKA PRESENSI MASUK ---


            // --- LOGIKA UNTUK GRAFIK TRAFFIC KEHADIRAN SISWA (KELUAR) ---
            $trafficOutData = Absensi::query()
                ->select(
                    DB::raw("DATE_FORMAT(date_time, '%H:%i') as time_minute"), // Menggunakan date_time
                    DB::raw('COUNT(*) as count') // Hitung jumlah presensi
                )
                ->whereDate('date_time', $today) // Filter untuk hari ini menggunakan date_time
                ->whereNotNull('date_time') // Pastikan ada data jam masuk di date_time
                ->where('jenis', 'keluar') // HANYA hitung presensi jenis 'keluar'
                ->groupBy('time_minute') // Kelompokkan berdasarkan menit
                ->orderBy('time_minute') // Urutkan berdasarkan waktu
                ->get();

            $outLabels = [];
            $outDataCounts = [];

            if ($trafficOutData->isNotEmpty()) {
                foreach ($trafficOutData as $item) {
                    $outLabels[] = $item->time_minute;
                    $outDataCounts[] = $item->count;
                }
            }
            $this->trafficChartOutLabels = (array) $outLabels;
            $this->trafficChartOutData = (array) $outDataCounts;
            // --- AKHIR LOGIKA PRESENSI KELUAR ---

            // --- LOGIKA UNTUK PIE CHART STATUS KEHADIRAN ---
            $allStatuses = ['hadir', 'terlambat', 'alpa', 'pulang', 'bolos', 'unassign', 'izin', 'sakit'];
            $statusCounts = Absensi::query()
                ->select('status', DB::raw('COUNT(*) as count'))
                ->whereDate('date_time', $today)
                ->whereIn('status', $allStatuses) // Pastikan hanya status yang relevan
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $pieLabels = [];
            $pieData = [];
            $pieColors = [];

            // Definisikan warna untuk setiap status
            $colorMap = [
                'hadir'     => 'rgba(34, 197, 94, 0.7)',  // Hijau
                'terlambat' => 'rgba(250, 204, 21, 0.7)', // Kuning
                'alpa'      => 'rgba(239, 68, 68, 0.7)',  // Merah
                'pulang'    => 'rgba(99, 102, 241, 0.7)', // Indigo/Biru
                'bolos'     => 'rgba(244, 63, 94, 0.7)',  // Merah Muda
                'unassign'  => 'rgba(107, 114, 128, 0.7)',// Abu-abu
                'izin'      => 'rgba(59, 130, 246, 0.7)', // Biru terang
                'sakit'     => 'rgba(139, 92, 246, 0.7)', // Ungu
            ];

            foreach ($allStatuses as $status) {
                $count = $statusCounts[$status] ?? 0; // Ambil hitungan atau 0 jika tidak ada
                if ($count > 0) { // Hanya tampilkan status yang memiliki data
                    $pieLabels[] = ucfirst($status); // Ubah 'hadir' menjadi 'Hadir'
                    $pieData[] = $count;
                    $pieColors[] = $colorMap[$status];
                }
            }
            $this->statusPieChartLabels = (array) $pieLabels;
            $this->statusPieChartData = (array) $pieData;
            $this->statusPieChartColors = (array) $pieColors;
            // --- AKHIR LOGIKA PIE CHART HARI INI ---

            // --- LOGIKA UNTUK JADWAL GURU HARI INI ---
            $this->todaySchedules = PresensiGuru::query()
                ->whereDate('tanggal_jadwal', $today)
                ->with(['kelas', 'guru', 'jam', 'mapel']) // Eager load relasi yang dibutuhkan
                ->orderBy('id_kelas')
                ->orderBy('id_jam')
                ->get()
                ->groupBy('kelas.nama_kelas') // Kelompokkan berdasarkan nama kelas
                ->toArray(); // Ubah ke array untuk diteruskan ke Blade
            // --- AKHIR LOGIKA JADWAL GURU HARI INI ---

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal memuat data ringkasan atau grafik.')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->totalSiswa = 0;
            $this->totalGuru = 0;
            $this->totalKelas = 0;
            $this->trafficChartLabels = [];
            $this->trafficChartData = [];

            $this->trafficChartOutLabels = []; // Reset juga data keluar
            $this->trafficChartOutData = [];   // Reset juga data keluar

            $this->statusPieChartLabels = []; // Reset juga data pie chart
            $this->statusPieChartData = [];   // Reset juga data pie chart
            $this->statusPieChartColors = []; // Reset juga warna pie chart

            $this->todaySchedules = []; // Reset jadwal guru hari ini
        }
    }
}
