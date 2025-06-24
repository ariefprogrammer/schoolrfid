<?php

namespace App\Filament\Resources\JamResource\Pages;

use App\Filament\Resources\JamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJam extends EditRecord
{
    protected static string $resource = JamResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
