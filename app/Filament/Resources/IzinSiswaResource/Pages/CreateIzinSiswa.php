<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\IzinSiswaResource;

class CreateIzinSiswa extends CreateRecord
{
    protected static string $resource = IzinSiswaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}