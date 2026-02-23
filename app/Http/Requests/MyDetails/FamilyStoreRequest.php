<?php

namespace App\Http\Requests\MyDetails;

use Illuminate\Foundation\Http\FormRequest;

class FamilyStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family' => ['required', 'array'],
            'family.*.relationship' => ['nullable', 'string', 'max:255'],
            'family.*.firstname' => ['nullable', 'string', 'max:255'],
            'family.*.middlename' => ['nullable', 'string', 'max:255'],
            'family.*.lastname' => ['nullable', 'string', 'max:255'],
            'family.*.extension' => ['nullable', 'string', 'max:255'],
            'family.*.dob' => ['nullable', 'string', 'max:255'],
            'family.*.occupation' => ['nullable', 'string', 'max:255'],
            'family.*.employer_name' => ['nullable', 'string', 'max:255'],
            'family.*.business_add' => ['nullable', 'string', 'max:255'],
            'family.*.tel_num' => ['nullable', 'string', 'max:255'],
        ];
    }
}
