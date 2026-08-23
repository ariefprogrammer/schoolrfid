<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiswaResource\Pages;
use App\Filament\Resources\SiswaResource\RelationManagers;
use App\Models\Siswa;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use App\Imports\SiswasImport;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;



class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Siswa';
    protected static ?string $pluralLabel = 'Siswa';
    protected static ?int $navigationSort = 17;
    protected static ?string $navigationGroup = 'Siswa';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rfid')
                    ->label('Kode RFID')
                    ->maxLength(255)
                    ->required(fn ($livewire) => $livewire instanceof \App\Filament\Resources\SiswaResource\Pages\CreateSiswa)
                    ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\SiswaResource\Pages\CreateSiswa)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('nis')
                    ->label('Nomor Induk Siswa (NIS)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('nama_siswa')
                    ->label('Nama Lengkap Siswa')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('id_kelas')
                    ->label('Kelas')
                    ->required()
                    ->options(Kelas::all()->mapWithKeys(function ($kelas) {
                        return [$kelas->id => $kelas->kelas . ' - ' . $kelas->nama_kelas];
                    }))
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('telepon_siswa')
                    ->label('Telepon Siswa')
                    ->tel()
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('nama_wali')
                    ->label('Nama Wali')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('telepon_wali')
                    ->label('Telepon Wali')
                    ->placeholder('Contoh: 6281234567890')
                    ->tel()
                    ->maxLength(255)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfid')
                    ->searchable()
                    ->label('RFID'),
                Tables\Columns\TextColumn::make('nis')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('NIS'),
                Tables\Columns\TextColumn::make('nama_siswa')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Siswa'),
                Tables\Columns\TextColumn::make('kelas.nama_kelas')
                    ->searchable()
                    ->sortable()
                    ->label('Kelas')
                    ->formatStateUsing(fn (string $state, $record) => $record->kelas->kelas . ' - ' . $state),
                Tables\Columns\TextColumn::make('telepon_siswa')
                    ->label('Telepon Siswa')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nama_wali')
                    ->label('Nama Wali')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('telepon_wali')
                    ->label('Telepon Wali')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('id_kelas')
                    ->label('Filter Kelas')
                    ->options(Kelas::all()->mapWithKeys(function ($kelas) {
                        return [$kelas->id => $kelas->kelas . ' ' . $kelas->nama_kelas];
                    })),
            ])
            ->actions([
                Tables\Actions\Action::make('gantiRfid')
                    ->label('')
                    ->icon('heroicon-o-key')
                    ->tooltip('Ganti RFID')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('rfid')
                            ->label('Kode RFID Baru')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record) {
                        \DB::transaction(function () use ($data, $record) {
                            $oldRfid = $record->rfid;

                            // Update tabel siswa
                            $record->update([
                                'rfid' => $data['rfid'],
                            ]);

                            // Update tabel absensi
                            \DB::table('tbl_absensi')
                                ->where('rfid', $oldRfid)
                                ->update(['rfid' => $data['rfid']]);
                        });

                        Notification::make()
                            ->title('RFID berhasil diperbarui di data siswa & absensi')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('')->tooltip('Edit'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Hapus'),
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
            'index' => Pages\ListSiswas::route('/'),
            'create' => Pages\CreateSiswa::route('/create'),
            'edit' => Pages\EditSiswa::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Contoh: Hanya role 'admin' yang bisa melihat Resource Guru
        return auth()->user()->role === 'admin';
    }
}