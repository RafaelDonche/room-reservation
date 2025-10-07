<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvailabilityRoomRequest;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Room::all();
    }

    public function availability(AvailabilityRoomRequest $request, Room $room): JsonResponse
    {
        $validated = $request->validated();
        $from = Carbon::parse($validated['from']);
        $to = Carbon::parse($validated['to']);
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

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

            return $slots; // Retorna o array completo
        });

        $slotsCollection = collect($allFreeSlots);

        $paginatedSlots = new LengthAwarePaginator(
            $slotsCollection->forPage($page, $perPage)->values(),
            $slotsCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'message' => 'Este são os horários disponíveis da sala '.$room->name.' dentro da perído inserido.',
            'availability' => $paginatedSlots
        ]);
    }
}
