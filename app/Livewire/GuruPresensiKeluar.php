<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\PresensiGuru;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Pengaturan;

class GuruPresensiKeluar extends Component
{
    public ?string $rfid = '';
    public ?Guru $guru = null;
    public $jadwalHariIni = []; // Akan berisi jadwal guru yang aktif (sudah masuk, belum keluar)
    public bool $showModal = false;

    public ?string $notificationTitle = null;
    public ?string $notificationBody = null;
    public ?string $notificationType = null; 

    public array $selectedSchedules = [];

    protected $rules = [
        'rfid' => 'required|string|max:255',
    ];

    protected function displayCustomNotification(string $title, string $body, string $type): void
    {
        $icon = match($type) {
            'success' => 'success',
            'danger' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'info',
        };

        $jsCode = "
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                icon: '{$icon}',
                title: " . json_encode($title) . ",
                text: " . json_encode($body) . "
            });
        ";
        $this->js($jsCode);
    }

    public function clearNotification(): void
    {
        $this->notificationTitle = null;
        $this->notificationBody = null;
        $this->notificationType = null;
    }

    public function showJadwal(): void
    {
        $this->validate();
        $this->clearNotification();

        $this->guru = null;
        $this->jadwalHariIni = [];
        $this->showModal = false;

        $guru = Guru::where('rfid', $this->rfid)->first();

        if (!$guru) {
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

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY)->toDateString();

        if (!$hariIni) {
            $this->displayCustomNotification(
                'Jadwal hari ini (' . $today . ') belum diatur di manajemen Hari.',
                'Silakan hubungi admin untuk mengatur jadwal Hari ini.',
                'warning'
            );
            return;
        }

        // --- PENTING: FILTER UNTUK PRESENSI KELUAR ---
        // Ambil jadwal dari tbl_presensi_guru yang sudah jam_in tapi belum jam_out
        $this->jadwalHariIni = PresensiGuru::with(['mapel', 'jam', 'kelas'])
                                        ->where('id_guru', $this->guru->id)
                                        ->where('id_hari', $hariIni->id)
                                        ->where('periode_awal', $startOfWeek)
                                        ->where('periode_akhir', $endOfWeek)
                                        ->where('tanggal_jadwal', Carbon::now()->toDateString())
                                        ->whereNotNull('jam_in') // Harus sudah masuk
                                        ->whereNull('jam_out')   // Belum keluar
                                        ->orderBy('id_jam')
                                        ->get();
        
        $this->selectedSchedules = []; 

        if ($this->jadwalHariIni->isEmpty()) {
            $this->displayCustomNotification(
                'Tidak ada jadwal aktif.',
                'Guru ini tidak memiliki jadwal yang sedang aktif (sudah presensi masuk tapi belum keluar) hari ini (' . $today . ').',
                'info'
            );
            return;
        }

        $this->showModal = true;

        // $this->displayCustomNotification(
        //     'Jadwal aktif berhasil dimuat.',
        //     'Jadwal aktif guru ' . $this->guru->nama_guru . ' hari ini telah ditampilkan.',
        //     'success'
        // );
    }

    public function updatedSelectedSchedules(): void
    {
        if (empty($this->selectedSchedules)) {
            $this->clearNotification();
            return;
        }

        $selectedSchedulesModels = $this->jadwalHariIni->filter(fn($schedule) => in_array($schedule->id, $this->selectedSchedules))
                                                        ->sortBy(fn($schedule) => $schedule->jam->ke);

        if ($selectedSchedulesModels->isEmpty()) {
             $this->selectedSchedules = [];
             $this->displayCustomNotification('Gagal Memilih Jadwal.', 'Pilihan jadwal tidak valid.', 'danger');
             return;
        }

        $firstClassId = $selectedSchedulesModels->first()->id_kelas;
        $firstClassName = ($selectedSchedulesModels->first()->kelas->kelas ?? '') . ' ' . ($selectedSchedulesModels->first()->kelas->nama_kelas ?? '-');
        $allSameClass = $selectedSchedulesModels->every(fn($schedule) => $schedule->id_kelas === $firstClassId);

        if (!$allSameClass) {
            $this->selectedSchedules = [];
            $this->displayCustomNotification('Gagal Memilih Jadwal.', 'Semua jadwal yang dipilih harus untuk kelas yang sama.', 'danger');
            return;
        }

        $isSequential = true;
        $previousJamOrder = null;
        foreach ($selectedSchedulesModels as $schedule) { // <-- Pastikan ini juga $selectedSchedulesModels
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

        // --- PENTING: Validasi khusus Presensi Keluar ---
        // Semua yang dipilih harus sudah presensi masuk dan belum presensi keluar
        $notYetClockedIn = $selectedSchedulesModels->first(fn($s) => is_null($s->jam_in)); // <-- Pastikan ini $selectedSchedulesModels
        $alreadyClockedOut = $selectedSchedulesModels->first(fn($s) => !is_null($s->jam_out)); // <-- Pastikan ini $selectedSchedulesModels

        if ($notYetClockedIn) {
            $this->selectedSchedules = [];
            $this->displayCustomNotification('Gagal Memilih.', 'Beberapa jadwal yang dipilih belum presensi masuk.', 'danger');
            return;
        }
        if ($alreadyClockedOut) {
            $this->selectedSchedules = [];
            $this->displayCustomNotification('Gagal Memilih.', 'Beberapa jadwal yang dipilih sudah presensi keluar.', 'danger');
            return;
        }

        // $this->displayCustomNotification('Jadwal dipilih.', 'Jadwal untuk kelas ' . $firstClassName . ' sudah valid untuk presensi keluar.', 'success');
    }

    // --- PENTING: Implementasi presensiKeluar() ---
    public function presensiKeluar(): void
    {
        $this->clearNotification();

        if (is_null($this->guru)) {
            $this->displayCustomNotification('Error.', 'Data guru tidak ditemukan. Mohon ulangi proses dari awal.', 'error');
            $this->showModal = false;
            $this->rfid = '';
            return;
        }

        if (empty($this->selectedSchedules)) {
            $this->displayCustomNotification('Peringatan.', 'Pilih setidaknya satu jadwal untuk presensi keluar.', 'warning');
            return;
        }

        $sortedSelected = $this->jadwalHariIni->filter(fn($schedule) => in_array($schedule->id, $this->selectedSchedules))
                                             ->sortBy(fn($schedule) => $schedule->jam->ke);
        
        // Re-validasi kelas sama dan jam berurutan
        if ($sortedSelected->isEmpty() || !$sortedSelected->every(fn($s) => $s->id_kelas === $sortedSelected->first()->id_kelas) || !$this->isSelectionSequential($sortedSelected)) {
            $this->displayCustomNotification('Gagal Presensi.', 'Pilihan jadwal tidak valid atau tidak berurutan/sama kelas.', 'danger');
            $this->selectedSchedules = [];
            return;
        }

        // Re-validate that all selected schedules are clocked in and NOT clocked out
        $notYetClockedIn = $sortedSelected->first(fn($s) => is_null($s->jam_in));
        $alreadyClockedOut = $sortedSelected->first(fn($s) => !is_null($s->jam_out));
        if ($notYetClockedIn || $alreadyClockedOut) {
            $this->displayCustomNotification('Gagal Presensi Keluar.', 'Beberapa jadwal belum presensi masuk atau sudah presensi keluar.', 'danger');
            return;
        }


        $firstSelectedSchedule = $sortedSelected->first();
        $firstClassName = ($firstSelectedSchedule->kelas->kelas ?? '') . ' ' . ($firstSelectedSchedule->kelas->nama_kelas ?? '-');

        try {
            $now = Carbon::now();
            $lastJamSelesai = Carbon::parse($sortedSelected->last()->jam->jam_selesai); // Ambil jam selesai dari jadwal terakhir yang dipilih
            $statusOut = ($now->greaterThanOrEqualTo($lastJamSelesai)) ? 'pulang' : 'bolos'; // Status berdasarkan jam selesai

            foreach ($sortedSelected as $scheduleToUpdate) {
                $scheduleToUpdate->update([
                    'jam_out' => $now,
                    'status_out' => $statusOut,
                ]);
            }

            // === Kirim Notifikasi Telegram Kepala Sekolah ===
            $pengaturan = \App\Models\Pengaturan::first();
            if ($pengaturan && $pengaturan->token_telegram && $pengaturan->telegram_kepsek) {
                $text = "📢 *Presensi Keluar Guru*\n"
                    . "Nama: *{$this->guru->nama_guru}*\n"
                    . "Kelas: *{$firstClassName}*\n"
                    . "Jam Keluar: " . $now->format('H:i') . "\n"
                    . "Status: *{$statusOut}*";

                try {
                    \Http::post("https://api.telegram.org/bot{$pengaturan->token_telegram}/sendMessage", [
                        'chat_id' => $pengaturan->telegram_kepsek,
                        'text' => $text,
                        'parse_mode' => 'Markdown'
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Gagal kirim notifikasi Telegram: " . $e->getMessage());
                }
            }

            $this->displayCustomNotification('Presensi Keluar Berhasil!', 'Anda telah berhasil presensi keluar untuk jadwal di kelas ' . $firstClassName . '.', 'success');
            
            $this->closeModal(); // Menutup modal dan mereset RFID input

        } catch (\Exception $e) {
            $this->displayCustomNotification('Terjadi Kesalahan.', 'Gagal menyimpan presensi keluar: ' . $e->getMessage(), 'danger');
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
        return view('livewire.guru-presensi-keluar'); // <-- PERHATIKAN INI: view untuk komponen ini
    }
}