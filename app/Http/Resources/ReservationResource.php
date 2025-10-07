<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room' => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'capacity' => $this->room->capacity,
            ],
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ],
            'start_time' => $this->start_time->toDateTimeString(),
            'end_time' => $this->end_time->toDateTimeString(),
            'attendees' => $this->attendees,
            'notes' => $this->notes,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at ? $this->cancelled_at->toDateTimeString() : '',
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
