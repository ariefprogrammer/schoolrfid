<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisTunjangan extends Model
{
    protected $table = 'tbl_jenis_tunjangan';

    protected $fillable = ['nama', 'tipe', 'nominal', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function penugasan(): HasMany
    {
        return $this->hasMany(TunjanganGuru::class, 'id_jenis');
    }
}