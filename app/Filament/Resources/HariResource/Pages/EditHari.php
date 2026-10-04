<?php

namespace App\Filament\Resources\HariResource\Pages;

use App\Filament\Resources\HariResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditHari extends EditRecord
{
    protected static string $resource = HariResource::class;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->gantiPolaJam($data['id_jam_profil'] ?? null);
        unset($data['id_jam_profil']);
        $record->update($data);

        return $record;
    }
}
