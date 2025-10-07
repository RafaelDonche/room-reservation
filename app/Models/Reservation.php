<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'customer_id',
        'start_time',
        'end_time',
        'attendees',
        'status',
        'confirmation_code',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Aplica as regras de validação para cancelar uma reserva.
     */
    public function validatesCancellation(): void
    {
        if ($this->start_time->isPast()) {
            throw new \Exception("Esta reserva não pode mais ser cancelada.");
        }

        if (now()->diffInHours($this->start_time) < 2) {
            throw new \Exception("A reserva só pode ser cancelada com pelo menos 2 horas de antecedência.");
        }
    }

    /**
     * Escopo "mestre" que aplica todos os outros filtros.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($filters['room_id'] ?? null, function ($q, $roomId) {
            $q->where('room_id', $roomId);
        });

        $query->when($filters['from'] ?? null, function ($q, $from) {
            $q->where('start_time', '>=', $from);
        });

        $query->when($filters['to'] ?? null, function ($q, $to) {
            $q->where('end_time', '<=', $to);
        });

        $query->when($filters['min_capacity'] ?? null, function ($q, $minCapacity) {
            $q->whereHas('room', function ($roomQuery) use ($minCapacity) {
                $roomQuery->where('capacity', '>=', $minCapacity);
            });
        });

        return $query;
    }
}
