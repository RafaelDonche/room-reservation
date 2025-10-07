<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Rules\ValidReservationDuration;
use Tests\TestCase;

class ReservationModelTest extends TestCase
{
    public function test_it_passes_when_duration_is_exactly_30_minutes(): void
    {
        $startTime = '2025-10-10 10:00:00';
        $endTime = '2025-10-10 10:30:00';
        $rule = new ValidReservationDuration($startTime);

        $this->assertTrue(
            validator(['end_time' => $endTime], ['end_time' => $rule])->passes()
        );
    }

    public function test_it_fails_when_duration_is_less_than_30_minutes(): void
    {
        $startTime = '2025-10-10 10:00:00';
        $endTime = '2025-10-10 10:29:59';
        $rule = new ValidReservationDuration($startTime);

        $this->assertFalse(
            validator(['end_time' => $endTime], ['end_time' => $rule])->passes()
        );
    }

    public function test_it_passes_when_duration_is_exactly_8_hours(): void
    {
        $startTime = '2025-10-10 10:00:00';
        $endTime = '2025-10-10 18:00:00';
        $rule = new ValidReservationDuration($startTime);

        $this->assertTrue(
            validator(['end_time' => $endTime], ['end_time' => $rule])->passes()
        );
    }

    public function test_it_fails_when_duration_is_more_than_8_hours(): void
    {
        $startTime = '2025-10-10 10:00:00';
        $endTime = '2025-10-10 18:00:01';
        $rule = new ValidReservationDuration($startTime);

        $this->assertFalse(
            validator(['end_time' => $endTime], ['end_time' => $rule])->passes()
        );
    }

    public function test_a_reservation_is_cancellable_if_it_is_more_than_2_hours_in_the_future(): void
    {
        $reservation = Reservation::factory()->make([
            'start_time' => now()->addHours(3),
        ]);

        $reservation->validatesCancellation();

        $this->assertTrue(true);
    }

    public function test_a_reservation_is_not_cancellable_if_it_is_less_than_2_hours_away(): void
    {
        $reservation = Reservation::factory()->make([
            'start_time' => now()->addMinutes(119),
        ]);

        $this->expectException(\Exception::class);

        $reservation->validatesCancellation();
    }

    public function test_a_reservation_is_not_cancellable_if_it_is_in_the_past(): void
    {
        $reservation = Reservation::factory()->make([
            'start_time' => now()->subHour(),
        ]);

        $this->expectException(\Exception::class);

        $reservation->validatesCancellation();
    }
}
