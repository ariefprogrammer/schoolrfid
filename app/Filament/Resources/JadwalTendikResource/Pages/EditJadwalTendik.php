<?php

namespace App\Filament\Resources\JadwalTendikResource\Pages;

use App\Filament\Resources\JadwalTendikResource;
use App\Models\JadwalTendik;
use Filament\Resources\Pages\EditRecord;

class EditJadwalTendik extends EditRecord
{
    protected static string $resource = JadwalTendikResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // kita tidak mau menyimpan 1 record default
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Hapus semua jadwal lama untuk tendik ini
        JadwalTendik::where('tendik_id', $data['tendik_id'])->delete();

        // Simpan semua hari baru
        foreach ($data['hari_ids'] as $hariId) {
            JadwalTendik::create([
                'tendik_id' => $data['tendik_id'],
                'hari_id'   => $hariId,
            ]);
        }

        return $record;
    }
}
