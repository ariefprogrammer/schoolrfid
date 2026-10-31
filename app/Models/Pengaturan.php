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
}