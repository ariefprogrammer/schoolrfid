<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Telegram Webhook Triggered', $request->all());
        $data = $request->all();

        // Cek apakah ini pesan teks masuk
        if (isset($data['message']['text'])) {
            $messageText = $data['message']['text'];
            $chatId = $data['message']['chat']['id'];

            // Cek apakah pesan dimulai dengan 'wali '
            if (preg_match('/^wali\s+(\d+)/i', $messageText, $matches)) {
                $rfid = $matches[1];

                // Cari siswa berdasarkan RFID
                $siswa = Siswa::where('rfid', $rfid)->first();

                if ($siswa) {
                    // Update telepon_wali dengan chat_id
                    $siswa->telepon_wali = $chatId;
                    $siswa->save();

                    // Kirim pesan sukses
                    $this->sendTelegramMessage($chatId, "Selamat anda berhasil terdaftar sebagai wali dari {$siswa->nama_siswa}.");
                } else {
                    // Kirim pesan gagal
                    $this->sendTelegramMessage($chatId, "RFID tidak ditemukan. Periksa kembali nomor RFID yang anda masukkan.");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendTelegramMessage($chatId, $message)
    {
        $botToken = env('TELEGRAM_BOT_TOKEN'); // simpan di .env

        Http::get("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message
        ]);
    }
}
