<?php

namespace App\Http\Requests\SelfService;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocatorSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purpose_of_travel' => ['required', 'string', 'max:255'],
            'travel_type' => ['required', 'in:official_business,official_time'],
            'travel_date' => ['required', 'date'],
            'time_out' => ['nullable', 'date_format:H:i'],
            'time_in' => ['nullable', 'date_format:H:i'],
            'destination' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purpose_of_travel.required' => 'Please enter the purpose of travel.',
            'travel_type.required' => 'Please select if this is official business or official time.',
            'travel_type.in' => 'The selected travel type is invalid.',
            'travel_date.required' => 'Please enter the travel date.',
            'travel_date.date' => 'The travel date must be a valid date.',
            'time_out.date_format' => 'Time out must use a valid time.',
            'time_in.date_format' => 'Time in must use a valid time.',
            'destination.required' => 'Please enter the destination.',
            'destination.max' => 'The destination may not be greater than 255 characters.',
        ];
    }
}
