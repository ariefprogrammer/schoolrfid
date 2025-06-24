<?php

namespace App\Filament\Resources\JamResource\Pages;

use App\Filament\Resources\JamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJam extends CreateRecord
{
    protected static string $resource = JamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
