<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hari extends Model
{
    use HasFactory;

    protected $table = 'tbl_hari'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'hari',
        'order',
    ];
}