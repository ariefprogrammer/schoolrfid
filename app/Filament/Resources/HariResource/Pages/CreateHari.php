<?php

namespace App\Filament\Resources\HariResource\Pages;

use App\Filament\Resources\HariResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHari extends CreateRecord
{
    protected static string $resource = HariResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
