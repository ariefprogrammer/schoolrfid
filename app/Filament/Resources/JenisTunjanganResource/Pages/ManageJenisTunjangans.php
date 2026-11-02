<?php

namespace App\Filament\Resources\JenisTunjanganResource\Pages;

use App\Filament\Resources\JenisTunjanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisTunjangans extends ManageRecords
{
    protected static string $resource = JenisTunjanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
