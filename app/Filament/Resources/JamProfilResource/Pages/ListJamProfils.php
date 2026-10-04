<?php

namespace App\Filament\Resources\JamProfilResource\Pages;

use App\Filament\Resources\JamProfilResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListJamProfils extends ListRecords
{
    protected static string $resource = JamProfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return new HtmlString('<span class="text-sm text-gray-500">Halaman ini digunakan untuk custom jam pelajaran yang berbeda (Misal : hari jumat jam pelajaran tidak sama dengan hari lain).</span>');
    }
}
