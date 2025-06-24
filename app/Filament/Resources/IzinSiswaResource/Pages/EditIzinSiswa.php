<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\IzinSiswaResource;

class EditIzinSiswa extends EditRecord
{
    protected static string $resource = IzinSiswaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}