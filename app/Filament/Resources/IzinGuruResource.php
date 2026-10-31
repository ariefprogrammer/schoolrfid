<?php

namespace App\Filament\Resources;

use App\Models\Guru;
use App\Models\PresensiGuru;
use App\Services\HonorGuruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                Forms\Components\Radio::make('jenis_izin')
                    ->label('Jenis')
                    ->options([
                        'dengan_tugas' => 'Dengan tugas',
                        'tanpa_tugas' => 'Tanpa tugas',
                    ])
                    ->inline()
                    ->required()
                    ->helperText('Menentukan persentase honor yang tetap dibayarkan (diatur di Pengaturan Aplikasi).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guru.nama_guru')->label('Nama Guru'),
                Tables\Columns\TextColumn::make('tanggal_jadwal')->label('Tanggal')->date('d M Y'),
                Tables\Columns\TextColumn::make('status_in')->label('Status'),
                Tables\Columns\TextColumn::make('jenis_izin')
                    ->label('Jenis')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'dengan_tugas' => 'Dengan tugas',
                        'tanpa_tugas' => 'Tanpa tugas',
                        default => '-',
                    }),
                Tables\Columns\TextColumn::make('total_upah')
                    ->label('Upah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('updated_at')->label('Terakhir Diubah'),
            ])
            ->defaultSort('tanggal_jadwal', 'desc')
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
}