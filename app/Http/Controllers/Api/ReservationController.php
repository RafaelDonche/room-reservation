<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelReservationRequest;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Jobs\ProcessReservation;
use App\Jobs\SendWebhookNotification;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all();

        $reservations = $request->user()->reservations()
            ->with(['room', 'customer'])
            ->filter($filters)
            ->orderBy(
                $request->query('sortBy', 'start_time'),
                $request->query('sortDirection', 'desc')
            )
            ->paginate($request->query('per_page', 15));

        return ReservationResource::collection($reservations);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = Reservation::create([
            'customer_id' => $request->user()->id,
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'attendees' => $request->attendees,
            'notes' => $request->notes,
            'status' => 'PENDING',
        ]);

        ProcessReservation::dispatch($reservation);

        // Código 202 = Aceito (em processamento).
        return response()->json([
            'message' => 'Sua reserva foi criada com status pendente. A confirmação será processada em breve.',
            'reservation' => $reservation
        ], 202);
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelado pelo cliente.',
        ]);

        return response()->json([
            'message' => 'Sua reserva foi cancelada com sucesso.',
            'reservation' => $reservation
        ]);
    }
}
