<?php

namespace App\Filament\Resources\JamProfilResource\Pages;

use App\Filament\Resources\JamProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJamProfil extends EditRecord
{
    protected static string $resource = JamProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
