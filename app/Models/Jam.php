<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jam extends Model
{
    use HasFactory;

    protected $table = 'tbl_jam'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'ke',
        'jam_mulai',
        'jam_selesai',
    ];
}