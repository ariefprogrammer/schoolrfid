<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelolaPresensiGuruResource\Pages;
use App\Models\PresensiGuru;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KelolaPresensiGuruResource extends Resource
{
    protected static ?string $model = PresensiGuru::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Kelola Presensi Guru';
    protected static ?string $modelLabel = 'Presensi Guru';
    protected static ?string $pluralModelLabel = 'Kelola Presensi Guru';
    protected static ?int $navigationSort = 25;

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('id_guru')
                ->label('Guru')
                ->relationship('guru', 'nama_guru')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('tanggal_jadwal')
                ->label('Tanggal Jadwal')
                ->required(),
            Forms\Components\TimePicker::make('jam_in')
                ->label('Jam Masuk'),
            Forms\Components\Select::make('status_in')
                ->label('Status Masuk')
                ->options([
                    'hadir' => 'Hadir',
                    'terlambat' => 'Terlambat',
                    'unassign' => 'Alpa (Unassign)',
                ]),
            Forms\Components\Select::make('status_out')
                ->label('Status Pulang')
                ->options([
                    'pulang' => 'Pulang',
                    'bolos' => 'Bolos',
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guru.nama_guru')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_jadwal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_in')->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('status_in')
                    ->label('Status Masuk')
                    ->badge(),
                Tables\Columns\TextColumn::make('status_out')
                    ->label('Status Pulang')
                    ->badge(),
            ])
            ->defaultSort('tanggal_jadwal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('id_guru')
                    ->label('Guru')
                    ->relationship('guru', 'nama_guru')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['dari'], fn ($q, $v) => $q->whereDate('tanggal_jadwal', '>=', $v))
                        ->when($data['sampai'], fn ($q, $v) => $q->whereDate('tanggal_jadwal', '<=', $v))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKelolaPresensiGurus::route('/'),
            'create' => Pages\CreateKelolaPresensiGuru::route('/create'),
            'edit' => Pages\EditKelolaPresensiGuru::route('/{record}/edit'),
        ];
    }
}