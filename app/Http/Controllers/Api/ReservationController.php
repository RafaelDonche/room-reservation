<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelReservationRequest;
use App\Http\Requests\Api\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Jobs\ProcessReservation;
use App\Jobs\SendWebhookNotification;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    // Injetamos o serviço no construtor para que esteja disponível em todos os métodos.
    public function __construct(protected ReservationService $reservationService)
    {
    }

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
        $reservation = $this->reservationService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Sua reserva foi criada com status pendente. A confirmação será processada em breve.',
            'reservation' => $reservation
        ], 202);
    }

    public function cancel(CancelReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $updatedReservation = $this->reservationService->cancel($reservation);

        return response()->json([
            'message' => 'Sua reserva foi cancelada com sucesso.',
            'reservation' => $updatedReservation
        ]);
    }
}
