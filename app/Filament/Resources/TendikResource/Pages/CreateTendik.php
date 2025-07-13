<?php

namespace App\Filament\Resources\TendikResource\Pages;

use App\Filament\Resources\TendikResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTendik extends CreateRecord
{
    protected static string $resource = TendikResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
