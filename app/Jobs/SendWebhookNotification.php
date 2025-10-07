<?php

namespace App\Jobs;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Aumenta o tempo de tentativa para 5 minutos
    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public Reservation $reservation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('app.webhook_url');
        Log::info($webhookUrl);

        if (!$webhookUrl) {
            Log::warning('WEBHOOK_URL não está configurada. Pulando notificação.');
            return;
        }

        // $this->reservation->refresh();

        Http::post($webhookUrl, [
            'event' => 'reservation.' . strtolower($this->reservation->status),
            'data' => $this->reservation->toArray(),
        ]);
    }
}
