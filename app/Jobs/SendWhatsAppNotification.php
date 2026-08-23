<?php
namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // detik, jeda sebelum retry kalau gagal

    public function __construct(
        public string|array $target,
        public string $message
    ) {}

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