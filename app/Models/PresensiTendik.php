<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresensiTendik extends Model
{
    use HasFactory;

    protected $table = 'tbl_presensi_tendik';

    protected $fillable = [
        'date_time',
        'rfid',
        'jenis',
        'status',
        'keterangan',
    ];

    public function tendik()
    {
        return $this->hasOne(Tendik::class, 'rfid', 'rfid');
    }

}
