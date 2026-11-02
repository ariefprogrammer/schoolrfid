<?php

namespace App\Filament\Resources\KehadiranHarianGuruResource\Pages;

use App\Filament\Resources\KehadiranHarianGuruResource;
use App\Services\TunjanganGuruService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateKehadiranHarianGuru extends CreateRecord
{
    protected static string $resource = KehadiranHarianGuruResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(TunjanganGuruService::class)->catatManual(
                (int) $data['id_guru'],
                $data['tanggal'],
                $data['status'],
                $data['alasan']
            );
        } catch (\DomainException $e) {
            throw ValidationException::withMessages(['data.tanggal' => $e->getMessage()]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}