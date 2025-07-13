<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Absensi;
use App\ViewModels\RekapSiswaRow;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapSiswaExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use App\Models\User;

class RekapSiswa extends Page
{

    public static function canAccess(array $parameters = []): bool
    {
        // Hanya user dengan role 'admin' yang bisa mengakses halaman ini
        return auth()->user()->role === 'admin';
    }
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Rekap Siswa';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 23;
    protected static ?string $title = 'Laporan Rekap Siswa';
    protected static string $view = 'filament.pages.rekap-siswa';

    public $dateStart;
    public $dateEnd;
    public $kelasId;

    public array $rekapLaporan = [];

    protected function getFormSchema(): array
    {
        return [
            Grid::make(3)->schema([
                DatePicker::make('dateStart')
                    ->label('Tanggal Mulai')
                    ->required(),

                DatePicker::make('dateEnd')
                    ->label('Tanggal Selesai')
                    ->required(),

                Select::make('kelasId')
                    ->label('Kelas')
                    ->options(\App\Models\Kelas::all()->pluck('nama_kelas', 'id'))
                    ->required(),
            ]),
        ];
    }

    public function tampilkan()
    {
        $this->validate();

        $absensi = Absensi::with('siswa.kelas')
            ->whereBetween('date_time', [$this->dateStart, $this->dateEnd])
            ->where('id_kelas', $this->kelasId)
            ->get();

        $siswaPerKelas = Siswa::where('id_kelas', $this->kelasId)->get();
        $rekap = [];

        foreach ($siswaPerKelas as $siswa) {
            $data = $absensi->where('rfid', $siswa->rfid);

            $rekap[] = [
                'rfid' => $siswa->rfid,
                'nama' => $siswa->nama_siswa, // sesuaikan field nama
                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                'hadir' => $data->where('status', 'hadir')->count(),
                'terlambat' => $data->where('status', 'terlambat')->count(),
                'alpa' => $data->where('status', 'alpa')->count(),
                'pulang' => $data->where('status', 'pulang')->count(),
                'bolos' => $data->where('status', 'bolos')->count(),
                'izin' => $data->where('status', 'izin')->count(),
                'sakit' => $data->where('status', 'sakit')->count(),
            ];

        }

        $this->rekapLaporan = $rekap;
    }

    public function exportExcel()
    {
        session()->put('rekap_export_data', $this->rekapLaporan);

        return redirect()->route('rekap-siswa.export');
    }

}
