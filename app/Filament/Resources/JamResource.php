<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JamResource\Pages;
use App\Filament\Resources\JamResource\RelationManagers;
use App\Models\Jam; // Pastikan model Jam diimport
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TimePicker; // Import TimePicker
use App\Models\User;

class JamResource extends Resource
{
    protected static ?string $model = Jam::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock'; // Ikon jam
    protected static ?string $navigationGroup = 'Data Guru'; // Bisa dimasukkan ke grup Pengaturan
    protected static ?string $navigationLabel = 'Jam Pelajaran';
    protected static ?string $pluralLabel = 'Jam Pelajaran';
    protected static ?int $navigationSort = 12; // Urutkan setelah Manajemen Hari (jika Hari 20)

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ke')
                    ->label('Jam Pelajaran Ke-')
                    ->numeric() // Pastikan inputnya angka
                    ->required()
                    ->unique(ignoreRecord: true) // Pastikan unik
                    ->minValue(1) // Minimal jam ke-1
                    ->helperText('Contoh: 1, 2, 3, dst.'),
                TimePicker::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->required()
                    ->seconds(false), // Tidak perlu detik
                TimePicker::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->required()
                    ->seconds(false), // Tidak perlu detik
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ke')
                    ->searchable()
                    ->sortable()
                    ->label('Jam Ke-'),
                Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Jam Mulai'),
                Tables\Columns\TextColumn::make('jam_selesai')
                    ->label('Jam Selesai'),
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
            ])
            // Urutkan tabel berdasarkan kolom 'ke' secara default
            ->defaultSort('ke', 'asc');
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
            'index' => Pages\ListJams::route('/'),
            'create' => Pages\CreateJam::route('/create'),
            'edit' => Pages\EditJam::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';

    }
}