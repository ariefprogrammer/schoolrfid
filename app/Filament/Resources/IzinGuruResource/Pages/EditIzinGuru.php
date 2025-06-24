<?php

namespace App\Filament\Resources\IzinGuruResource\Pages;

use App\Filament\Resources\IzinGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIzinGuru extends EditRecord
{
    protected static string $resource = IzinGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
