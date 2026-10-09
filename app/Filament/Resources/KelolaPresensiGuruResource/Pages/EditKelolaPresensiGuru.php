<?php

namespace App\Filament\Resources\KelolaPresensiGuruResource\Pages;

use App\Filament\Resources\KelolaPresensiGuruResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKelolaPresensiGuru extends EditRecord
{
    protected static string $resource = KelolaPresensiGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
