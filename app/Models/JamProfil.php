<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JamProfil extends Model
{
    protected $table = 'tbl_jam_profil';

    protected $fillable = ['nama', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function jams()
    {
        return $this->hasMany(Jam::class, 'id_jam_profil')->orderBy('ke');
    }
}