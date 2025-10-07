<?php

namespace Tests\Feature;

use App\Jobs\ProcessReservation;
use App\Models\Customer;
use App\Models\Room;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_customer_can_request_a_reservation_and_a_processing_job_is_dispatched(): void
    {
        Queue::fake();

        /** @var \App\Models\Customer $customer */
        $customer = Customer::factory()->create();
        $room = Room::factory()->create(['capacity' => 10]);

        $reservationData = [
            'room_id' => $room->id,
            'start_time' => now()->addDay()->hour(10)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDay()->hour(11)->format('Y-m-d H:i:s'),
            'attendees' => 5,
        ];

        $response = $this->actingAs($customer)->postJson('/api/reservations', $reservationData);

        $response->assertStatus(202);

        $response->assertJsonFragment(['status' => 'PENDING']);

        $this->assertDatabaseHas('reservations', [
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'status' => 'PENDING',
        ]);

        Queue::assertPushed(ProcessReservation::class);
    }
}
