<x-filament-panels::page>
    <div class="fi-page-content-wrapper p-4 md:p-6 lg:p-8">
        {{-- Statistik Ringkasan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
            {{-- Card Jumlah Siswa --}}
            <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="relative px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="fi-stats-overview-widget-card-icon-container flex items-center justify-center rounded-full bg-primary-500/10 p-3 text-primary-500 dark:bg-primary-500/20">
                            <x-heroicon-o-users class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Siswa</h3>
                            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ $totalSiswa }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Jumlah Guru --}}
            <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="relative px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="fi-stats-overview-widget-card-icon-container flex items-center justify-center rounded-full bg-emerald-500/10 p-3 text-emerald-500 dark:bg-emerald-500/20">
                            <x-heroicon-o-user-group class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Guru</h3>
                            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ $totalGuru }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Jumlah Kelas --}}
            <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="relative px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="fi-stats-overview-widget-card-icon-container flex items-center justify-center rounded-full bg-purple-500/10 p-3 text-purple-500 dark:bg-purple-500/20">
                            <x-heroicon-o-building-library class="h-6 w-6" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Kelas</h3>
                            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ $totalKelas }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Jadwal Guru Hari Ini per Kelas --}}
        <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 p-6">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Jadwal Guru Hari Ini</h3>
            @if (empty($todaySchedules))
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada jadwal guru untuk hari ini.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6"> {{-- Grid untuk mengatur jadwal per kelas --}}
                    @foreach ($todaySchedules as $className => $schedules)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                            <h4 class="text-md font-semibold text-gray-950 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">Jadwal Kelas {{ $className }}</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-3 py-2">Waktu</th>
                                            <th scope="col" class="px-3 py-2">Mapel</th>
                                            <th scope="col" class="px-3 py-2 rounded-tr-lg">Guru</th>
                                            <th scope="col" class="px-3 py-2">In</th>
                                            <th scope="col" class="px-3 py-2">Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($schedules as $schedule)
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td class="px-3 py-2">
                                                    {{ \Carbon\Carbon::parse($schedule['jam']['jam_mulai'])->format('H:i') ?? '-' }} - {{ \Carbon\Carbon::parse($schedule['jam']['jam_selesai'])->format('H:i') ?? '-' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $schedule['mapel']['kode'] ?? '-' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $schedule['guru']['nama_guru'] ?? '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @php
                                                        $statusIn = $schedule['status_in'] ?? null;
                                                        $jamIn = $schedule['jam_in'] ?? null;
                                                        $titleIn = '';
                                                        if ($statusIn === 'hadir') {
                                                            $titleIn = 'Hadir';
                                                            if ($jamIn) {
                                                                $parsedTime = \Carbon\Carbon::parse($jamIn)->format('H:i');
                                                                $titleIn .= ' (' . $parsedTime . ')';
                                                            }
                                                        } elseif ($statusIn === 'terlambat') {
                                                            $titleIn = 'Terlambat';
                                                            if ($jamIn) {
                                                                $parsedTime = \Carbon\Carbon::parse($jamIn)->format('H:i');
                                                                $titleIn .= ' (' . $parsedTime . ')';
                                                            }
                                                        } elseif ($statusIn === 'unassign' || is_null($statusIn)) {
                                                            $titleIn = 'Belum Presensi';
                                                        }
                                                    @endphp
                                                    @if ($statusIn === 'hadir')
                                                        <x-heroicon-o-check-circle class="h-6 w-6 text-green-500 mx-auto" title="{{ $titleIn }}" />
                                                    @elseif ($statusIn === 'terlambat')
                                                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-500 mx-auto" title="{{ $titleIn }}" />
                                                    @elseif ($statusIn === 'unassign' || is_null($statusIn))
                                                        <x-heroicon-o-exclamation-circle class="h-6 w-6 text-amber-500 mx-auto" title="{{ $titleIn }}" />
                                                    @else
                                                        {{ $statusIn }}
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    @php
                                                        $statusOut = $schedule['status_out'] ?? null;
                                                        $jamOut = $schedule['jam_out'] ?? null;
                                                        $titleOut = '';
                                                        if ($statusOut === 'pulang') {
                                                            $titleOut = 'Pulang';
                                                            if ($jamOut) {
                                                                $parsedTime = \Carbon\Carbon::parse($jamOut)->format('H:i');
                                                                $titleOut .= ' (' . $parsedTime . ')';
                                                            }
                                                        } elseif ($statusOut === 'bolos') {
                                                            $titleOut = 'Bolos';
                                                            if ($jamOut) {
                                                                $parsedTime = \Carbon\Carbon::parse($jamOut)->format('H:i');
                                                                $titleOut .= ' (' . $parsedTime . ')';
                                                            }
                                                        } elseif ($statusOut === 'unassign' || is_null($statusOut)) {
                                                            $titleOut = 'Belum Presensi';
                                                        }
                                                    @endphp
                                                    @if ($statusOut === 'pulang')
                                                        <x-heroicon-o-check-circle class="h-6 w-6 text-green-500 mx-auto" title="{{ $titleOut }}" />
                                                    @elseif ($statusOut === 'bolos')
                                                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-500 mx-auto" title="{{ $titleOut }}" />
                                                    @elseif ($statusOut === 'unassign' || is_null($statusOut))
                                                        <x-heroicon-o-exclamation-circle class="h-6 w-6 text-amber-500 mx-auto" title="{{ $titleOut }}" />
                                                    @else
                                                        {{ $statusOut }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Grafik Traffic Kehadiran Siswa (MASUK) --}}
        <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 p-6">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Traffic Kehadiran Siswa (Masuk) Hari Ini</h3>
            <div style="height: 250px;">
                <canvas
                    id="attendanceInChart"
                    data-labels="{{ json_encode($trafficChartLabels) }}"
                    data-counts="{{ json_encode($trafficChartData) }}"
                    style="width: 100%; height: 100%;"
                ></canvas>
            </div>
            @if (empty($trafficChartLabels))
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada data presensi masuk siswa untuk hari ini.</p>
            @endif
        </div>

        {{-- Grafik Traffic Kehadiran Siswa (KELUAR) --}}
        <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 p-6">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Traffic Kehadiran Siswa (Keluar) Hari Ini</h3>
            <div style="height: 250px;">
                <canvas
                    id="attendanceOutChart"
                    data-labels="{{ json_encode($trafficChartOutLabels) }}"
                    data-counts="{{ json_encode($trafficChartOutData) }}"
                    style="width: 100%; height: 100%;"
                ></canvas>
            </div>
            @if (empty($trafficChartOutLabels))
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada data presensi keluar siswa untuk hari ini.</p>
            @endif
        </div>

        {{-- Pie Chart Status Kehadiran Siswa Hari Ini --}}
        <div class="fi-card group relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6 p-6">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Status Kehadiran Siswa Hari Ini</h3>
            <div style="height: 300px; display: flex; justify-content: center; align-items: center;"> {{-- Flexbox untuk sentrisitas --}}
                <canvas
                    id="attendanceStatusPieChart"
                    data-labels="{{ json_encode($statusPieChartLabels) }}"
                    data-data="{{ json_encode($statusPieChartData) }}"
                    data-colors="{{ json_encode($statusPieChartColors) }}"
                    style="max-width: 100%; max-height: 100%;" {{-- Pastikan responsif di dalam flex container --}}
                ></canvas>
            </div>
            @if (empty($statusPieChartLabels))
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada data status kehadiran siswa untuk hari ini.</p>
            @endif
        </div>
        
        
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- Fungsi untuk inisialisasi Line Chart ---
                function initializeLineChart(canvasId, labelPrefix, dataLabels, dataCounts, borderColor, backgroundColor) {
                    const chartCanvas = document.getElementById(canvasId);
                    if (chartCanvas) {
                        const labels = JSON.parse(chartCanvas.dataset.labels);
                        const counts = JSON.parse(chartCanvas.dataset.counts);

                        if (labels && labels.length > 0 && counts && counts.length > 0) {
                            const ctx = chartCanvas.getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: labelPrefix,
                                        data: counts,
                                        backgroundColor: backgroundColor,
                                        borderColor: borderColor,
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.3,
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            title: { display: true, text: 'Jumlah Siswa' },
                                            ticks: { stepSize: 1 }
                                        },
                                        x: {
                                            title: { display: true, text: 'Waktu (Jam:Menit)' }
                                        }
                                    },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { mode: 'index', intersect: false }
                                    }
                                }
                            });
                        } else {
                            console.warn(`Chart.js (${canvasId}): Tidak ada data untuk inisialisasi grafik.`);
                        }
                    } else {
                        console.warn(`Chart.js (${canvasId}): Elemen canvas tidak ditemukan.`);
                    }
                }

                // --- Inisialisasi Grafik Presensi MASUK ---
                initializeLineChart(
                    'attendanceInChart',
                    'Jumlah Presensi Masuk',
                    '{{ json_encode($trafficChartLabels) }}',
                    '{{ json_encode($trafficChartData) }}',
                    'rgb(59, 130, 246)',
                    'rgba(59, 130, 246, 0.5)'
                );

                // --- Inisialisasi Grafik Presensi KELUAR ---
                initializeLineChart(
                    'attendanceOutChart',
                    'Jumlah Presensi Keluar',
                    '{{ json_encode($trafficChartOutLabels) }}',
                    '{{ json_encode($trafficChartOutData) }}',
                    'rgb(239, 68, 68)',
                    'rgba(239, 68, 68, 0.5)'
                );

                // --- Grafik Pie Chart Status Kehadiran ---
                const statusPieChartCanvas = document.getElementById('attendanceStatusPieChart');
                if (statusPieChartCanvas) {
                    const pieLabels = JSON.parse(statusPieChartCanvas.dataset.labels);
                    const pieData = JSON.parse(statusPieChartCanvas.dataset.data);
                    const pieColors = JSON.parse(statusPieChartCanvas.dataset.colors);

                    if (pieLabels && pieLabels.length > 0 && pieData && pieData.length > 0) {
                        const pieCtx = statusPieChartCanvas.getContext('2d');
                        new Chart(pieCtx, {
                            type: 'pie', // Jenis grafik: pie
                            data: {
                                labels: pieLabels, // Label untuk setiap segmen
                                datasets: [{
                                    label: 'Jumlah Siswa',
                                    data: pieData, // Data untuk setiap segmen
                                    backgroundColor: pieColors, // Warna untuk setiap segmen
                                    hoverOffset: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false, // Penting untuk kontrol ukuran dalam div
                                plugins: {
                                    legend: {
                                        position: 'right', // Posisi legend di kanan
                                        labels: {
                                            boxWidth: 20,
                                            padding: 15
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                // Tampilkan persentase di tooltip
                                                let sum = context.dataset.data.reduce((a, b) => a + b, 0);
                                                let percentage = (context.raw * 100 / sum).toFixed(1) + '%';
                                                return label + context.raw + ' (' + percentage + ')';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        console.warn('Chart.js (Status Pie): Tidak ada data untuk inisialisasi grafik.');
                    }
                } else {
                    console.warn('Chart.js (Status Pie): Elemen canvas tidak ditemukan.');
                }
            });
        </script>
    @endpush
</x-filament-panels::page>
