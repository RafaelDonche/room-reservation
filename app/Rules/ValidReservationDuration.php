<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidReservationDuration implements ValidationRule
{
    private Carbon $startTime;

    public function __construct(string $startTime)
    {
        $this->startTime = Carbon::parse($startTime);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $endTime = Carbon::parse($value);
        $durationInMinutes = $this->startTime->diffInMinutes($endTime);

        if ($durationInMinutes < 30) {
            $fail('A duração mínima da reserva é de 30 minutos.');
        }

        if ($durationInMinutes > 480) {
            $fail('A duração máxima da reserva é de 8 horas.');
        }
    }
}
