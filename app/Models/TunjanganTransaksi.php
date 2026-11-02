<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TunjanganTransaksi extends Model
{
    protected $table = 'tbl_tunjangan_transaksi';

    protected $fillable = [
        'id_guru', 'id_jenis', 'periode', 'tanggal', 'nominal', 'paid', 'disesuaikan', 'keterangan',
    ];

    protected $casts = [
        'periode' => 'date',
        'tanggal' => 'date',
        'nominal' => 'integer',
        'paid' => 'boolean',
        'disesuaikan' => 'boolean',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisTunjangan::class, 'id_jenis');
    }
}