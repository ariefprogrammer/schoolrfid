<?php

namespace App\Filament\Resources\PresensiGuruResource\Pages;

use App\Filament\Resources\PresensiGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresensiGuru extends EditRecord
{
    protected static string $resource = PresensiGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
