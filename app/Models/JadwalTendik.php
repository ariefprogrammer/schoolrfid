<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalTendik extends Model
{
    use HasFactory;

    protected $table = 'tbl_jadwal_tendik';

    protected $fillable = [
        'tendik_id',
        'hari_id',
        'jadwal_masuk',
        'jadwal_keluar',
    ];

    public function tendik()
    {
        return $this->belongsTo(Tendik::class);
    }

    public function hari()
    {
        return $this->belongsTo(Hari::class);
    }
}
