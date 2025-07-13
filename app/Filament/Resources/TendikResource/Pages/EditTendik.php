<?php

namespace App\Filament\Resources\TendikResource\Pages;

use App\Filament\Resources\TendikResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTendik extends EditRecord
{
    protected static string $resource = TendikResource::class;

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
