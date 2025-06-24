<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'tbl_kelas';

    protected $fillable = [
        'kelas',
        'nama_kelas',
    ];

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'id_kelas');
    }
}