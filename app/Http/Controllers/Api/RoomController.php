<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvailabilityRoomRequest;
use App\Models\Room;
use App\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class RoomController extends Controller
{

    public function __construct(protected RoomService $roomService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Room::all();
    }

    public function availability(AvailabilityRoomRequest $request, Room $room): JsonResponse
    {
        $paginatedSlots = $this->roomService->getPaginatedAvailability(
            $request->validated(),
            $room,
            $request->url(),
            $request->query()
        );

        return response()->json([
            'message' => 'Este são os horários disponíveis da sala '.$room->name.' dentro da perído inserido.',
            'availability' => $paginatedSlots
        ]);
    }
}
