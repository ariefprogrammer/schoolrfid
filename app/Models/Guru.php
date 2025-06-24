<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; // Import Authenticatable

class Guru extends Authenticatable // Ubah extends Model menjadi extends Authenticatable
{
    use HasFactory;

    protected $table = 'tbl_guru'; // Tentukan nama tabel yang sesuai

    protected $fillable = [
        'kode',
        'nip',
        'nama_guru',
        'telegram_chat_id',
        'email',
        'password',
        'rfid',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Opsional: Untuk casting field
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    //     'password' => 'hashed', // Laravel 10+ otomatis mengenkripsi password
    // ];
}