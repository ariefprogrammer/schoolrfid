<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tendik extends Model
{
    use HasFactory;

    protected $table = 'tbl_tendik';

    protected $fillable = [
        'nip',
        'nama',
        'id_jabatan',
        'email',
        'rfid',
        'no_wa',
    ];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }
}
