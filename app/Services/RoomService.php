<?php

namespace App\Services;

use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RoomService
{
    public function getPaginatedAvailability($filters, Room $room, $url, $query)
    {
        $from = Carbon::parse($filters['from']);
        $to = Carbon::parse($filters['to']);
        $perPage = !empty($filters['per_page']) ? $filters['per_page'] : 15;
        $page = !empty($filters['page']) ? $filters['page'] : 1;

        $cacheTag = "room-availability-{$room->id}";
        $cacheKey = "availability.{$room->id}.from_{$from->toDateString()}.to_{$to->toDateString()}";

        $allFreeSlots = Cache::tags($cacheTag)->remember($cacheKey, now()->addHour(), function () use ($room, $from, $to) {
            $reservations = $room->reservations()
                ->where('status', 'CONFIRMED')
                ->where(function ($query) use ($from, $to) {
                    $query->where('start_time', '<', $to)
                          ->where('end_time', '>', $from);
                })
                ->orderBy('start_time')
                ->get();

            $slots = [];
            $cursor = $from->copy();

            foreach ($reservations as $reservation) {
                if ($cursor->lt($reservation->start_time)) {
                    $slots[] = [
                        'start' => $cursor->toDateTimeString(),
                        'end' => $reservation->start_time->toDateTimeString(),
                    ];
                }
                $cursor = $reservation->end_time;
            }

            if ($cursor->lt($to)) {
                $slots[] = [
                    'start' => $cursor->toDateTimeString(),
                    'end' => $to->toDateTimeString(),
                ];
            }

            return $slots;
        });

        $slotsCollection = collect($allFreeSlots);

        $paginatedSlots = new LengthAwarePaginator(
            $slotsCollection->forPage($page, $perPage)->values(),
            $slotsCollection->count(),
            $perPage,
            $page,
            ['path' => $url, 'query' => $query]
        );

        return $paginatedSlots;
    }
}
