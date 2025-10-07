<?php

namespace App\Observers;

use App\Jobs\SendWebhookNotification;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;

class ReservationObserver
{
    /**
     * Handle the Reservation "created" event.
     */
    public function saved(Reservation $reservation): void
    {
        if ($reservation->wasChanged('status') && in_array($reservation->status, ['CONFIRMED', 'CANCELLED'])) {
            Cache::tags("room-availability-{$reservation->room_id}")->flush(); // limpa o cache
            SendWebhookNotification::dispatch($reservation); // envia a notificação ao webhook
        }
    }

    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "deleted" event.
     */
    public function deleted(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "restored" event.
     */
    public function restored(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "force deleted" event.
     */
    public function forceDeleted(Reservation $reservation): void
    {
        //
    }
}
