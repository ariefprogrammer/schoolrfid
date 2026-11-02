<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KelolaPresensiGuruResource\Pages;
use App\Models\Pengaturan;
use App\Models\PresensiGuru;
use App\Services\HonorGuruService;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use App\Services\TunjanganGuruService;

class KelolaPresensiGuruResource extends Resource
{
    protected static ?string $model = PresensiGuru::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Kelola Presensi Guru';
    protected static ?string $modelLabel = 'Presensi Guru';
    protected static ?string $pluralModelLabel = 'Kelola Presensi Guru';
    protected static ?int $navigationSort = 25;

    protected const STATUS_MASUK = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'tidak_diikuti' => 'Tidak Diikuti',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'libur' => 'Libur',
        'unassign' => 'Belum Presensi (Unassign)',
    ];

    protected const STATUS_KELUAR = [
        'pulang' => 'Pulang',
        'bolos' => 'Bolos',
        'tidak_diikuti' => 'Tidak Diikuti',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'libur' => 'Libur',
        'unassign' => 'Belum Presensi (Unassign)',
    ];

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    // Baris presensi dibuat otomatis dari jadwal; resource ini khusus untuk koreksi
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['guru', 'jam', 'kelas']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Jadwal')
                ->description('Identitas jadwal tidak dapat diubah dari sini.')
                ->schema([
                    Forms\Components\Select::make('id_guru')
                        ->label('Guru')
                        ->relationship('guru', 'nama_guru')
                        ->disabled(),
                    Forms\Components\DatePicker::make('tanggal_jadwal')
                        ->label('Tanggal Jadwal')
                        ->disabled(),
                    Forms\Components\Placeholder::make('info_jam')
                        ->label('Jam Pelajaran')
                        ->content(fn ($record) => $record?->jam
                            ? "Jam ke-{$record->jam->ke} ({$record->jam->jam_mulai} - {$record->jam->jam_selesai})"
                            : '-'),
                    Forms\Components\Placeholder::make('info_kelas')
                        ->label('Kelas')
                        ->content(fn ($record) => $record?->namaKelasTampil() ?? '-'),
                    Forms\Components\Placeholder::make('peringatan_dibayar')
                        ->hiddenLabel()
                        ->content(new HtmlString('<div style="color:#b91c1c;font-weight:600">Baris ini sudah dibayar. Mengubah upah atau potongan membuat laporan gaji berbeda dari yang sudah dibayarkan.</div>'))
                        ->visible(fn ($record) => (bool) $record?->paid)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Presensi')
                ->schema([
                    Forms\Components\DateTimePicker::make('jam_in')
                        ->label('Jam Masuk')
                        ->seconds(false),
                    Forms\Components\Select::make('status_in')
                        ->label('Status Masuk')
                        ->options(self::STATUS_MASUK)
                        ->live(),
                    Forms\Components\DateTimePicker::make('jam_out')
                        ->label('Jam Pulang')
                        ->seconds(false)
                        ->afterOrEqual('jam_in'),
                    Forms\Components\Select::make('status_out')
                        ->label('Status Pulang')
                        ->options(self::STATUS_KELUAR),
                ])
                ->columns(2),

            Forms\Components\Section::make('Keterlambatan dan Bolos')
                ->description('Tombol kalkulator di tiap potongan menghitung otomatis dari durasi dan pengaturan saat ini.')
                ->schema([
                    Forms\Components\TextInput::make('durasi_terlambat')
                        ->label('Durasi Terlambat')
                        ->numeric()->minValue(0)->suffix('menit'),
                    Forms\Components\TextInput::make('potongan_terlambat')
                        ->label('Potongan Terlambat')
                        ->numeric()->minValue(0)->prefix('Rp')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('hitungPotonganTerlambat')
                                ->icon('heroicon-m-calculator')
                                ->tooltip('Hitung dari durasi terlambat')
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $aturan = Pengaturan::first() ?? new Pengaturan();
                                    $set('potongan_terlambat', $aturan->hitungPotonganTerlambat((int) $get('durasi_terlambat')));
                                })
                        ),
                    Forms\Components\TextInput::make('durasi_bolos')
                        ->label('Durasi Bolos')
                        ->numeric()->minValue(0)->suffix('menit'),
                    Forms\Components\TextInput::make('potongan_bolos')
                        ->label('Potongan Bolos')
                        ->numeric()->minValue(0)->prefix('Rp')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('hitungPotonganBolos')
                                ->icon('heroicon-m-calculator')
                                ->tooltip('Hitung dari durasi bolos')
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $aturan = Pengaturan::first() ?? new Pengaturan();
                                    $set('potongan_bolos', $aturan->hitungPotonganBolos((int) $get('durasi_bolos')));
                                })
                        ),
                ])
                ->columns(2),

            Forms\Components\Section::make('Upah dan Pembayaran')
                ->schema([
                    Forms\Components\TextInput::make('upah_dasar')
                        ->label('Upah Dasar')
                        ->numeric()->minValue(0)->prefix('Rp')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('isiUpahDasar')
                                ->icon('heroicon-m-calculator')
                                ->tooltip('Isi dari upah per jam di pengaturan')
                                ->action(function (Forms\Set $set) {
                                    $set('upah_dasar', (int) Pengaturan::first()?->upah_perjam);
                                })
                        ),
                    Forms\Components\Select::make('jenis_izin')
                        ->label('Jenis Izin')
                        ->options([
                            'dengan_tugas' => 'Dengan tugas',
                            'tanpa_tugas' => 'Tanpa tugas',
                        ])
                        ->visible(fn (Forms\Get $get) => in_array($get('status_in'), ['izin', 'sakit'])),
                    Forms\Components\TextInput::make('total_upah')
                        ->label('Total Upah')
                        ->numeric()->minValue(0)->prefix('Rp')
                        ->helperText('Tidak boleh melebihi upah dasar.')
                        ->rules([
                            fn (Forms\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                if ((int) $value > (int) $get('upah_dasar')) {
                                    $fail('Total upah tidak boleh melebihi upah dasar.');
                                }
                            },
                        ])
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('hitungTotalUpah')
                                ->icon('heroicon-m-calculator')
                                ->tooltip('Hitung dari upah dasar, potongan, atau persen izin')
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $aturan = Pengaturan::first() ?? new Pengaturan();
                                    $dasar = (int) $get('upah_dasar');

                                    $total = match ($get('status_in')) {
                                        'izin', 'sakit' => (int) round($dasar * (int) ($get('jenis_izin') === 'dengan_tugas'
                                            ? $aturan->izin_dengan_tugas
                                            : $aturan->izin_tanpa_tugas) / 100),
                                        'libur' => (int) round($dasar * HonorGuruService::PERSEN_LIBUR / 100),
                                        default => $dasar - (int) $get('potongan_terlambat') - (int) $get('potongan_bolos'),
                                    };

                                    $set('total_upah', max(0, $total));
                                })
                        ),
                    Forms\Components\Toggle::make('paid')
                        ->label('Sudah dibayar')
                        ->helperText('Ubah hanya bila status pembayaran memang salah dicatat.'),
                ])
                ->columns(2),
        ]);
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
                Tables\Columns\TextColumn::make('jam.ke')
                    ->label('Jam Ke')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('kelas_tampil')
                    ->label('Kelas')
                    ->state(fn ($record) => $record->namaKelasTampil()),
                Tables\Columns\TextColumn::make('jam_in')
                    ->label('Jam Masuk')
                    ->dateTime('H:i'),
                Tables\Columns\TextColumn::make('status_in')
                    ->label('Status Masuk')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS_MASUK[strtolower((string) $state)] ?? $state)
                    ->color(fn ($state) => self::warnaStatus($state)),
                Tables\Columns\TextColumn::make('jam_out')
                    ->label('Jam Pulang')
                    ->dateTime('H:i'),
                Tables\Columns\TextColumn::make('status_out')
                    ->label('Status Pulang')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS_KELUAR[strtolower((string) $state)] ?? $state)
                    ->color(fn ($state) => self::warnaStatus($state)),
                Tables\Columns\TextColumn::make('durasi_terlambat')
                    ->label('Menit Terlambat')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_bolos')
                    ->label('Menit Bolos')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('upah_dasar')
                    ->label('Upah Dasar')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('potongan_terlambat')
                    ->label('Potongan Terlambat')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('potongan_bolos')
                    ->label('Potongan Bolos')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_upah')
                    ->label('Total Upah')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('paid')
                    ->label('Dibayar')
                    ->boolean(),
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
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari')
                            ->default(now()->startOfMonth()),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai')
                            ->default(now()->endOfMonth()),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['dari'], fn ($q, $v) => $q->whereDate('tanggal_jadwal', '>=', $v))
                        ->when($data['sampai'], fn ($q, $v) => $q->whereDate('tanggal_jadwal', '<=', $v))),
                Tables\Filters\SelectFilter::make('status_in')
                    ->label('Status Masuk')
                    ->options(self::STATUS_MASUK),
                Tables\Filters\TernaryFilter::make('paid')
                    ->label('Pembayaran')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah dibayar')
                    ->falseLabel('Belum dibayar')
                    ->queries(
                        true: fn (Builder $q) => $q->where('paid', 1),
                        false: fn (Builder $q) => $q->where(fn ($w) => $w->whereNull('paid')->orWhere('paid', 0)),
                        blank: fn (Builder $q) => $q,
                    ),
                Tables\Filters\Filter::make('belum_keluar')
                    ->label('Belum presensi keluar')
                    ->toggle()
                    ->query(fn (Builder $q) => $q
                        ->whereNotNull('jam_in')
                        ->whereNull('jam_out')
                        ->where(fn ($w) => $w->whereNull('status_in')->orWhere('status_in', '!=', 'tidak_diikuti'))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('hitungUlang')
                    ->label('Hitung Ulang')
                    ->icon('heroicon-o-calculator')
                    ->color('gray')
                    ->visible(fn ($record) => filled($record->jam_in))
                    ->requiresConfirmation()
                    ->modalHeading('Hitung Ulang Presensi')
                    ->modalDescription(fn ($record) => 'Status, durasi, potongan, dan upah semua jam milik '
                        . ($record->guru?->nama_guru ?? '-') . ' pada ' . $record->tanggal_jadwal->format('d M Y')
                        . ' akan dihitung ulang dari jam masuk dan jam pulang, memakai pengaturan saat ini. '
                        . 'Nilai yang pernah diubah manual pada tanggal itu akan tertimpa.')
                    ->action(function ($record) {
                        if (self::adaBarisDibayar($record)) {
                            Notification::make()
                                ->title('Tidak dapat dihitung ulang')
                                ->body('Ada jam pada tanggal ini yang sudah dibayar. Ubah manual lewat form edit.')
                                ->warning()
                                ->send();
                            return;
                        }

                        app(HonorGuruService::class)->hitungUlangHari(
                            (int) $record->id_guru,
                            $record->tanggal_jadwal->toDateString()
                        );

                        app(TunjanganGuruService::class)->sinkronHarian(
                            (int) $record->id_guru,
                            $record->tanggal_jadwal->toDateString(),
                            true
                        );

                        Notification::make()
                            ->title('Berhasil dihitung ulang')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => ! $record->paid),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hitungUlang')
                        ->label('Hitung Ulang')
                        ->icon('heroicon-o-calculator')
                        ->requiresConfirmation()
                        ->modalDescription('Setiap guru dan tanggal yang dipilih akan dihitung ulang dari jam masuk dan jam pulang. Nilai yang pernah diubah manual akan tertimpa, dan tanggal yang berisi jam sudah dibayar dilewati.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $service = app(HonorGuruService::class);
                            $dihitung = 0;
                            $dilewati = 0;

                            $pasangan = $records
                                ->filter(fn ($r) => filled($r->jam_in))
                                ->unique(fn ($r) => $r->id_guru . '|' . $r->tanggal_jadwal->toDateString());

                            foreach ($pasangan as $r) {
                                if (self::adaBarisDibayar($r)) {
                                    $dilewati++;
                                    continue;
                                }

                                $service->hitungUlangHari((int) $r->id_guru, $r->tanggal_jadwal->toDateString());
                                app(TunjanganGuruService::class)->sinkronHarian(
                                    (int) $r->id_guru,
                                    $r->tanggal_jadwal->toDateString(),
                                    true
                                );
                                $dihitung++;
                            }

                            Notification::make()
                                ->title("{$dihitung} guru-tanggal dihitung ulang")
                                ->body($dilewati > 0 ? "{$dilewati} dilewati karena sudah ada jam yang dibayar." : null)
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            if ($records->contains(fn ($r) => $r->paid)) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus')
                                    ->body('Sebagian baris yang dipilih sudah dibayar.')
                                    ->danger()
                                    ->send();
                                $action->halt();
                            }
                        }),
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

    protected static function warnaStatus(?string $state): string
    {
        return match (strtolower((string) $state)) {
            'hadir', 'pulang' => 'success',
            'terlambat', 'tidak_diikuti' => 'warning',
            'bolos' => 'danger',
            'izin', 'sakit' => 'info',
            default => 'gray',
        };
    }

    protected static function adaBarisDibayar(PresensiGuru $record): bool
    {
        return PresensiGuru::where('id_guru', $record->id_guru)
            ->whereDate('tanggal_jadwal', $record->tanggal_jadwal)
            ->where('paid', 1)
            ->exists();
    }
}