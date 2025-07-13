<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalPresensiResource\Pages;
use App\Filament\Resources\JadwalPresensiResource\RelationManagers;
use App\Models\JadwalPresensi; // Pastikan model JadwalPresensi diimport
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select; // Import Select untuk dropdown
use Filament\Forms\Components\TimePicker; // Import TimePicker
use App\Models\User;

class JadwalPresensiResource extends Resource
{
    protected static ?string $model = JadwalPresensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days'; // Icon yang cocok

    protected static ?string $navigationLabel = 'Jadwal Presensi';
    protected static ?string $pluralLabel = 'Jadwal Presensi';
    protected static ?int $navigationSort = 18;
    protected static ?string $navigationGroup = 'Siswa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('hari')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true), // Pastikan hari unik (satu jadwal per hari)

                TimePicker::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->required()
                    ->seconds(false), // Tidak perlu detik

                TimePicker::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->required()
                    ->seconds(false), // Tidak perlu detik
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari')
                    ->searchable()
                    ->sortable()
                    ->label('Hari'),
                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Jam Pulang'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui Pada'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwalPresensis::route('/'),
            'create' => Pages\CreateJadwalPresensi::route('/create'),
            'edit' => Pages\EditJadwalPresensi::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}