<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Hari extends Model
{
    use HasFactory;

    protected $table = 'tbl_hari'; // Tentukan nama tabel yang sesuai

    protected $fillable = ['hari', 'order', 'id_jam_profil'];

    public function profilJam()
    {
        return $this->belongsTo(JamProfil::class, 'id_jam_profil');
    }

    public function jamPelajaran(): Collection
    {
        $profilId = $this->id_jam_profil
            ?? JamProfil::where('is_default', true)->value('id');

        return Jam::where('id_jam_profil', $profilId)->orderBy('ke')->get();
    }

    public function gantiPolaJam(?int $polaBaruId): void
    {
        DB::transaction(function () use ($polaBaruId) {
            $target = $polaBaruId ?? JamProfil::where('is_default', true)->value('id');
            $jamBaru = Jam::where('id_jam_profil', $target)->pluck('id', 'ke');

            Jadwal::where('id_hari', $this->id)->with('jam')->get()->each(function ($j) use ($jamBaru) {
                $idBaru = $jamBaru[$j->jam->ke] ?? null;
                $idBaru ? $j->update(['id_jam' => $idBaru]) : $j->delete(); 
            });

            $this->update(['id_jam_profil' => $polaBaruId]);
        });
    }
}