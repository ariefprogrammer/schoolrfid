<?php

namespace App\Filament\Resources\JadwalTendikResource\Pages;

use App\Filament\Resources\JadwalTendikResource;
use App\Models\JadwalTendik;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalTendik extends CreateRecord
{
    protected static string $resource = JadwalTendikResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Hapus jadwal lama untuk tendik ini (jaga-jaga kalau user submit ulang)
        JadwalTendik::where('tendik_id', $data['tendik_id'])->delete();

        // Simpan semua baris dari repeater 'jadwal'
        foreach ($data['jadwal'] as $jadwalItem) {
            JadwalTendik::create([
                'tendik_id'      => $data['tendik_id'],
                'hari_id'        => $jadwalItem['hari_id'],
                'jadwal_masuk'   => $jadwalItem['jadwal_masuk'],
                'jadwal_keluar'  => $jadwalItem['jadwal_keluar'],
            ]);
        }

        // Return salah satu record sebagai acuan redirect
        return JadwalTendik::where('tendik_id', $data['tendik_id'])->first();
    }
}
