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
use App\Jobs\SendWhatsAppNotification;
use App\Services\HonorGuruService;
use App\Services\TunjanganGuruService;

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
        $this->jadwalHariIni = PresensiGuru::with(['mapel', 'jam', 'kelas', 'jadwal.kelases'])
                                        ->where('id_guru', $this->guru->id)
                                        ->where('id_hari', $hariIni->id)
                                        ->whereNull('jam_in')
                                        ->where(function ($q) {
                                            $q->whereNull('status_in')
                                            ->orWhereNotIn('status_in', ['izin', 'sakit', 'libur']);
                                        })
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
        $firstClassKey = $selectedSchedulesModels->first()->idKelasSesi();
        $firstClassName = $selectedSchedulesModels->first()->namaKelasTampil();
        $allSameClass = $selectedSchedulesModels->every(fn ($schedule) => $schedule->idKelasSesi() === $firstClassKey);

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
        if ($sortedSelected->isEmpty() || !$sortedSelected->every(fn($s) => $s->idKelasSesi() === $sortedSelected->first()->idKelasSesi()) || !$this->isSelectionSequential($sortedSelected)) {
            $this->displayCustomNotification('Gagal Presensi.', 'Pilihan jadwal tidak valid atau tidak berurutan/sama kelas.', 'danger');
            $this->selectedSchedules = [];
            return;
        }

        $firstSelectedSchedule = $sortedSelected->first();
        $firstClassKey = $firstSelectedSchedule->idKelasSesi();
        $firstClassName = $firstSelectedSchedule->namaKelasTampil();

        // Validasi: Guru tidak boleh presensi masuk jika masih aktif di sesi dengan kelas berbeda
        $activePresensiDiKelasLain = PresensiGuru::with(['kelas', 'jadwal.kelases'])
            ->where('id_guru', $this->guru->id)
            ->whereNotNull('jam_in')
            ->whereNull('jam_out')
            ->get()
            ->first(fn ($p) => $p->idKelasSesi() !== $firstClassKey);

        if ($activePresensiDiKelasLain) {
            $this->displayCustomNotification(
                'Gagal Presensi Masuk.',
                'Anda masih aktif presensi di kelas lain (' . $activePresensiDiKelasLain->namaKelasTampil() . '). Harap presensi keluar terlebih dahulu.',
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

        // Tolak bila semua jam pelajaran yang dipilih sudah berakhir
        $service = app(HonorGuruService::class);
        if (! $service->adaJamDiikuti($sortedSelected, Carbon::now())) {
            $this->displayCustomNotification('Gagal Presensi Masuk.', 'Jam pelajaran yang dipilih sudah berakhir. Silakan hubungi admin.', 'danger');
            return;
        }

        // Lakukan update ke database
        try {
            $now = Carbon::now();

            // Status, durasi terlambat, dan potongan dihitung & disimpan oleh service
            $statusIn = $service->catatMasuk($sortedSelected, $now);

            // Catat kehadiran harian & tunjangan harian (sekali per hari; tap berikutnya dilewati)
            try {
                app(TunjanganGuruService::class)->sinkronHarian((int) $this->guru->id, $now->toDateString());
            } catch (\Throwable $e) {
                \Log::error('Gagal sinkron tunjangan harian: ' . $e->getMessage());
            }

            // Notifikasi ke Telegram Kepala Sekolah & Guru
            try {
                $pengaturan = Pengaturan::first();
                if ($pengaturan && $pengaturan->token_telegram) {
                    $pesan = "📢 *Presensi Masuk Guru*\n"
                        . "Nama: *{$this->guru->nama_guru}*\n"
                        . "Kelas: *{$firstClassName}*\n"
                        . "Waktu: " . $now->format('H:i:s') . "\n"
                        . "Status: *" . ucfirst($statusIn) . "*";

                    if (!empty($pengaturan->telegram_kepsek)) {
                        $urlKepsek = "https://api.telegram.org/bot{$pengaturan->token_telegram}/sendMessage"
                            . "?chat_id={$pengaturan->telegram_kepsek}"
                            . "&text=" . urlencode($pesan)
                            . "&parse_mode=Markdown";
                        @file_get_contents($urlKepsek);
                    }

                    if (!empty($this->guru->telegram_chat_id)) {
                        $urlGuru = "https://api.telegram.org/bot{$pengaturan->token_telegram}/sendMessage"
                            . "?chat_id={$this->guru->telegram_chat_id}"
                            . "&text=" . urlencode($pesan)
                            . "&parse_mode=Markdown";
                        @file_get_contents($urlGuru);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim notifikasi Telegram: ' . $e->getMessage());
            }

            // Notifikasi WhatsApp
            try {
                $pesanWa = "📢 Presensi Masuk Guru\n"
                    . "Nama: {$this->guru->nama_guru}\n"
                    . "Kelas: {$firstClassName}\n"
                    . "Waktu: " . $now->format('H:i:s') . "\n"
                    . "Status: " . ucfirst($statusIn);

                if (!empty($this->guru->no_wa)) {
                    SendWhatsAppNotification::dispatch($this->guru->no_wa, $pesanWa);
                }

                $pengaturan = Pengaturan::first();
                if (!empty($pengaturan?->wa_kepsek)) {
                    SendWhatsAppNotification::dispatch($pengaturan->wa_kepsek, $pesanWa);
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim notifikasi WhatsApp: ' . $e->getMessage());
            }

            $this->displayCustomNotification('Presensi Masuk Berhasil!', 'Anda telah berhasil presensi masuk untuk jadwal di kelas ' . $firstClassName . '.', 'success');

            $this->closeModal();

        } catch (\Exception $e) {
            $this->displayCustomNotification('Terjadi Kesalahan.', 'Gagal menyimpan presensi: ' . $e->getMessage(), 'danger');
        }
    }

    private function isSelectionSequential(Collection $schedules): bool
    {
        $previousJamOrder = null;

        foreach ($schedules->values() as $schedule) {
            if (!is_null($previousJamOrder) && $schedule->jam->ke !== ($previousJamOrder + 1)) {
                return false;
            }
            $previousJamOrder = $schedule->jam->ke;
        }

        return true;
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