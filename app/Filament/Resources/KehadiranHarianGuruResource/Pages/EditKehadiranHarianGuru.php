<?php

namespace App\Filament\Resources\KehadiranHarianGuruResource\Pages;

use App\Filament\Resources\KehadiranHarianGuruResource;
use App\Services\TunjanganGuruService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditKehadiranHarianGuru extends EditRecord
{
    protected static string $resource = KehadiranHarianGuruResource::class;

    protected function getHeaderActions(): array
    {
        return []; // tanpa Hapus; gunakan "Kembalikan Otomatis" di daftar
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(TunjanganGuruService::class)->catatManual(
                (int) $record->id_guru,
                $record->tanggal->toDateString(),
                $data['status'],
                $data['alasan']
            );
        } catch (\DomainException $e) {
            throw ValidationException::withMessages(['data.status' => $e->getMessage()]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}