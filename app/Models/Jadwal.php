<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'tbl_jadwal'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'id_hari',
        'id_jam',
        'id_guru',
        'id_mapel',
        'id_kelas',
    ];

    /**
     * Get the hari that owns the Jadwal.
     */
    public function hari(): BelongsTo
    {
        return $this->belongsTo(Hari::class, 'id_hari', 'id');
    }

    /**
     * Get the jam that owns the Jadwal.
     */
    public function jam(): BelongsTo
    {
        return $this->belongsTo(Jam::class, 'id_jam', 'id');
    }

    /**
     * Get the guru that owns the Jadwal.
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id');
    }

    /**
     * Get the mapel that owns the Jadwal.
     */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'id_mapel', 'id');
    }

    /**
     * Get the kelas that owns the Jadwal.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id');
    }
}