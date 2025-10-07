<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CancelReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');
        return $reservation && $this->user()->id === $reservation->customer_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $reservation = $this->route('reservation');

            // Verifica se a reserva já está cancelada ou se já ocorreu
            if ($reservation->status === 'CANCELLED' || $reservation->start_time->isPast()) {
                $validator->errors()->add('reservation', 'Esta reserva não pode mais ser cancelada.');
                return;
            }

            // Só pode cancelar com 2 ou mais horas de antecedência
            if (now()->diffInHours($reservation->start_time) < 2) {
                $validator->errors()->add('reservation', 'A reserva só pode ser cancelada com pelo menos 2 horas de antecedência.');
            }
        });
    }
}
