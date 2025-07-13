<?php

namespace App\Filament\Resources\JadwalTendikResource\Pages;

use App\Filament\Resources\JadwalTendikResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJadwalTendiks extends ListRecords
{
    protected static string $resource = JadwalTendikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
