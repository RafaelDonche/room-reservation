<?php

namespace Tests\Feature; // <<-- Note o namespace atualizado para Feature

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationObserverTest extends TestCase
{
    use RefreshDatabase; // Essencial: Prepara o banco de dados de teste para nós

    #[Test]
    public function it_invalidates_cache_when_status_is_changed_to_a_terminal_state(): void
    {
        Http::fake();

        Cache::shouldReceive('tags')
            ->once()
            ->with(Mockery::on(function ($argument) {
                return str_starts_with($argument, 'room-availability-');
            }))
            ->andReturnSelf()
            ->shouldReceive('flush');

        $room = Room::factory()->create();
        $customer = Customer::factory()->create();
        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'start_time' => now()->addDay()->addHours(1),
            'end_time' => now()->addDay()->addHours(2),
            'attendees' => $room->capacity,
            'status' => 'PENDING',
        ]);

        $reservation->status = 'CONFIRMED';
        $reservation->save();

        Http::assertSentCount(1);

        // Inspeciona a chamada para a reserva confirmada
        Http::assertSent(function (Request $request) use ($reservation) {
            // Verifica se a carga (payload) contém os dados corretos
            return $request->url() === config('app.webhook_url') &&
                   $request['event'] === 'reservation.confirmed' &&
                   $request['data']['id'] === $reservation->id;
        });
    }

    #[Test]
    public function it_does_not_invalidate_cache_when_reservation_is_created_with_pending_status(): void
    {
        Cache::shouldReceive('tags')->never();

        $room = Room::factory()->create();
        $customer = Customer::factory()->create();
        Reservation::factory()->create([
            'room_id' => $room->id,
            'customer_id' => $customer->id,
            'start_time' => now()->addDay()->addHours(1),
            'end_time' => now()->addDay()->addHours(2),
            'attendees' => $room->capacity,
            'status' => 'PENDING',
        ]);

        $this->assertTrue(true);
    }
}
