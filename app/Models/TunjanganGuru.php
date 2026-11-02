<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TunjanganGuru extends Model
{
    protected $table = 'tbl_tunjangan_guru';

    protected $fillable = ['id_guru', 'id_jenis', 'nominal', 'berlaku_mulai', 'berlaku_sampai'];

    protected $casts = [
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'id_guru');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisTunjangan::class, 'id_jenis');
    }

    public function nominalEfektif(): int
    {
        return (int) ($this->nominal ?? $this->jenis->nominal);
    }

    public function sedangBerlaku(): bool
    {
        $hariIni = now()->startOfDay();

        return $this->berlaku_mulai->lte($hariIni)
            && ($this->berlaku_sampai === null || $this->berlaku_sampai->gte($hariIni));
    }
}