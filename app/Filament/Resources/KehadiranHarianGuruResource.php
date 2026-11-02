<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KehadiranHarianGuruResource\Pages;
use App\Models\KehadiranHarianGuru;
use App\Models\User;
use App\Services\TunjanganGuruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KehadiranHarianGuruResource extends Resource
{
    protected static ?string $model = KehadiranHarianGuru::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Presensi Guru';
    protected static ?string $navigationLabel = 'Kehadiran Harian Guru';
    protected static ?string $modelLabel = 'Kehadiran Harian';
    protected static ?string $pluralModelLabel = 'Kehadiran Harian Guru';
    protected static ?int $navigationSort = 5;

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
                ->required()
                ->disabledOn('edit'),
            Forms\Components\DatePicker::make('tanggal')
                ->label('Tanggal')
                ->required()
                ->disabledOn('edit'),
            Forms\Components\Select::make('status')
                ->label('Keputusan')
                ->options([
                    'hadir' => 'Hadir (tidak masuk kelas), berhak tunjangan harian',
                    'batal' => 'Batalkan tunjangan harian',
                ])
                ->required(),
            Forms\Components\Textarea::make('alasan')
                ->label('Alasan')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guru.nama_guru')->label('Nama Guru')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'hadir' ? 'Hadir' : 'Dibatalkan')
                    ->color(fn ($state) => $state === 'hadir' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'manual' ? 'Manual (admin)' : 'Otomatis (presensi)')
                    ->color(fn ($state) => $state === 'manual' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('jam_datang')->label('Jam Datang')->dateTime('H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('alasan')->label('Alasan')->limit(40)->tooltip(fn ($record) => $record->alasan)->placeholder('-'),
                Tables\Columns\TextColumn::make('diinput_oleh')
                    ->label('Diinput Oleh')
                    ->formatStateUsing(fn ($state) => $state ? (User::find($state)?->name ?? '#' . $state) : '-'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('id_guru')
                    ->label('Guru')
                    ->relationship('guru', 'nama_guru')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari')->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai')->default(now()->endOfMonth()),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['dari'], fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                        ->when($data['sampai'], fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))),
                Tables\Filters\SelectFilter::make('sumber')
                    ->label('Sumber')
                    ->options(['otomatis' => 'Otomatis (presensi)', 'manual' => 'Manual (admin)']),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['hadir' => 'Hadir', 'batal' => 'Dibatalkan']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->sumber === 'manual'),
                Tables\Actions\Action::make('batalkan')
                    ->label('Batalkan Tunjangan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => $record->sumber === 'otomatis' && $record->status === 'hadir')
                    ->form([
                        Forms\Components\Textarea::make('alasan')->label('Alasan')->required()->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        try {
                            app(TunjanganGuruService::class)->catatManual(
                                (int) $record->id_guru,
                                $record->tanggal->toDateString(),
                                'batal',
                                $data['alasan']
                            );

                            Notification::make()->title('Tunjangan harian dibatalkan')->success()->send();
                        } catch (\DomainException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('kembalikan')
                    ->label('Kembalikan Otomatis')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn ($record) => $record->sumber === 'manual')
                    ->requiresConfirmation()
                    ->modalDescription('Keputusan manual dihapus, lalu kehadiran dihitung ulang dari data presensi.')
                    ->action(function ($record) {
                        try {
                            app(TunjanganGuruService::class)->kembalikanKeOtomatis($record);

                            Notification::make()->title('Dikembalikan ke otomatis')->success()->send();
                        } catch (\DomainException $e) {
                            Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKehadiranHarianGurus::route('/'),
            'create' => Pages\CreateKehadiranHarianGuru::route('/create'),
            'edit' => Pages\EditKehadiranHarianGuru::route('/{record}/edit'),
        ];
    }
}