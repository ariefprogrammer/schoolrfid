<?php

namespace App\Http\Controllers;

use App\Services\FonnteService;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Absensi;
use App\Models\JadwalPresensi;
use App\Models\JadwalTendik;
use App\Models\Tendik;
use App\Models\PresensiTendik;
use Carbon\Carbon; // Untuk bekerja dengan tanggal dan waktu
use App\Livewire\GuruPresensiMasuk;
use App\Livewire\GuruPresensiKeluar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class PresensiController extends Controller
{
    /**
     * Menampilkan halaman form presensi masuk dan daftar presensi hari ini.
     */
    public function showPresensiMasukForm(Request $request)
    {
        // Ambil data presensi masuk hari ini, urutkan descending berdasarkan date_time
        $today = Carbon::today();
        $presensiHariIni = Absensi::with(['siswa.kelas']) // Eager load relasi siswa dan kelas
                                    ->whereDate('date_time', $today)
                                    ->where('jenis', 'masuk')
                                    ->orderBy('date_time', 'desc')
                                    ->get();

        // Ambil pesan error dari session jika ada
        $errorMessage = session('error');
        $successMessage = session('success');

        return view('presensi.masuk', [
            'presensiHariIni' => $presensiHariIni,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
        ]);
    }

    /**
     * Memproses input RFID untuk presensi masuk.
     */
    public function processPresensiMasuk(Request $request)
    {
        // Validasi input RFID
        $request->validate([
            'rfid' => 'required|string|max:255',
        ]);

        $rfid = $request->input('rfid');
        $now = Carbon::now();
        $today = Carbon::today();
        $dayOfWeek = $now->isoFormat('dddd'); // Mendapatkan nama hari dalam Bahasa Indonesia (misal: Senin)

        try {
            // 1. Periksa apakah RFID ada di tbl_siswa
            $siswa = Siswa::where('rfid', $rfid)->first();

            if (!$siswa) {
                return redirect()->back()->with('error', 'RFID tidak ditemukan.');
            }

            // 2. Periksa apakah siswa sudah presensi masuk hari ini
            $sudahPresensiMasuk = Absensi::where('rfid', $rfid)
                                        ->whereDate('date_time', $today)
                                        ->where('jenis', 'masuk')
                                        ->exists();

            if ($sudahPresensiMasuk) {
                return redirect()->back()->with('error', 'Siswa dengan RFID ini sudah presensi masuk hari ini.');
            }

            // 3. Ambil jadwal presensi untuk hari ini
            $jadwal = JadwalPresensi::where('hari', $dayOfWeek)->first();

            if (!$jadwal) {
                return redirect()->back()->with('error', 'Jadwal presensi untuk hari ' . $dayOfWeek . ' belum diatur.');
            }

            // 4. Bandingkan waktu sekarang dengan jam_masuk di jadwal
            $jamMasukJadwal = Carbon::parse($jadwal->jam_masuk);
            $status = ($now->lessThanOrEqualTo($jamMasukJadwal)) ? 'hadir' : 'terlambat';

            // 5. Simpan data presensi ke tbl_absensi
            Absensi::create([
                'date_time' => $now,
                'rfid' => $rfid,
                'id_kelas' => $siswa->id_kelas,
                'jenis' => 'masuk',
                'status' => $status,
                'keterangan' => null, // Keterangan awal null
            ]);

            $this->notifikasiTelegram(
                $rfid,
                $siswa->nama_siswa,
                $siswa->kelas->nama_kelas ?? '-',
                $now,
                'masuk',
                $status,
                $siswa->telepon_wali
            );

            $this->notifikasiWhatsApp($siswa->nama_siswa, $siswa->kelas->nama_kelas ?? '-', $now, 'masuk', $status, $siswa->telepon_wali);

            return redirect()->back()->with('success', 'Presensi masuk berhasil untuk ' . $siswa->nama_siswa . ' (Status: ' . $status . ')');

        } catch (\Exception $e) {
            // Penanganan error umum
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showPresensiKeluarForm(Request $request)
    {
        Carbon::setLocale('id'); // Pastikan locale tetap diatur

        $today = Carbon::today();
        $presensiHariIni = Absensi::with(['siswa.kelas']) // Eager load relasi siswa dan kelas
                                    ->whereDate('date_time', $today)
                                    ->where('jenis', 'keluar') // Hanya ambil jenis 'keluar'
                                    ->orderBy('date_time', 'desc')
                                    ->get();

        $errorMessage = session('error');
        $successMessage = session('success');

        return view('presensi.keluar', [ // Menggunakan view presensi.keluar
            'presensiHariIni' => $presensiHariIni,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
        ]);
    }

    /**
     * Memproses input RFID untuk presensi keluar.
     */
    public function processPresensiKeluar(Request $request)
    {
        Carbon::setLocale('id'); // Pastikan locale tetap diatur

        // Validasi input RFID
        $request->validate([
            'rfid' => 'required|string|max:255',
        ]);

        $rfid = $request->input('rfid');
        $now = Carbon::now();
        $today = Carbon::today();
        $dayOfWeek = $now->isoFormat('dddd');

        try {
            // 1. Periksa apakah RFID ada di tbl_siswa (penting untuk mendapatkan id_kelas dan nama)
            $siswa = Siswa::where('rfid', $rfid)->first();
            if (!$siswa) {
                return redirect()->back()->with('error', 'RFID tidak ditemukan.');
            }

            // 2. Periksa apakah siswa sudah presensi masuk hari ini
            $sudahPresensiMasukHariIni = Absensi::where('rfid', $rfid)
                                                ->whereDate('date_time', $today)
                                                ->where('jenis', 'masuk')
                                                ->exists();

            if (!$sudahPresensiMasukHariIni) {
                return redirect()->back()->with('error', 'Siswa ini belum presensi masuk hari ini.');
            }

            // 3. Periksa apakah siswa sudah presensi keluar hari ini
            $sudahPresensiKeluarHariIni = Absensi::where('rfid', $rfid)
                                                ->whereDate('date_time', $today)
                                                ->where('jenis', 'keluar')
                                                ->exists();

            if ($sudahPresensiKeluarHariIni) {
                return redirect()->back()->with('error', 'Siswa ini sudah presensi keluar hari ini.');
            }

            // 4. Ambil jadwal presensi untuk hari ini
            $jadwal = JadwalPresensi::where('hari', $dayOfWeek)->first();
            if (!$jadwal) {
                return redirect()->back()->with('error', 'Jadwal presensi untuk hari ' . $dayOfWeek . ' belum diatur.');
            }

            // 5. Bandingkan waktu sekarang dengan jam_pulang di jadwal
            $jamPulangJadwal = Carbon::parse($jadwal->jam_pulang);
            $status = ($now->greaterThanOrEqualTo($jamPulangJadwal)) ? 'pulang' : 'bolos'; // Jika jam sekarang > jam pulang jadwal = pulang, jika < = bolos

            // 6. Simpan data presensi keluar ke tbl_absensi
            Absensi::create([
                'date_time' => $now,
                'rfid' => $rfid,
                'id_kelas' => $siswa->id_kelas,
                'jenis' => 'keluar',
                'status' => $status,
                'keterangan' => null, // Keterangan awal null
            ]);

            $this->notifikasiTelegram(
                $rfid,
                $siswa->nama_siswa,
                $siswa->kelas->nama_kelas ?? '-',
                $now,
                'keluar',
                $status,
                $siswa->telepon_wali
            );

            $this->notifikasiWhatsApp($siswa->nama_siswa, $siswa->kelas->nama_kelas ?? '-', $now, 'keluar', $status, $siswa->telepon_wali);

            return redirect()->back()->with('success', 'Presensi keluar berhasil untuk ' . $siswa->nama_siswa . ' (Status: ' . $status . ')');

        } catch (\Exception $e) {
            // Penanganan error umum
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Presensi guru
    public function showPresensiGuruForm()
    {
        // View ini hanya akan memuat komponen Livewire
        return view('presensi.guru');
    }

    public function showPresensiGuruMasukForm() // <-- NAMA METODE BERUBAH
    {
        return view('presensi.guru_masuk'); // <-- View yang akan kita buat/sesuaikan
    }

    public function showPresensiGuruKeluarForm() // <-- METODE BARU
    {
        return view('presensi.guru_keluar'); // <-- View yang akan kita buat
    }

    // Presensi masuk (tendik) 
    public function showPresensiTendikMasukForm()
    {
        $today = Carbon::today();
        $presensiHariIni = PresensiTendik::with('tendik') // jika nanti ingin eager load relasi
                                ->whereDate('date_time', $today)
                                ->where('jenis', 'masuk')
                                ->orderBy('date_time', 'desc')
                                ->get();

        $errorMessage = session('error');
        $successMessage = session('success');

        return view('presensi.tendik_masuk', [
            'presensiHariIni' => $presensiHariIni,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
        ]);
    }


    public function processPresensiTendikMasuk(Request $request)
    {
        $request->validate([
            'rfid' => 'required|string|max:255',
        ]);

        $rfid = $request->input('rfid');
        $now = Carbon::now();
        $today = Carbon::today();
        $namaHari = $now->locale('id')->isoFormat('dddd'); // nama hari dalam bahasa Indonesia: Senin, Selasa, dst

        try {
            $tendik = Tendik::where('rfid', $rfid)->first();

            if (!$tendik) {
                return redirect()->back()->with('error', 'RFID tendik tidak ditemukan.');
            }

            // Cek apakah sudah presensi masuk hari ini
            $sudahPresensi = PresensiTendik::where('rfid', $rfid)
                ->whereDate('date_time', $today)
                ->where('jenis', 'masuk')
                ->exists();

            if ($sudahPresensi) {
                return redirect()->back()->with('error', 'Tendik sudah presensi masuk hari ini.');
            }

            // Ambil jadwal tendik untuk hari ini
            $jadwalHariIni = JadwalTendik::where('tendik_id', $tendik->id)
                ->whereHas('hari', fn ($q) => $q->where('hari', $namaHari))
                ->first();

            if (!$jadwalHariIni) {
                return redirect()->back()->with('error', 'Hari ini tidak ada jadwal untuk tendik ' . $tendik->nama . '.');
            }

            // Bandingkan waktu sekarang dengan jadwal_masuk
            $jadwalMasuk = Carbon::parse($jadwalHariIni->jadwal_masuk);
            $status = $now->lessThanOrEqualTo($jadwalMasuk) ? 'hadir' : 'terlambat';

            // Simpan presensi
            PresensiTendik::create([
                'date_time' => $now,
                'rfid' => $rfid,
                'jenis' => 'masuk',
                'status' => $status,
                'keterangan' => null,
            ]);

            // Kirim notifikasi ke Kepala Sekolah
            $this->notificationKepsekTendik($tendik->nama, 'masuk', $status, $now);

            return redirect()->back()->with('success', 'Presensi masuk berhasil untuk ' . $tendik->nama . ' (Status: ' . $status . ')');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Presensi keluar (tendik)
    public function showPresensiTendikKeluarForm()
    {
        $today = Carbon::today();

        $presensiHariIni = PresensiTendik::whereDate('date_time', $today)
            ->where('jenis', 'keluar')
            ->with('tendik') // kalau ada relasi tendik di model
            ->orderBy('date_time', 'desc')
            ->get();

        $errorMessage = session('error');
        $successMessage = session('success');

        return view('presensi.tendik_keluar', [
            'presensiHariIni' => $presensiHariIni,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
        ]);
    }

    public function processPresensiTendikKeluar(Request $request)
    {
        $request->validate([
            'rfid' => 'required|string|max:255',
        ]);

        $rfid = $request->input('rfid');
        $now = Carbon::now();
        $today = Carbon::today();
        $namaHari = $now->locale('id')->isoFormat('dddd'); // Senin, dst.

        try {
            $tendik = Tendik::where('rfid', $rfid)->first();

            if (!$tendik) {
                return redirect()->back()->with('error', 'RFID tendik tidak ditemukan.');
            }

            // Pastikan sudah presensi masuk hari ini
            $sudahMasuk = PresensiTendik::where('rfid', $rfid)
                ->whereDate('date_time', $today)
                ->where('jenis', 'masuk')
                ->exists();

            if (!$sudahMasuk) {
                return redirect()->back()->with('error', 'Tendik belum presensi masuk hari ini.');
            }

            // Pastikan belum presensi keluar hari ini
            $sudahKeluar = PresensiTendik::where('rfid', $rfid)
                ->whereDate('date_time', $today)
                ->where('jenis', 'keluar')
                ->exists();

            if ($sudahKeluar) {
                return redirect()->back()->with('error', 'Tendik sudah presensi keluar hari ini.');
            }

            // Ambil jadwal tendik untuk hari ini
            $jadwalHariIni = JadwalTendik::where('tendik_id', $tendik->id)
                ->whereHas('hari', fn ($q) => $q->where('hari', $namaHari))
                ->first();

            if (!$jadwalHariIni) {
                return redirect()->back()->with('error', 'Hari ini tidak ada jadwal untuk tendik ' . $tendik->nama . '.');
            }

            // Bandingkan waktu sekarang dengan jadwal_keluar
            $jadwalKeluar = Carbon::parse($jadwalHariIni->jadwal_keluar);
            $status = $now->greaterThanOrEqualTo($jadwalKeluar) ? 'pulang' : 'bolos';

            // Simpan presensi keluar
            PresensiTendik::create([
                'date_time' => $now,
                'rfid' => $rfid,
                'jenis' => 'keluar',
                'status' => $status,
                'keterangan' => null,
            ]);

            // Panggil notifikasi Kepsek
            $this->notificationKepsekTendik($tendik->nama, 'keluar', $status, $now);

            return redirect()->back()->with('success', 'Presensi keluar berhasil untuk ' . $tendik->nama . ' (Status: ' . $status . ')');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    protected function notificationKepsekTendik($namaTendik, $jenis, $status, $waktu)
    {
        $pengaturan = \App\Models\Pengaturan::first();
        $token = $pengaturan?->token_telegram; 
        $chatId = $pengaturan?->telegram_kepsek; 

        if (!$token || !$chatId) {
            \Log::warning('Token Telegram atau Chat ID Kepala Sekolah tidak tersedia.');
            return;
        }

        $formattedTime = Carbon::parse($waktu)->format('H:i');
        $pesan = "📢 *Notifikasi Presensi Tendik*\n";
        $pesan .= "Nama : {$namaTendik}\n";
        $pesan .= "Jenis : {$jenis}\n";
        $pesan .= "Waktu : {$formattedTime}\n";
        $pesan .= "Status : {$status}";

        \Log::info("Mengirim notifikasi Kepsek: \n" . $pesan);

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $pesan,
                'parse_mode' => 'Markdown'
            ]);

            if ($response->failed()) {
                \Log::error("Gagal kirim notifikasi Kepsek: " . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error("Exception saat kirim notifikasi Kepsek: " . $e->getMessage());
        }
    }


    protected function notifikasiTelegram($rfid, $namaSiswa, $kelas, $waktu, $jenis, $status, $telepon_wali)
    {
        $pengaturan = \App\Models\Pengaturan::first();
        $token = $pengaturan?->token_telegram;
        $chatId = $telepon_wali;

        if (!$token) {
            \Log::warning('Token Telegram tidak tersedia.');
            return;
        }

        $formattedTime = Carbon::parse($waktu)->format('H:i');

        $pesan = "📢 Notifikasi aplikasi presensi {$jenis}\n";
        $pesan .= "Nama : {$namaSiswa}\n";
        $pesan .= "Kelas : {$kelas}\n";
        $pesan .= "Waktu {$jenis} : {$formattedTime}\n";
        $pesan .= "Status : {$status}";

        \Log::info("Mengirim pesan ke Telegram: \n" . $pesan);

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage",  [
                'chat_id' => $chatId,
                'text' => $pesan,
                'parse_mode' => 'HTML'
            ]);

            if ($response->failed()) {
                \Log::error("Gagal kirim ke Telegram: " . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error("Exception saat kirim Telegram: " . $e->getMessage());
        }
    }

    protected function notifikasiWhatsApp($namaSiswa, $kelas, $waktu, $jenis, $status, $teleponWali)
    {
        if (empty($teleponWali)) {
            \Log::info('Nomor WA wali tidak tersedia, notifikasi WA dilewati.');
            return;
        }

        $formattedTime = Carbon::parse($waktu)->format('H:i');
        $pesan = "📢 Notifikasi Presensi {$jenis}\n"
            . "Nama: {$namaSiswa}\n"
            . "Kelas: {$kelas}\n"
            . "Waktu {$jenis}: {$formattedTime}\n"
            . "Status: {$status}";

        app(FonnteService::class)->send($teleponWali, $pesan);
    }
}