<?php

namespace App\Filament\Resources\JamResource\Pages;

use App\Filament\Resources\JamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJams extends ListRecords
{
    protected static string $resource = JamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
