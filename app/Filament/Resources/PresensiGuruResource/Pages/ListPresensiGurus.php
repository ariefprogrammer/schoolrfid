<?php

namespace App\Filament\Resources\PresensiGuruResource\Pages;

use App\Filament\Resources\PresensiGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPresensiGurus extends ListRecords
{
    protected static string $resource = PresensiGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
