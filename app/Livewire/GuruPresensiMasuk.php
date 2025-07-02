<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\PresensiGuru;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GuruPresensiMasuk extends Component
{
    public ?string $rfid = ''; // Untuk input RFID
    public ?Guru $guru = null; // Untuk menyimpan model Guru yang ditemukan
    public $jadwalHariIni = []; // Untuk menyimpan jadwal guru hari ini
    public bool $showModal = false; // Untuk mengontrol visibilitas modal

    public array $selectedSchedules = [];

    protected $rules = [
        'rfid' => 'required|string|max:255',
    ];

    protected function displayCustomNotification(string $title, string $body, string $type): void
    {
        // Peta tipe notifikasi kustom ke ikon SweetAlert2
        $icon = match($type) {
            'success' => 'success',
            'danger' => 'error',   // SweetAlert2 menggunakan 'error' untuk merah/bahaya
            'warning' => 'warning',
            'info' => 'info',
            default => 'info',
        };

        // Dispatch JavaScript untuk menampilkan SweetAlert2 Toast
        // Menggunakan json_encode untuk memastikan string yang lewat valid JS
        $jsCode = "
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000, // Menggunakan 5 detik untuk keterbacaan, Anda bisa sesuaikan ke 1500ms
                timerProgressBar: true,
                icon: '{$icon}',
                title: " . json_encode($title) . ", // Pastikan judul di-encode
                text: " . json_encode($body) . " // Pastikan body di-encode
            });
        ";
        $this->js($jsCode);
    }

    public function showJadwal(): void
    {
        $this->validate();
        // $this->clearNotification();

        $this->guru = null;
        $this->jadwalHariIni = [];
        $this->showModal = false;

        $guru = Guru::where('rfid', $this->rfid)->first();

        if (!$guru) {
            // --- PERBAIKAN DI SINI ---
            // Panggil metode kustom yang kita buat
            $this->displayCustomNotification(
                'RFID Guru tidak ditemukan.',
                'Mohon cek kembali RFID yang Anda masukkan.',
                'danger'
            );
            return;
        }

        $this->guru = $guru;

        Carbon::setLocale('id');
        $today = Carbon::now()->isoFormat('dddd');
        $hariIni = Hari::where('hari', $today)->first();

        // Hitung periode minggu ini (Senin - Sabtu)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY)->toDateString();

        if (!$hariIni) {
            // Panggil metode kustom yang kita buat
            $this->displayCustomNotification(
                'Jadwal hari ini (' . $today . ') belum diatur di manajemen Hari.',
                'Silakan hubungi admin untuk mengatur jadwal Hari ini.',
                'warning'
            );
            return;
        }

        // Ambil jadwal dari tbl_presensi_guru
        $this->jadwalHariIni = PresensiGuru::with(['mapel', 'jam', 'kelas'])
                                        ->where('id_guru', $this->guru->id)
                                        ->where('id_hari', $hariIni->id)
                                        ->whereNull('jam_in')
                                        // Filter berdasarkan periode minggu ini
                                        ->where('periode_awal', $startOfWeek)
                                        ->where('periode_akhir', $endOfWeek)
                                        // Filter untuk tanggal hari ini (tanggal_jadwal)
                                        ->where('tanggal_jadwal', Carbon::now()->toDateString())
                                        ->orderBy('id_jam')
                                        ->get();

        // Reset pilihan checkbox saat modal dibuka
        $this->selectedSchedules = [];

        // Jika tidak ada jadwal untuk hari ini, tampilkan notifikasi khusus
        if ($this->jadwalHariIni->isEmpty()) {
            $this->displayCustomNotification(
                'Tidak ada jadwal pelajaran.',
                'Guru ini tidak memiliki jadwal pelajaran hari ini (' . $today . ').',
                'info' 
            );
            return;
        }

        $this->showModal = true;

        // --- TAMBAHAN (jika Anda ingin notifikasi sukses saat jadwal tampil) ---
        // $this->displayCustomNotification(
        //     'Jadwal berhasil dimuat.',
        //     'Jadwal guru ' . $this->guru->nama_guru . ' hari ini telah ditampilkan.',
        //     'success'
        // );
        // --- AKHIR TAMBAHAN ---
    }

    public function updatedSelectedSchedules(): void
    {
        if (empty($this->selectedSchedules)) {
            return;
        }

        $selectedSchedulesModels = $this->jadwalHariIni->filter(fn($schedule) => in_array($schedule->id, $this->selectedSchedules))
                                                        ->sortBy(fn($schedule) => $schedule->jam->ke);

        if ($selectedSchedulesModels->isEmpty()) {
             $this->selectedSchedules = []; // Kosongkan pilihan jika filter gagal mendapatkan model
             $this->displayCustomNotification('Gagal Memilih Jadwal.', 'Pilihan jadwal tidak valid.', 'danger');
             return;
        }

        // --- Validasi 1: Semua jadwal yang dipilih harus untuk kelas yang sama ---
        $firstClassId = $selectedSchedulesModels->first()->id_kelas;
        $firstClassName = ($selectedSchedulesModels->first()->kelas->kelas ?? '') . ' ' . ($selectedSchedulesModels->first()->kelas->nama_kelas ?? '-');
        $allSameClass = $selectedSchedulesModels->every(fn($schedule) => $schedule->id_kelas === $firstClassId);

        if (!$allSameClass) {
            $this->selectedSchedules = [];
            $this->displayCustomNotification('Gagal Memilih Jadwal.', 'Semua jadwal yang dipilih harus untuk kelas yang sama.', 'danger');
            return;
        }

        // --- Validasi 2: Jam pelajaran harus berurutan ---
        $isSequential = true;
        $previousJamOrder = null;
        foreach ($selectedSchedulesModels as $schedule) {
            if (is_null($previousJamOrder)) {
                $previousJamOrder = $schedule->jam->ke;
                continue;
            }
            if ($schedule->jam->ke !== ($previousJamOrder + 1)) {
                $isSequential = false;
                break;
            }
            $previousJamOrder = $schedule->jam->ke;
        }

        if (!$isSequential) {
            $this->selectedSchedules = [];
            $this->displayCustomNotification('Gagal Memilih Jadwal.', 'Jadwal yang dipilih harus berurutan (misal: Jam 1, Jam 2, Jam 3).', 'danger');
            return;
        }

        // $this->displayCustomNotification('Jadwal dipilih.', 'Jadwal untuk kelas ' . $firstClassName . ' sudah valid.', 'success');
    }

    public function presensiMasuk(): void
    {

        if (is_null($this->guru)) {
            $this->displayCustomNotification('Error.', 'Data guru tidak ditemukan. Mohon ulangi proses dari awal.', 'danger');
            $this->showModal = false;
            $this->rfid = '';
            return;
        }

        // Re-validasi pilihan saat tombol submit diklik
        if (empty($this->selectedSchedules)) {
            $this->displayCustomNotification('Peringatan.', 'Pilih setidaknya satu jadwal untuk presensi masuk.', 'warning');
            return;
        }

        $sortedSelected = $this->jadwalHariIni->filter(fn($schedule) => in_array($schedule->id, $this->selectedSchedules))
                                             ->sortBy(fn($schedule) => $schedule->jam->ke);
        
        // Lakukan kembali validasi kelas sama dan jam berurutan secara cepat
        if ($sortedSelected->isEmpty() || !$sortedSelected->every(fn($s) => $s->id_kelas === $sortedSelected->first()->id_kelas) || !$this->isSelectionSequential($sortedSelected)) {
            $this->displayCustomNotification('Gagal Presensi.', 'Pilihan jadwal tidak valid atau tidak berurutan/sama kelas.', 'danger');
            $this->selectedSchedules = [];
            return;
        }

        $firstSelectedSchedule = $sortedSelected->first();
        $firstClassId = $firstSelectedSchedule->id_kelas;
        $firstClassName = ($firstSelectedSchedule->kelas->kelas ?? '') . ' ' . ($firstSelectedSchedule->kelas->nama_kelas ?? '-');

        // Validasi: Guru tidak boleh presensi masuk jika sudah aktif di kelas lain
        $activePresensiDiKelasLain = PresensiGuru::where('id_guru', $this->guru->id)
                                                  ->whereNotNull('jam_in')
                                                  ->whereNull('jam_out')
                                                  ->where('id_kelas', '!=', $firstClassId) // Di kelas yang berbeda
                                                  ->first();
        if ($activePresensiDiKelasLain) {
            $this->displayCustomNotification(
                'Gagal Presensi Masuk.',
                'Anda masih aktif presensi di kelas lain (' . ($activePresensiDiKelasLain->kelas->kelas ?? '') . ' ' . ($activePresensiDiKelasLain->kelas->nama_kelas ?? '-') . '). Harap presensi keluar terlebih dahulu.',
                'danger'
            );
            return;
        }
        
        // Validasi: Guru tidak boleh presensi masuk ulang di jadwal yang sama jika jam_in sudah terisi
        $alreadyClockedIn = $sortedSelected->first(fn($schedule) => !is_null($schedule->jam_in));
        if ($alreadyClockedIn) {
            $this->displayCustomNotification('Gagal Presensi Masuk.', 'Beberapa jadwal yang Anda pilih sudah presensi masuk.', 'danger');
            return;
        }


        // Lakukan update ke database
        try {
            $now = Carbon::now();
            $firstJamMulai = Carbon::parse($firstSelectedSchedule->jam->jam_mulai);
            $statusIn = ($now->lessThanOrEqualTo($firstJamMulai)) ? 'hadir' : 'terlambat';

            foreach ($sortedSelected as $scheduleToUpdate) {
                // Periksa sekali lagi untuk keamanan (tidak seharusnya terjadi jika validasi di atas benar)
                if (!is_null($scheduleToUpdate->jam_in)) {
                     $this->displayCustomNotification('Peringatan.', 'Jadwal ID ' . $scheduleToUpdate->id . ' sudah presensi masuk. Melewatkan.', 'warning');
                     continue;
                }

                $scheduleToUpdate->update([
                    'jam_in' => $now,
                    'status_in' => $statusIn,
                ]);
            }

            $this->displayCustomNotification('Presensi Masuk Berhasil!', 'Anda telah berhasil presensi masuk untuk jadwal di kelas ' . $firstClassName . '.', 'success');
            
            $this->closeModal();

        } catch (\Exception $e) {
            $this->displayCustomNotification('Terjadi Kesalahan.', 'Gagal menyimpan presensi: ' . $e->getMessage(), 'danger');
        }
    }

    private function isSelectionSequential(Collection $schedules): bool
    {
        if ($schedules->count() <= 1) {
            return true;
        }
        $isSequential = true;
        $previousJamOrder = null;
        foreach ($schedules as $index => $schedule) {
            if ($index === 0) {
                $previousJamOrder = $schedule->jam->ke;
                continue;
            }
            if ($schedule->jam->ke !== ($previousJamOrder + 1)) {
                $isSequential = false;
                break;
            }
            $previousJamOrder = $schedule->jam->ke;
        }
        return $isSequential;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->rfid = ''; 
        $this->js('$("#rfid_input").focus();');
    }

    public function render()
    {
        return view('livewire.guru-presensi-masuk');
    }
}