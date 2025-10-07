<?php

namespace Tests\Feature;

use App\Jobs\ProcessReservation;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prevents_double_booking_and_sends_webhooks(): void
    {
        Http::fake();

        $customer = Customer::factory()->create();
        $room = Room::factory()->create();
        $startTime = now()->addDay()->hour(14);
        $endTime = now()->addDay()->hour(15);

        $reservation1 = Reservation::factory()->create([
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'attendees' => $room->capacity,
            'status' => 'PENDING',
        ]);
        $reservation2 = Reservation::factory()->create([
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'attendees' => $room->capacity,
            'status' => 'PENDING',
        ]);

        $job1 = new ProcessReservation($reservation1);
        $job2 = new ProcessReservation($reservation2);
        dispatch_sync($job1);
        dispatch_sync($job2);

        $this->assertDatabaseHas('reservations', ['id' => $reservation1->id, 'status' => 'CONFIRMED']);
        $this->assertDatabaseHas('reservations', ['id' => $reservation2->id, 'status' => 'CANCELLED']);

        Http::assertSentCount(2);

        // Inspeciona a chamada para a reserva confirmada
        Http::assertSent(function (Request $request) use ($reservation1) {
            // Verifica se a carga (payload) contém os dados corretos
            return $request->url() === config('app.webhook_url') &&
                   $request['event'] === 'reservation.confirmed' &&
                   $request['data']['id'] === $reservation1->id;
        });

        // Inspeciona a chamada para a reserva cancelada
        Http::assertSent(function (Request $request) use ($reservation2) {
            return $request->url() === config('app.webhook_url') &&
                   $request['event'] === 'reservation.cancelled' &&
                   $request['data']['id'] === $reservation2->id;
        });
    }
}
