<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\IzinGuruResource;
use App\Models\PresensiGuru;
use App\Services\HonorGuruService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateIzinGuru extends CreateRecord
{
    protected static string $resource = IzinGuruResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $jumlah = app(HonorGuruService::class)->catatIzin(
            (int) $data['id_guru'],
            $data['tanggal'],
            $data['status'],
            $data['jenis_izin'],
        );

        if ($jumlah === 0) {
            throw ValidationException::withMessages([
                'data.tanggal' => 'Tidak ada jadwal yang bisa diberi izin pada tanggal tersebut (belum ada jadwal, sudah presensi, atau hari libur).',
            ]);
        }

        // Model kosong agar Filament tidak error; data sudah diperbarui oleh service
        return new PresensiGuru();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Izin guru berhasil dicatat.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Ajukan Izin Guru';
    }
}