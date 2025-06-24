<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiGuru extends Model
{
    use HasFactory;

    protected $table = 'tbl_presensi_guru';

    protected $fillable = [
        'periode_awal',
        'periode_akhir',
        'id_kelas',
        'id_hari',
        'id_jam',
        'id_guru',
        'id_mapel',
        'jam_in',
        'status_in',
        'jam_out',
        'status_out',
        'tanggal_jadwal',
    ];

    // Casting untuk memastikan Carbon instances saat diakses
    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'tanggal_jadwal' => 'date',
        'jam_in' => 'datetime',
        'jam_out' => 'datetime',
    ];

    // Relasi ke model-model terkait (berguna untuk pelaporan nanti)
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id');
    }

    public function hari(): BelongsTo
    {
        return $this->belongsTo(Hari::class, 'id_hari', 'id');
    }

    public function jam(): BelongsTo
    {
        return $this->belongsTo(Jam::class, 'id_jam', 'id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'id_mapel', 'id');
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal');
    }
}