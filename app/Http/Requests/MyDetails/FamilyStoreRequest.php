<?php

namespace App\Http\Requests\MyDetails;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FamilyStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family' => ['nullable', 'array'],
            'family.*.relationship' => ['nullable', 'string', 'max:255'],
            'family.*.firstname' => ['nullable', 'string', 'max:255'],
            'family.*.middlename' => ['nullable', 'string', 'max:255'],
            'family.*.lastname' => ['nullable', 'string', 'max:255'],
            'family.*.extension' => ['nullable', 'string', 'max:50'],
            'family.*.dob' => ['nullable', 'string', 'max:50'],
            'family.*.occupation' => ['nullable', 'string', 'max:255'],
            'family.*.employer_name' => ['nullable', 'string', 'max:255'],
            'family.*.business_add' => ['nullable', 'string'],
            'family.*.tel_num' => ['nullable', 'string', 'max:50'],
        ];
    }
}
