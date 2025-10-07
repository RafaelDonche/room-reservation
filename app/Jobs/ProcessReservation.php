<?php

namespace App\Jobs;

use App\Models\Reservation;
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
    public function handle(): void
    {
        DB::transaction(function () {
            $reservation = Reservation::lockForUpdate()->findOrFail($this->reservation->id);

            // A capacidade da sala não pode ser excedida
            if ($reservation->attendees > $reservation->room->capacity) {
                $this->failReservation($reservation, 'O número de participantes excede a capacidade da sala.');
                return;
            }

            // Não pode haver choque de horário com reservas confirmadas
            $isConflict = Reservation::where('room_id', $reservation->room_id)
                ->where('status', 'CONFIRMED')
                ->where(function ($query) use ($reservation) {
                    $query->where(function ($q) use ($reservation) {
                        $q->where('start_time', '<', $reservation->end_time)
                        ->where('end_time', '>', $reservation->start_time);
                    });
                })
                ->exists();

            if ($isConflict) {
                $this->failReservation($reservation, 'Conflito de horário. A sala já está reservada neste intervalo.');
                return;
            }

            // Limite de 2 reservas confirmadas por dia por cliente
            $dailyReservationsCount = Reservation::where('customer_id', $reservation->customer_id)
                ->where('status', 'CONFIRMED')
                ->whereDate('start_time', Carbon::parse($reservation->start_time)->toDateString())
                ->count();

            if ($dailyReservationsCount >= 2) {
                $this->failReservation($reservation, 'Limite diário de 2 reservas confirmadas por cliente atingido.');
                return;
            }

            // Se todas as regras passaram, confirma a reserva
            $this->confirmReservation($reservation);
        });
    }

    private function confirmReservation(Reservation $reservation): void
    {
        $reservation->update([
            'status' => 'CONFIRMED',
            'confirmation_code' => Str::uuid()->toString(),
        ]);
    }

    private function failReservation(Reservation $reservation, string $reason): void
    {
        $reservation->update([
            'status' => 'CANCELLED',
            'cancellation_reason' => $reason,
        ]);
    }
}
