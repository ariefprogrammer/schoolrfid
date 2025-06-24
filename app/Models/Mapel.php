<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'tbl_mapel'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'kode',
        'mata_pelajaran',
    ];
}