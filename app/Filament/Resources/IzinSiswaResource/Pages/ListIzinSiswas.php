<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\CreateAction;
use App\Filament\Resources\IzinSiswaResource;

class ListIzinSiswas extends ListRecords
{
    protected static string $resource = IzinSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Pengajuan Izin Siswa';
    }
}