<?php

namespace App\Filament\Resources\GuruResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TunjangansRelationManager extends RelationManager
{
    protected static string $relationship = 'tunjangans';

    protected static ?string $title = 'Tunjangan';

    protected static ?string $modelLabel = 'Tunjangan';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('id_jenis')
                ->label('Jenis Tunjangan')
                ->relationship('jenis', 'nama', fn (Builder $query) => $query->where('aktif', true))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('nominal')
                ->label('Nominal Khusus')
                ->numeric()->minValue(0)->prefix('Rp')
                ->helperText('Kosongkan untuk memakai nominal standar jenis tunjangan.'),
            Forms\Components\DatePicker::make('berlaku_mulai')
                ->label('Berlaku Mulai')
                ->required()
                ->default(now()->startOfMonth()),
            Forms\Components\DatePicker::make('berlaku_sampai')
                ->label('Berlaku Sampai')
                ->afterOrEqual('berlaku_mulai')
                ->helperText('Kosongkan bila masih berlaku. Bila ada penugasan jenis yang sama yang tumpang tindih, dipakai yang berlaku mulai paling baru.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('jenis.nama')->label('Jenis'),
                Tables\Columns\TextColumn::make('jenis.tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'harian' ? 'Harian' : 'Bulanan')
                    ->color(fn ($state) => $state === 'harian' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('nominal_efektif')
                    ->label('Nominal')
                    ->alignEnd()
                    ->state(fn ($record) => 'Rp ' . number_format($record->nominalEfektif(), 0, ',', '.')),
                Tables\Columns\TextColumn::make('berlaku_mulai')->label('Mulai')->date('d M Y'),
                Tables\Columns\TextColumn::make('berlaku_sampai')->label('Sampai')->date('d M Y')->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record) => $record->sedangBerlaku() ? 'Berlaku' : 'Tidak berlaku')
                    ->color(fn (string $state) => $state === 'Berlaku' ? 'success' : 'gray'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}