<?php

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Pages\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SiswasImport;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('Import Siswa')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('file')
                        ->label('Upload File Excel')
                        ->required()
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->storeFiles() // WAJIB: Filament akan menyimpan file secara otomatis
                        ->disk('local') // File akan disimpan di storage/app/
                        ->directory('imports') // Direktori di dalam disk 'local', jadi storage/app/imports
                        ->preserveFilenames(),

            ])
            ->action(function (array $data): void {
                    $filePathOnDisk = $data['file'];
                    $absoluteFilePath = Storage::disk('local')->path($filePathOnDisk);

                    if (!Storage::disk('local')->exists($filePathOnDisk)) {
                        Notification::make()
                            ->title('Gagal Upload File')
                            ->danger()
                            ->body(new HtmlString("File tidak ditemukan setelah diunggah. Ini biasanya karena **izin folder (permissions)**. <br> Pastikan folder `storage` dan sub-foldernya memiliki izin tulis untuk server web Anda. <br> Path yang diperiksa: <code>{$absoluteFilePath}</code>"))
                            ->persistent()
                            ->send();
                        \Log::error("File tidak ditemukan setelah diunggah oleh Filament FileUpload: {$absoluteFilePath}");
                        return;
                    }

                    try {
                        Excel::import(new SiswasImport, $filePathOnDisk, 'local');

                        // Hapus file setelah diimpor
                        Storage::disk('local')->delete($filePathOnDisk);

                        Notification::make()
                            ->title('Import Berhasil')
                            ->success()
                            ->body('Data siswa berhasil diimpor.')
                            ->send();

                    } catch (ValidationException $e) {
                        $failures = $e->failures();
                        $hasUniqueError = false;
                        $messages = [];

                        foreach ($failures as $failure) {
                            $attribute = $failure->attribute();
                            $errorMessages = $failure->errors();

                            foreach ($errorMessages as $msg) {
                                // Cek apakah pesan error terkait dengan unique constraint
                                if (Str::contains(strtolower($msg), 'has already been taken') && ($attribute === 'nis' || $attribute === 'rfid')) {
                                    $hasUniqueError = true;
                                }
                                $messages[] = "Baris " . $failure->row() . " (Kolom: " . $attribute . "): " . $msg;
                            }
                        }

                        // Jika ada setidaknya satu error keunikan untuk NIS atau RFID
                        if ($hasUniqueError) {
                            Notification::make()
                                ->title('Gagal Import: Data Duplikat')
                                ->danger()
                                ->body('Import dibatalkan karena ada data NIS atau RFID yang sudah ada di sistem. Mohon periksa kembali file Excel Anda.')
                                ->persistent()
                                ->send();
                        } else {
                            // Jika ada error validasi lain (bukan unique NIS/RFID)
                            Notification::make()
                                ->title('Gagal Import: Ada Kesalahan Validasi')
                                ->danger()
                                ->body(new HtmlString("Beberapa baris tidak berhasil diimpor karena kesalahan validasi:<br><ul><li>" . implode("</li><li>", $messages) . "</li></ul>"))
                                ->persistent()
                                ->send();
                        }

                        // Hapus file bahkan jika ada kegagalan validasi
                        Storage::disk('local')->delete($filePathOnDisk);

                    } catch (\Exception $e) {
                        // Ini akan menangkap kesalahan SQLSTATE jika validasi Maatwebsite tidak menangkapnya
                        // Kita bisa memeriksa pesan error SQL untuk memberikan pesan yang lebih spesifik
                        $errorMessage = $e->getMessage();
                        $displayMessage = "Terjadi kesalahan yang tidak terduga saat impor.";

                        if (Str::contains($errorMessage, 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry')) {
                            // Pesan yang lebih spesifik untuk duplikasi dari database
                            $displayMessage = 'Gagal import, terdapat duplikasi data NIS atau RFID. Silakan periksa file Excel Anda.';
                        } else {
                            // Tampilkan pesan error umum jika bukan duplikasi
                            $displayMessage = "Terjadi kesalahan: " . $errorMessage;
                        }

                        Notification::make()
                            ->title('Gagal Import')
                            ->danger()
                            ->body($displayMessage)
                            ->persistent()
                            ->send();

                        if (isset($filePathOnDisk) && Storage::disk('local')->exists($filePathOnDisk)) {
                            Storage::disk('local')->delete($filePathOnDisk);
                        }
                    }
                }),


        ];
    }
}
