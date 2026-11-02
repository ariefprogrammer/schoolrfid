<?php

namespace App\Filament\Resources\TunjanganTransaksiResource\Pages;

use App\Filament\Resources\TunjanganTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTunjanganTransaksis extends ManageRecords
{
    protected static string $resource = TunjanganTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
