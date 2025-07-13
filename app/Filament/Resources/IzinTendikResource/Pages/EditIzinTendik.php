<?php

namespace App\Filament\Resources\IzinTendikResource\Pages;

use App\Filament\Resources\IzinTendikResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIzinTendik extends EditRecord
{
    protected static string $resource = IzinTendikResource::class;

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
