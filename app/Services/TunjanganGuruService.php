<?php

namespace App\Services;

use App\Models\JenisTunjangan;
use App\Models\KehadiranHarianGuru;
use App\Models\PresensiGuru;
use App\Models\TunjanganGuru;
use App\Models\TunjanganTransaksi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TunjanganGuruService
{
    /**
     * Catat kehadiran harian dan tunjangan harian seorang guru pada satu tanggal.
     * Tanpa $paksa: bila kehadiran hari itu sudah tercatat, tidak melakukan apa pun (tap berikutnya dilewati).
     * Dengan $paksa: dihitung ulang dari data presensi terbaru (untuk koreksi dan perintah malam hari).
     */
    public function sinkronHarian(int $idGuru, $tanggal, bool $paksa = false): void
    {
        $tanggal = Carbon::parse($tanggal)->toDateString();

        if (! $paksa && KehadiranHarianGuru::where('id_guru', $idGuru)->where('tanggal', $tanggal)->exists()) {
            return;
        }

        DB::transaction(function () use ($idGuru, $tanggal) {
            $idJenisHarian = JenisTunjangan::where('tipe', 'harian')->pluck('id');

            // Hari yang tunjangan harian-nya sudah dibayar dikunci
            $terkunci = TunjanganTransaksi::where('id_guru', $idGuru)
                ->where('tanggal', $tanggal)
                ->whereIn('id_jenis', $idJenisHarian)
                ->where('paid', true)
                ->exists();

            if ($terkunci) {
                return;
            }

            $rows = PresensiGuru::where('id_guru', $idGuru)
                ->whereDate('tanggal_jadwal', $tanggal)
                ->get(['id', 'jam_in', 'status_in']);

            $adaJadwal = $rows->isNotEmpty();
            $libur = $rows->contains(fn ($r) => strtolower((string) $r->status_in) === 'libur');
            $pertama = $rows->whereNotNull('jam_in')->sortBy('jam_in')->first()?->jam_in;

            $kehadiran = KehadiranHarianGuru::where('id_guru', $idGuru)->where('tanggal', $tanggal)->first();

            if ($kehadiran && $kehadiran->sumber === 'manual') {
                // Keputusan admin menang atas data presensi
                $hadir = $kehadiran->status === 'hadir';
            } else {
                $hadir = $adaJadwal && ! $libur && $pertama !== null;

                if ($hadir) {
                    KehadiranHarianGuru::updateOrCreate(
                        ['id_guru' => $idGuru, 'tanggal' => $tanggal],
                        ['status' => 'hadir', 'sumber' => 'otomatis', 'jam_datang' => $pertama]
                    );
                } else {
                    $kehadiran?->delete();
                }
            }

            $this->sinkronTransaksiHarian($idGuru, $tanggal, $hadir && $adaJadwal, $idJenisHarian);
        });
    }

    /**
     * Buat/perbarui tunjangan bulanan untuk satu bulan (semua guru, atau satu guru).
     */
    public function sinkronBulanan($periode, ?int $idGuru = null): void
    {
        $awal = Carbon::parse($periode)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth()->toDateString();
        $per = $awal->toDateString();

        $idJenisBulanan = JenisTunjangan::where('tipe', 'bulanan')->pluck('id');

        $guruIds = TunjanganGuru::whereIn('id_jenis', $idJenisBulanan)
            ->when($idGuru, fn ($q) => $q->where('id_guru', $idGuru))
            ->pluck('id_guru')
            ->merge(
                TunjanganTransaksi::where('periode', $per)
                    ->whereIn('id_jenis', $idJenisBulanan)
                    ->when($idGuru, fn ($q) => $q->where('id_guru', $idGuru))
                    ->pluck('id_guru')
            )
            ->unique();

        foreach ($guruIds as $g) {
            DB::transaction(function () use ($g, $per, $akhir, $idJenisBulanan) {
                $seharusnya = $this->penugasanBerlaku((int) $g, 'bulanan', $per, $akhir);

                foreach ($seharusnya as $p) {
                    $transaksi = TunjanganTransaksi::firstOrNew([
                        'id_guru' => $g,
                        'id_jenis' => $p->id_jenis,
                        'tanggal' => $per,
                    ]);

                    if ($transaksi->exists && ($transaksi->paid || $transaksi->disesuaikan)) {
                        continue;
                    }

                    $transaksi->fill(['periode' => $per, 'nominal' => $p->nominalEfektif()])->save();
                }

                TunjanganTransaksi::where('id_guru', $g)
                    ->where('periode', $per)
                    ->where('paid', false)
                    ->where('disesuaikan', false)
                    ->whereIn('id_jenis', $idJenisBulanan)
                    ->whereNotIn('id_jenis', $seharusnya->pluck('id_jenis'))
                    ->delete();
            });
        }
    }

    protected function sinkronTransaksiHarian(int $idGuru, string $tanggal, bool $berhak, Collection $idJenisHarian): void
    {
        $periode = Carbon::parse($tanggal)->startOfMonth()->toDateString();

        $seharusnya = $berhak
            ? $this->penugasanBerlaku($idGuru, 'harian', $tanggal, $tanggal)
            : collect();

        foreach ($seharusnya as $p) {
            $transaksi = TunjanganTransaksi::firstOrNew([
                'id_guru' => $idGuru,
                'id_jenis' => $p->id_jenis,
                'tanggal' => $tanggal,
            ]);

            if ($transaksi->exists && $transaksi->disesuaikan) {
                continue;
            }

            $transaksi->fill(['periode' => $periode, 'nominal' => $p->nominalEfektif()])->save();
        }

        // Hapus tunjangan harian yang belum dibayar dan tidak lagi berhak
        TunjanganTransaksi::where('id_guru', $idGuru)
            ->where('tanggal', $tanggal)
            ->where('paid', false)
            ->where('disesuaikan', false)
            ->whereIn('id_jenis', $idJenisHarian)
            ->whereNotIn('id_jenis', $seharusnya->pluck('id_jenis'))
            ->delete();
    }

    /**
     * Penugasan yang berlaku pada rentang [$mulai, $sampai], satu per jenis.
     * Bila ada yang tumpang tindih untuk jenis yang sama, dipakai yang berlaku mulai paling baru.
     */
    protected function penugasanBerlaku(int $idGuru, string $tipe, string $mulai, string $sampai): Collection
    {
        return TunjanganGuru::with('jenis')
            ->where('id_guru', $idGuru)
            ->whereDate('berlaku_mulai', '<=', $sampai)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', $mulai))
            ->whereHas('jenis', fn ($q) => $q->where('tipe', $tipe)->where('aktif', true))
            ->get()
            ->sortByDesc('berlaku_mulai')
            ->unique('id_jenis')
            ->values();
    }

    /**
     * Input manual admin: 'hadir' (hadir tanpa masuk kelas) atau 'batal' (cabut hak tunjangan harian).
     * Keputusan manual menang atas data presensi. Melempar \DomainException bila tidak diizinkan.
     */
    public function catatManual(int $idGuru, $tanggal, string $status, string $alasan): KehadiranHarianGuru
    {
        $tanggal = Carbon::parse($tanggal)->toDateString();

        if (! in_array($status, ['hadir', 'batal'], true)) {
            throw new \DomainException('Status tidak valid.');
        }

        if (blank($alasan)) {
            throw new \DomainException('Alasan wajib diisi.');
        }

        if (! PresensiGuru::where('id_guru', $idGuru)->whereDate('tanggal_jadwal', $tanggal)->exists()) {
            throw new \DomainException('Guru ini tidak punya jadwal pada tanggal tersebut.');
        }

        if ($this->hariTerkunci($idGuru, $tanggal)) {
            throw new \DomainException('Tunjangan harian pada tanggal ini sudah dibayar dan tidak dapat diubah.');
        }

        $ada = KehadiranHarianGuru::where('id_guru', $idGuru)->where('tanggal', $tanggal)->first();

        if ($ada && $ada->sumber === 'otomatis' && $status === 'hadir') {
            throw new \DomainException('Kehadiran guru ini pada tanggal tersebut sudah tercatat otomatis dari presensi.');
        }

        return DB::transaction(function () use ($idGuru, $tanggal, $status, $alasan, $ada) {
            $kehadiran = KehadiranHarianGuru::updateOrCreate(
                ['id_guru' => $idGuru, 'tanggal' => $tanggal],
                [
                    'status' => $status,
                    'sumber' => 'manual',
                    'alasan' => $alasan,
                    'diinput_oleh' => auth()->id(),
                    'jam_datang' => $ada?->jam_datang,
                ]
            );

            $this->sinkronHarian($idGuru, $tanggal, true);

            return $kehadiran->refresh();
        });
    }

    /** Hapus keputusan manual, lalu hitung ulang dari data presensi. */
    public function kembalikanKeOtomatis(KehadiranHarianGuru $kehadiran): void
    {
        $idGuru = (int) $kehadiran->id_guru;
        $tanggal = $kehadiran->tanggal->toDateString();

        if ($this->hariTerkunci($idGuru, $tanggal)) {
            throw new \DomainException('Tunjangan harian pada tanggal ini sudah dibayar dan tidak dapat diubah.');
        }

        DB::transaction(function () use ($kehadiran, $idGuru, $tanggal) {
            $kehadiran->delete();
            $this->sinkronHarian($idGuru, $tanggal, true);
        });
    }

    protected function hariTerkunci(int $idGuru, string $tanggal): bool
    {
        return TunjanganTransaksi::where('id_guru', $idGuru)
            ->where('tanggal', $tanggal)
            ->whereIn('id_jenis', JenisTunjangan::where('tipe', 'harian')->pluck('id'))
            ->where('paid', true)
            ->exists();
    }
}