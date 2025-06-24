<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPresensi extends Model
{
    use HasFactory;

    protected $table = 'tbl_jadwal_presensi';

    protected $fillable = [
        'hari',
        'jam_masuk',
        'jam_pulang',
    ];

    // Opsional: Jika Anda ingin Laravel mengelola casting untuk enum, meskipun tidak selalu diperlukan.
    // protected $casts = [
    //     'hari' => \App\Enums\HariPresensi::class, // Contoh jika Anda punya enum PHP
    // ];
}