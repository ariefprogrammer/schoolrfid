<?php

namespace App\Filament\Resources\KehadiranHarianGuruResource\Pages;

use App\Filament\Resources\KehadiranHarianGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKehadiranHarianGurus extends ListRecords
{
    protected static string $resource = KehadiranHarianGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
