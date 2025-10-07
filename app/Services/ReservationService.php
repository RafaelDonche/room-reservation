<?php

namespace App\Services;

use App\Jobs\ProcessReservation;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReservationService
{
    /**
     * Cria uma solicitação de reserva e despacha o job de processamento.
     */
    public function create(array $data, Customer $customer): Reservation
    {
        $reservation = Reservation::create([
            'customer_id' => $customer->id,
            'status' => 'PENDING',
            ...$data,
        ]);

        ProcessReservation::dispatch($reservation);

        return $reservation;
    }

    /**
     * Executa as regras de negócio para confirmar ou cancelar uma reserva pendente.
     * Esta é a lógica que estava dentro do handle() do job.
     */
    public function process(Reservation $reservation): Reservation
    {
        // Regra 3: Capacidade
        if ($reservation->attendees > $reservation->room->capacity) {
            return $this->fail($reservation, 'O número de participantes excede a capacidade da sala.');
        }

        // Regra 2: Conflito de horário
        $isConflict = Reservation::where('room_id', $reservation->room_id)
            ->where('status', 'CONFIRMED')
            ->where('start_time', '<', $reservation->end_time)
            ->where('end_time', '>', $reservation->start_time)
            ->where('id', '!=', $reservation->id) // Evita que a própria reserva conflite consigo mesma
            ->exists();

        if ($isConflict) {
            return $this->fail($reservation, 'Conflito de horário. A sala já está reservada neste intervalo.');
        }

        // Regra 4: Limite diário por cliente
        $dailyReservationsCount = Reservation::where('customer_id', $reservation->customer_id)
            ->where('status', 'CONFIRMED')
            ->whereDate('start_time', Carbon::parse($reservation->start_time)->toDateString())
            ->count();

        if ($dailyReservationsCount >= 2) {
            return $this->fail($reservation, 'Limite diário de 2 reservas confirmadas por cliente atingido.');
        }

        // Se tudo passou, confirma a reserva.
        return $this->confirm($reservation);
    }

    private function confirm(Reservation $reservation): Reservation
    {
        $reservation->update([
            'status' => 'CONFIRMED',
            'confirmation_code' => Str::uuid()->toString(),
        ]);
        return $reservation;
    }

    private function fail(Reservation $reservation, string $reason): Reservation
    {
        $reservation->update([
            'status' => 'CANCELLED',
            'cancellation_reason' => $reason,
        ]);
        return $reservation;
    }

    public function cancel(Reservation $reservation): Reservation
    {
        $reservation->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelado pelo cliente.',
        ]);

        return $reservation;
    }
}
