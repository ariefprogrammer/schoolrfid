<?php

namespace App\Livewire;

use App\Models\Hari;
use App\Models\Jam;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;
// use App\Models\Jadwal; // TIDAK DIGUNAKAN LAGI UNTUK LOAD SCHEDULE
use App\Models\PresensiGuru; // Penting: Pastikan ini di-import
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class TodayScheduleMatrix extends Component
{
    public Collection $haris;
    public Collection $jams;
    public Collection $gurus;
    public Collection $mapels;
    public Collection $kelases;

    public array $jadwalDataPerKelas = [];
    public ?int $todayHariId = null; // ID hari untuk hari ini

    // Properti untuk modal (pertahankan untuk fitur selanjutnya)
    public bool $showDetailsModal = false;
    public ?array $selectedJadwalDetails = null;

    public function mount(): void
    {
        // Mendapatkan hari ini dalam bahasa Indonesia
        $dayName = Carbon::now()->isoFormat('dddd');

        // Cari ID hari ini di database
        $hariToday = Hari::where('hari', $dayName)->first();

        if ($hariToday) {
            $this->todayHariId = $hariToday->id;
        } else {
            $this->todayHariId = null;
        }

        // Muat semua data master yang dibutuhkan
        $this->haris = Hari::orderBy('order')->get();
        $this->jams = Jam::orderBy('ke')->get();
        $this->gurus = Guru::all();
        $this->mapels = Mapel::all();
        $this->kelases = Kelas::all();

        // Load jadwal jika hari ini ditemukan
        if ($this->todayHariId) {
            $this->loadSchedule();
        }
    }

    public function loadSchedule(): void
    {
        if (!$this->todayHariId) {
            $this->jadwalDataPerKelas = [];
            return;
        }

        // Ambil data presensi guru untuk hari ini
        // Eager load relasi langsung dari PresensiGuru
        $presensiHariIni = PresensiGuru::whereDate('tanggal_jadwal', Carbon::today())
                                        // Filter berdasarkan hari ini jika kolom id_hari ada di PresensiGuru
                                        // Jika tidak ada id_hari di PresensiGuru, ini bisa dihapus
                                        ->where('id_hari', $this->todayHariId)
                                        ->with(['kelas', 'jam', 'mapel', 'guru']) // Eager load relasi langsung
                                        ->get();

        $this->jadwalDataPerKelas = [];

        // Iterasi melalui semua kelas yang ada (dari data master)
        foreach ($this->kelases as $kelas) {
            $this->jadwalDataPerKelas[$kelas->id] = [];
            // Filter presensi yang sudah diambil berdasarkan id_kelas
            $kelasPresensi = $presensiHariIni->where('id_kelas', $kelas->id)->keyBy('id_jam');

            foreach ($this->jams as $jam) {
                $this->jadwalDataPerKelas[$kelas->id][$jam->id] = [
                    'id_guru' => null,
                    'id_mapel' => null,
                    'jadwal_id' => null, // Ini akan tetap null atau disesuaikan jika id_jadwal ada di PresensiGuru
                    'presensi_guru_id' => null,
                ];

                if ($kelasPresensi->has($jam->id)) {
                    $presensi = $kelasPresensi->get($jam->id);
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['id_guru'] = $presensi->id_guru;
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['id_mapel'] = $presensi->id_mapel;
                    // Jika Anda ingin menyimpan id_jadwal dari PresensiGuru, pastikan kolomnya ada
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['jadwal_id'] = $presensi->id_jadwal ?? null;
                    $this->jadwalDataPerKelas[$kelas->id][$jam->id]['presensi_guru_id'] = $presensi->id;
                }
            }
        }
    }

    /**
     * Memuat detail presensi guru untuk jadwal tertentu dan menampilkan modal.
     * Menggunakan presensi_guru_id untuk mengambil detail langsung.
     */
    public function showDetails(?int $presensiGuruId): void
    {
        if (is_null($presensiGuruId)) {
            $this->selectedJadwalDetails = null;
            $this->showDetailsModal = true;
            return;
        }

        // Ambil record PresensiGuru berdasarkan ID
        // Eager load guru dan mapel langsung jika belum ada di $this->gurus dan $this->mapels
        $presensi = PresensiGuru::with(['guru', 'mapel'])->find($presensiGuruId);

        if ($presensi) {
            // Mengambil nama guru dan kode mapel dari relasi langsung
            $guruName = $presensi->guru->nama_guru ?? 'N/A';
            $mapelKode = $presensi->mapel->kode ?? 'N/A'; // Asumsi ada kolom 'kode' di Mapel

            $this->selectedJadwalDetails = [
                'guru_name' => $guruName,
                'mapel_kode' => $mapelKode,
                'jam_in' => $presensi->jam_in ? Carbon::parse($presensi->jam_in)->format('H:i:s') : '-',
                'status_in' => $presensi->status_in ?? '-',
                'jam_out' => $presensi->jam_out ? Carbon::parse($presensi->jam_out)->format('H:i:s') : '-',
                'status_out' => $presensi->status_out ?? '-',
                'catatan' => $presensi->catatan ?? '-',
            ];
        } else {
            $this->selectedJadwalDetails = null;
        }

        $this->showDetailsModal = true;
    }

    /**
     * Menutup modal.
     */
    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedJadwalDetails = null;
    }

    public function render()
    {
        return view('livewire.today-schedule-matrix');
    }
}