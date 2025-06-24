<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\IzinGuruResource;
use Filament\Pages\Actions\CreateAction;
use Filament\Pages\Actions\Action;

class ListIzinGurus extends ListRecords
{
    protected static string $resource = IzinGuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ajukanIzin')
                ->label('Ajukan Izin')
                ->url(fn (): string => route('filament.administrator.pages.ajukan-izin-guru')) // Pastikan routenya benar
                ->icon('heroicon-o-plus'),
            Action::make('ajukanIzin')
                ->label('Ajukan Libur')
                ->url(fn (): string => route('filament.administrator.pages.ajukan-libur-guru')) // Pastikan routenya benar
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Pengajuan Izin Guru';
    }
}