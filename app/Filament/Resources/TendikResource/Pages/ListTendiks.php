<?php

namespace App\Filament\Resources\TendikResource\Pages;

use App\Filament\Resources\TendikResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTendiks extends ListRecords
{
    protected static string $resource = TendikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
