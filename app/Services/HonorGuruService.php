<?php

namespace App\Services;

use App\Models\Pengaturan;
use App\Models\PresensiGuru;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HonorGuruService
{
    protected Pengaturan $aturan;

    public function __construct()
    {
        $this->aturan = Pengaturan::first() ?? new Pengaturan();
    }

    /** Masih ada jam pelajaran yang bisa diikuti bila guru masuk pada $jamIn? */
    public function adaJamDiikuti(Collection $rows, Carbon $jamIn): bool
    {
        return $rows->contains(fn ($r) => $this->diikuti($r, $jamIn, null));
    }

    public function catatMasuk(Collection $rows, Carbon $jamIn): string
    {
        return DB::transaction(function () use ($rows, $jamIn) {
            $segar = $this->ambilTerkunci($rows);

            if ($segar->contains(fn ($r) => ! is_null($r->jam_in))) {
                throw new \RuntimeException('Beberapa jadwal sudah presensi masuk.');
            }

            return $this->terapkanMasuk($segar, $jamIn);
        });
    }

    public function catatKeluar(Collection $rows, Carbon $jamOut): string
    {
        return DB::transaction(function () use ($rows, $jamOut) {
            $segar = $this->ambilTerkunci($rows);

            if ($segar->contains(fn ($r) => is_null($r->jam_in) || ! is_null($r->jam_out))) {
                throw new \RuntimeException('Beberapa jadwal belum presensi masuk atau sudah presensi keluar.');
            }

            return $this->terapkanKeluar($segar, $jamOut);
        });
    }

    /** Hitung ulang semua presensi seorang guru pada satu tanggal (pakai pengaturan saat ini). */
    public function hitungUlangHari(int $idGuru, $tanggal): void
    {
        DB::transaction(function () use ($idGuru, $tanggal) {
            $rows = PresensiGuru::with('jam')
                ->where('id_guru', $idGuru)
                ->whereDate('tanggal_jadwal', $tanggal)
                ->whereNotNull('jam_in')
                ->lockForUpdate()
                ->get();

            foreach ($rows->groupBy(fn ($r) => $r->jam_in->toDateTimeString()) as $batch) {
                $this->terapkanMasuk($batch, $batch->first()->jam_in->copy());
            }

            foreach ($rows->whereNotNull('jam_out')->groupBy(fn ($r) => $r->jam_out->toDateTimeString()) as $batch) {
                $this->terapkanKeluar($batch, $batch->first()->jam_out->copy());
            }

            foreach ($rows->whereNull('jam_out') as $row) {
                $row->update([
                    'durasi_bolos' => 0,
                    'potongan_bolos' => 0,
                    'upah_dasar' => 0,
                    'total_upah' => 0,
                ]);
            }
        });
    }

    protected function terapkanMasuk(Collection $rows, Carbon $jamIn): string
    {
        $rows = $this->urut($rows);

        // Acuan keterlambatan: jam pertama yang benar-benar diikuti
        $acuan = $rows->first(fn ($r) => $this->diikuti($r, $jamIn, null));
        $idxAcuan = $acuan ? $rows->search(fn ($r) => $r->is($acuan)) : null;

        $menit = 0;
        if ($acuan) {
            $mulai = $this->waktu($acuan, 'jam_mulai');
            $menit = $jamIn->greaterThan($mulai) ? (int) $mulai->diffInMinutes($jamIn, true) : 0;
        }

        $status = $this->aturan->statusMasuk($menit);
        $potongan = $this->aturan->hitungPotonganTerlambat($menit);

        foreach ($rows as $i => $row) {
            $ini = $i === $idxAcuan;

            $statusRow = match (true) {
                $idxAcuan === null || $i < $idxAcuan => 'terlambat', // jam yang sudah lewat sebelum guru masuk
                $ini => $status,                                      // jam acuan, sesuai batas
                default => 'hadir',                                   // jam berikutnya
            };

            $row->update([
                'jam_in' => $jamIn,
                'status_in' => $statusRow,
                'durasi_terlambat' => $ini ? $menit : 0,
                'potongan_terlambat' => $ini ? $potongan : 0,
            ]);
        }

        return $status; // status jam acuan, dipakai untuk notifikasi
    }

    protected function terapkanKeluar(Collection $rows, Carbon $jamOut): string
    {
        $rows = $this->urut($rows);
        $upah = (int) $this->aturan->upah_perjam;

        $diikuti = $rows->filter(fn ($r) => $this->diikuti($r, $r->jam_in, $jamOut));

        // Acuan bolos: jam terakhir yang benar-benar diikuti
        $acuan = $diikuti->last() ?? $rows->last();
        $idxAcuan = $rows->search(fn ($r) => $r->is($acuan));

        $selesai = $this->waktu($acuan, 'jam_selesai');
        $menit = $jamOut->lessThan($selesai) ? (int) $jamOut->diffInMinutes($selesai, true) : 0;

        $status = $this->aturan->statusKeluar($menit);
        $potonganBolos = $this->aturan->hitungPotonganBolos($menit);

        // Total potongan dibagi ke jam yang diikuti, masing-masing maksimal sebesar upahnya
        $sisa = (int) $rows->sum('potongan_terlambat') + $potonganBolos;

        foreach ($rows as $i => $row) {
            $ikut = $diikuti->contains(fn ($r) => $r->is($row));
            $dipotong = $ikut ? min($sisa, $upah) : 0;
            $sisa -= $dipotong;
            $ini = $i === $idxAcuan;

            $statusRow = match (true) {
                $diikuti->isEmpty() => 'bolos',  // tidak ada jam yang diikuti
                $ini => $status,                  // jam acuan, sesuai batas
                $i > $idxAcuan => 'bolos',        // jam yang belum mulai saat guru keluar
                default => 'pulang',              // jam sebelumnya
            };

            $row->update([
                'jam_out' => $jamOut,
                'status_out' => $statusRow,
                'durasi_bolos' => $ini ? $menit : 0,
                'potongan_bolos' => $ini ? $potonganBolos : 0,
                'upah_dasar' => $ikut ? $upah : 0,
                'total_upah' => $ikut ? $upah - $dipotong : 0,
            ]);
        }

        return $status; // status jam acuan, dipakai untuk notifikasi
    }

    /** Jam pelajaran diikuti bila rentangnya bersinggungan dengan rentang kehadiran guru. */
    protected function diikuti(PresensiGuru $row, Carbon $jamIn, ?Carbon $jamOut): bool
    {
        $mulai = $this->waktu($row, 'jam_mulai');
        $selesai = $this->waktu($row, 'jam_selesai');

        return $jamIn->lt($selesai) && (is_null($jamOut) || $jamOut->gt($mulai));
    }

    protected function waktu(PresensiGuru $row, string $kolom): Carbon
    {
        return $row->tanggal_jadwal->copy()->setTimeFromTimeString($row->jam->{$kolom});
    }

    protected function urut(Collection $rows): Collection
    {
        return $rows->sortBy(fn ($r) => $r->jam->ke)->values();
    }

    protected function ambilTerkunci(Collection $rows): Collection
    {
        return PresensiGuru::with('jam')
            ->whereIn('id', $rows->pluck('id'))
            ->lockForUpdate()
            ->get();
    }
}