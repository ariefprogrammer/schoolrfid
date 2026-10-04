<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jam extends Model
{
    use HasFactory;

    protected $table = 'tbl_jam'; // Tentukan nama tabel yang sesuai

    protected $fillable = ['id_jam_profil', 'ke', 'jam_mulai', 'jam_selesai'];

    public function profil()
    {
        return $this->belongsTo(JamProfil::class, 'id_jam_profil');
    }
}