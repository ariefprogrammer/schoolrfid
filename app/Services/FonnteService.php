<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected function token(): ?string
    {
        // Prioritaskan token per-sekolah dari tbl_pengaturan,
        // fallback ke .env untuk instalasi single-tenant
        return Pengaturan::first()?->fonnte_token ?? config('services.fonnte.token');
    }

    /**
     * Kirim pesan WhatsApp via Fonnte.
     * $target bisa nomor tunggal atau array nomor.
     */
    public function send(string|array $target, string $message): bool
    {
        $token = $this->token();

        if (!$token) {
            Log::warning('Token Fonnte tidak tersedia, notifikasi WA dilewati.');
            return false;
        }

        if (empty($target)) {
            return false;
        }

        $nomor = is_array($target) ? implode(',', $target) : $target;

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post(config('services.fonnte.base_url') . '/send', [
                'target' => $nomor,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->failed()) {
                Log::error('Gagal kirim WA via Fonnte: ' . $response->body());
                return false;
            }

            Log::info('WA terkirim ke ' . $nomor);
            return true;

        } catch (\Exception $e) {
            Log::error('Exception saat kirim WA Fonnte: ' . $e->getMessage());
            return false;
        }
    }
}