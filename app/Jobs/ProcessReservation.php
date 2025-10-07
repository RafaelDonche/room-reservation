<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(ReservationService $reservationService): void
    {
        // O observer cuidará de disparar o webhook quando o status for atualizado
        $reservationService->process($this->reservation);
    }
}
