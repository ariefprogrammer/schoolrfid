<?php

namespace App\Filament\Resources\HariResource\Pages;

use App\Filament\Resources\HariResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHaris extends ListRecords
{
    protected static string $resource = HariResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
