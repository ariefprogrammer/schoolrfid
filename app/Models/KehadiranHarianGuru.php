<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KehadiranHarianGuru extends Model
{
    protected $table = 'tbl_kehadiran_harian_guru';

    protected $fillable = [
        'id_guru', 'tanggal', 'status', 'sumber', 'jam_datang', 'alasan', 'diinput_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_datang' => 'datetime',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }
}