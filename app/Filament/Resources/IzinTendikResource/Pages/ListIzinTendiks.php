<?php

namespace App\Filament\Resources\IzinTendikResource\Pages;

use App\Filament\Resources\IzinTendikResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIzinTendiks extends ListRecords
{
    protected static string $resource = IzinTendikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
