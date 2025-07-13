<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TendikResource\Pages;
use App\Models\Tendik;
use App\Models\Jabatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\User;

class TendikResource extends Resource
{
    protected static ?string $model = Tendik::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Data Tendik';
    protected static ?string $navigationLabel = 'Tenaga Pendidik';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nip')
                    ->label('NIP')
                    ->unique()
                    ->required()
                    ->maxLength(20),

                Forms\Components\TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('id_jabatan')
                    ->label('Jabatan')
                    ->options(Jabatan::query()->pluck('jabatan', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique()
                    ->required(),

                Forms\Components\TextInput::make('rfid')
                    ->label('RFID')
                    ->unique()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nip')->searchable(),
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('jabatan.jabatan')->label('Jabatan'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('rfid'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTendiks::route('/'),
            'create' => Pages\CreateTendik::route('/create'),
            'edit' => Pages\EditTendik::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}
