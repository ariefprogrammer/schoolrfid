<?php

namespace App\Filament\Resources\IzinTendikResource\Pages;

use App\Filament\Resources\IzinTendikResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIzinTendik extends CreateRecord
{
    protected static string $resource = IzinTendikResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['jenis'] = 'izin';
        return $data;
    }
}
