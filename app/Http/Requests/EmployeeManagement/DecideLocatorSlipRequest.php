<?php

namespace App\Http\Requests\EmployeeManagement;

use Illuminate\Foundation\Http\FormRequest;

class DecideLocatorSlipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,disapprove'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Please choose whether to approve or disapprove this locator slip.',
            'decision.in' => 'The selected decision is invalid.',
            'remarks.max' => 'Remarks may not be greater than 2000 characters.',
        ];
    }
}
