<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IzinTendikResource\Pages;
use App\Models\PresensiTendik;
use App\Models\Tendik;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IzinTendikResource extends Resource
{
    protected static ?string $model = PresensiTendik::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Presensi Tendik';
    protected static ?string $navigationLabel = 'Izin Tendik';
    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        $now = now();

        return parent::getEloquentQuery()
            ->where('jenis', 'izin')
            ->whereMonth('date_time', $now->month)
            ->whereYear('date_time', $now->year);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('rfid')
                    ->label('Tendik')
                    ->options(Tendik::pluck('nama', 'rfid'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('date_time')
                    ->label('Tanggal Izin')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tendik.nama')
                    ->label('Tendik')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_time')
                    ->label('Tanggal')
                    ->date(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('keterangan')
                    ->limit(30),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIzinTendiks::route('/'),
            'create' => Pages\CreateIzinTendik::route('/create'),
            'edit' => Pages\EditIzinTendik::route('/{record}/edit'),
        ];
    }
}
