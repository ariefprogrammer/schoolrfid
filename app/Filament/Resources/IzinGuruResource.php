<?php

namespace App\Filament\Resources;

use App\Models\Guru;
use App\Models\PresensiGuru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class IzinGuruResource extends Resource
{
    protected static ?string $model = PresensiGuru::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Presensi Guru';
    protected static ?string $navigationLabel = 'Izin Guru';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_guru')
                    ->label('Pilih Guru')
                    ->options(Guru::all()->pluck('nama_guru', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Izin')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guru.nama_guru')->label('Nama Guru'),
                Tables\Columns\TextColumn::make('tanggal_jadwal')->label('Tanggal'),
                Tables\Columns\TextColumn::make('status_in')->label('Status'),
                Tables\Columns\TextColumn::make('updated_at')->label('Terakhir Diubah'),
            ])
            ->filters([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status_in', ['Izin', 'Sakit', 'Libur']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIzinGurus::route('/'),
            'create' => Pages\CreateIzinGuru::route('/create'),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $idGuru = $data['id_guru'];
        $tanggal = $data['tanggal'];
        $status = $data['status'];

        // Cek apakah guru punya jadwal di tanggal tersebut
        $exists = PresensiGuru::where('id_guru', $idGuru)
            ->whereDate('tanggal_jadwal', $tanggal)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'tanggal' => ['Tidak ada jadwal guru ini pada tanggal tersebut.'],
            ]);
        }

        // Update status_in dan status_out
        PresensiGuru::where('id_guru', $idGuru)
            ->whereDate('tanggal_jadwal', $tanggal)
            ->update([
                'status_in' => $status,
                'status_out' => $status,
            ]);

        // Tampilkan notifikasi sukses
        Notification::make()
            ->title('Berhasil!')
            ->body('Status presensi guru berhasil diperbarui.')
            ->success()
            ->send();

        // Kembalikan model dummy agar Filament tidak error
        return new PresensiGuru();
    }
}