<?php

namespace App\Filament\Pages;

use App\Models\Pengaturan;
use App\Services\FonnteService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Models\User;

class AppSettings extends Page implements HasForms
{

    public static function canAccess(): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }
    
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';
    protected static ?string $title = 'Pengaturan Aplikasi';
    protected static string $view = 'filament.pages.pengaturan';
    protected static ?int $navigationSort = 27;

    public ?array $data = [];
    public ?Pengaturan $settings = null;

    // settings fonnte
    public ?string $qrImage = null;
    public bool $showQrModal = false;
    public ?string $deviceStatus = null;
    public ?string $deviceNumber = null;

    public function mount(): void
    {
        $this->settings = Pengaturan::first();
        if (!$this->settings) {
            $this->settings = Pengaturan::create();
        }
        $this->form->fill($this->settings->toArray());
        $this->refreshDeviceStatus();
    }

    public function refreshDeviceStatus(): void
    {
        if (empty($this->settings->fonnte_token)) {
            $this->deviceStatus = null;
            return;
        }

        $status = app(FonnteService::class)->getDeviceStatus($this->settings->fonnte_token);
        $this->deviceStatus = $status['connected'] ? 'connect' : 'disconnect';
        $this->deviceNumber = $status['device'];
    }

    public function connectWhatsApp(): void
    {
        if (empty($this->settings->fonnte_token)) {
            Notification::make()
                ->title('Isi Token Device Fonnte dahulu, lalu simpan pengaturan.')
                ->warning()->send();
            return;
        }

        $result = app(FonnteService::class)->getQr($this->settings->fonnte_token);

        if (!$result['success'] || empty($result['qr'])) {
            $this->refreshDeviceStatus();
            if ($this->deviceStatus === 'connect') {
                Notification::make()->title('WhatsApp sudah terhubung.')->success()->send();
            } else {
                Notification::make()
                    ->title('Gagal mengambil QR code.')
                    ->body($result['message'] ?? 'Cek kembali token device kamu.')
                    ->danger()->send();
            }
            return;
        }

        $this->qrImage = $result['qr'];
        $this->showQrModal = true;
    }

    public function pollConnectionStatus(): void
    {
        if (!$this->showQrModal) return;

        $this->refreshDeviceStatus();

        if ($this->deviceStatus === 'connect') {
            $this->showQrModal = false;
            $this->qrImage = null;
            Notification::make()->title('WhatsApp berhasil terhubung! 🎉')->success()->send();
        }
    }

    public function disconnectWhatsApp(): void
    {
        app(FonnteService::class)->disconnect($this->settings->fonnte_token);
        $this->refreshDeviceStatus();
        Notification::make()->title('WhatsApp diputus.')->success()->send();
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->qrImage = null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_aplikasi')
                    ->label('Nama Aplikasi')
                    ->maxLength(255)
                    ->placeholder('Contoh: Sistem Presensi RFID Sekolah'),

                TextInput::make('token_telegram')
                    ->label('Token Telegram Bot (Opsional)')
                    ->maxLength(255)
                    ->placeholder('Masukkan token bot Telegram Anda'),

                TextInput::make('telegram_kepsek')
                    ->label('Chat ID Telegram Kepala Sekolah (Opsional)')
                    ->maxLength(255)
                    ->placeholder('Contoh: 123456789'),

                TextInput::make('upah_perjam')
                    ->label('Upah Per Jam Guru (Rp)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->helperText('Digunakan untuk perhitungan honor guru'),

                TextInput::make('fonnte_token')
                    ->label('Token Device')
                    ->maxLength(255)
                    ->placeholder('Token device dari dashboard Fonnte'),

                TextInput::make('wa_kepsek')
                    ->label('Nomor WA Kepala Sekolah (Opsional)')
                    ->maxLength(255)
                    ->placeholder('Contoh: 6281234567890'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->settings->update($data);

            Notification::make()
                ->title('Pengaturan berhasil disimpan!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat menyimpan pengaturan.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}