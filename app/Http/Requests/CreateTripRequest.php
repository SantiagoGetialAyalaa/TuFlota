<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'driver_id' => ['required', 'integer', 'min:1'],
            'departure_datetime' => ['required', 'date'],
            'estimated_arrival_datetime' => ['nullable', 'date', 'after:departure_datetime'],
            'status' => ['nullable', Rule::in(['scheduled', 'boarding', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
