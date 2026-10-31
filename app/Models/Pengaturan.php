<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'tbl_pengaturan'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'nama_aplikasi',
        'token_telegram',
        'telegram_kepsek',
        'upah_perjam',
        'fonnte_token', 
        'wa_kepsek',

        'batas_terlambat',
        'potongan_terlambat',
        'durasi_potongan_terlambat',
        'maks_potongan_terlambat',
        'batas_bolos',
        'potongan_bolos',
        'durasi_potongan_bolos',
        'maks_potongan_bolos',
        'izin_dengan_tugas',
        'izin_tanpa_tugas',
    ];

    public function hitungPotonganTerlambat(int $menit): int
    {
        return $this->hitungPotongan(
            $menit,
            (int) $this->batas_terlambat,
            (int) $this->durasi_potongan_terlambat,
            (int) $this->potongan_terlambat,
            (int) $this->maks_potongan_terlambat,
        );
    }

    public function hitungPotonganBolos(int $menit): int
    {
        return $this->hitungPotongan(
            $menit,
            (int) $this->batas_bolos,
            (int) $this->durasi_potongan_bolos,
            (int) $this->potongan_bolos,
            (int) $this->maks_potongan_bolos,
        );
    }

    protected function hitungPotongan(int $menit, int $batas, int $durasi, int $nominal, int $maks): int
    {
        // Batas hanya ambang aktivasi; bila terlewati, seluruh menit dihitung
        if ($menit <= 0 || $menit <= $batas) {
            return 0;
        }

        $potongan = intdiv($menit, max(1, $durasi)) * $nominal;

        return $maks > 0 ? min($potongan, $maks) : $potongan;
    }

    public function statusMasuk(int $menitTerlambat): string
    {
        return $menitTerlambat > (int) $this->batas_terlambat ? 'terlambat' : 'hadir';
    }

    public function statusKeluar(int $menitBolos): string
    {
        return $menitBolos > (int) $this->batas_bolos ? 'bolos' : 'pulang';
    }
}