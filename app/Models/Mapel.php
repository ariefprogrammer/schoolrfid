<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'tbl_mapel'; 

    protected $fillable = [
        'kode',
        'mata_pelajaran',
        'boleh_gabung',
        'boleh_paralel',
    ];

    protected $casts = [
        'boleh_gabung' => 'boolean',
        'boleh_paralel' => 'boolean',
    ];
}