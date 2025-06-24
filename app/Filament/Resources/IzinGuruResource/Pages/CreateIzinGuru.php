<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\IzinGuruResource;

class CreateIzinGuru extends CreateRecord
{
    protected static string $resource = IzinGuruResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Ajukan Izin Guru';
    }
}