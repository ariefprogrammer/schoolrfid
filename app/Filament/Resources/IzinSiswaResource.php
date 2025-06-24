<?php

namespace App\Filament\Resources;

use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components;
use App\Models\User;

class IzinSiswaResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Presensi Siswa';
    protected static ?string $navigationLabel = 'Izin Siswa';
    
    protected static ?int $navigationSort = 5;

    public static function getModel(): string
    {
        return Absensi::class;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('jenis', 'Izin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('rfid')
                    ->label('Pilih Siswa')
                    ->options(function () {
                        return DB::table('tbl_siswa')->pluck('nama_siswa', 'rfid');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('date_time')
                    ->label('Tanggal Izin')
                    ->default(now())
                    ->required(),

                Forms\Components\Select::make('id_kelas')
                    ->label('Kelas')
                    ->options(\App\Models\Kelas::all()->pluck('nama_kelas', 'id'))
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->required(),

                Components\Hidden::make('jenis')
                    ->default('izin'),

                Forms\Components\TextInput::make('keterangan')
                    ->label('Keterangan Izin')
                    ->nullable(),
                    ]);
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfid')->label('RFID'),
                Tables\Columns\TextColumn::make('date_time')->label('Tanggal'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('keterangan'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListIzinSiswas::route('/'),
            'create' => Pages\CreateIzinSiswa::route('/create'),
            'edit' => Pages\EditIzinSiswa::route('/{record}/edit'),
        ];
    }
}