<?php

namespace App\Http\Requests\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minBookingTime = now()->addMinutes(5);

        return [
            'room_id' => 'required|exists:rooms,id',
            'start_time' => [
                'required',
                'date',
                'after_or_equal:' . $minBookingTime,
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',

                function ($attribute, $value, $fail) {
                    $startTime = Carbon::parse($this->input('start_time'));
                    $endTime = Carbon::parse($value);
                    $durationInMinutes = $startTime->diffInMinutes($endTime);

                    if ($durationInMinutes < 30) {
                        $fail('A duração mínima da reserva é de 30 minutos.');
                    }

                    if ($durationInMinutes > 480) {
                        $fail('A duração máxima da reserva é de 8 horas.');
                    }
                },
            ],
            'attendees' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
