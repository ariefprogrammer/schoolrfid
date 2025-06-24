<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import BelongsTo

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'tbl_siswa'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'rfid',
        'nis',
        'nama_siswa',
        'id_kelas',
        'telepon_siswa',
        'nama_wali',
        'telepon_wali',
    ];

    /**
     * Get the kelas that owns the Siswa.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id');
    }
}