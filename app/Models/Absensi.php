<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'tbl_absensi';

    protected $fillable = [
        'date_time',
        'rfid',
        'id_kelas',
        'jenis',
        'status',
        'keterangan',
    ];

    // Relasi ke model Siswa (meskipun rfid tidak foreign key, kita bisa pakai relasi berdasarkan rfid)
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'rfid', 'rfid');
    }

    // Relasi ke model Kelas
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id');
    }
}