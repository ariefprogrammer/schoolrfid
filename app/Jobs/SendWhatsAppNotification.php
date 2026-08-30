<?php
namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100;      // dinaikkan jauh — rate limit release dihitung sebagai attempt
    public int $backoff = 30;     // ini tetap dipakai kalau job GAGAL beneran (bukan sekadar rate-limited)
    public int $maxExceptions = 3; // opsional: batasi berapa kali boleh gagal KARENA ERROR sungguhan sebelum menyerah

    public function __construct(
        public string|array $target,
        public string $message
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('whatsapp')];
    }

    public function handle(FonnteService $fonnte): void
    {
        $sent = $fonnte->send($this->target, $this->message);

        if (!$sent) {
            // Lempar exception supaya job dianggap gagal dan bisa di-retry
            throw new \RuntimeException('Gagal mengirim WhatsApp ke: ' . (is_array($this->target) ? implode(',', $this->target) : $this->target));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job WhatsApp gagal permanen: ' . $exception->getMessage());
    }
}