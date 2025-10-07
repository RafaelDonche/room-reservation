<?php

namespace App\Http\Requests\Api;

use App\Rules\ValidReservationDuration;
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
                new ValidReservationDuration($this->input('start_time')),
            ],
            'attendees' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
