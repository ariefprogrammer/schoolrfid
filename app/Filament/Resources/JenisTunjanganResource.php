<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisTunjanganResource\Pages;
use App\Models\JenisTunjangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JenisTunjanganResource extends Resource
{
    protected static ?string $model = JenisTunjangan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Data Guru';
    protected static ?string $navigationLabel = 'Jenis Tunjangan';
    protected static ?string $modelLabel = 'Jenis Tunjangan';
    protected static ?string $pluralModelLabel = 'Jenis Tunjangan';
    protected static ?int $navigationSort = 16;

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Tunjangan')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100)
                ->placeholder('Contoh: Tunjangan Transport'),
            Forms\Components\Select::make('tipe')
                ->label('Tipe')
                ->options([
                    'bulanan' => 'Bulanan',
                    'harian' => 'Harian (dibayar per hari hadir)',
                ])
                ->required()
                ->disabledOn('edit')
                ->helperText('Tipe tidak dapat diubah setelah dibuat.'),
            Forms\Components\TextInput::make('nominal')
                ->label('Nominal Standar')
                ->numeric()->minValue(0)->required()->prefix('Rp')
                ->helperText('Bisa diganti per guru lewat tab Tunjangan di data guru.'),
            Forms\Components\Toggle::make('aktif')
                ->label('Aktif')
                ->default(true)
                ->helperText('Nonaktifkan bila sudah tidak dipakai. Jenis yang punya riwayat tidak dihapus.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'harian' ? 'Harian' : 'Bulanan')
                    ->color(fn ($state) => $state === 'harian' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal Standar')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                Tables\Columns\IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJenisTunjangans::route('/'),
        ];
    }
}