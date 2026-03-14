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
            'purpose' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'purpose.required' => 'Please enter the purpose of your locator slip request.',
            'purpose.max' => 'The purpose may not be greater than 100 characters.',
            'reason.required' => 'Please enter the reason for your locator slip request.',
            'reason.max' => 'The reason may not be greater than 100 characters.',
            'attachment.mimes' => 'The attachment must be a PDF, JPG, JPEG, or PNG file.',
            'attachment.max' => 'The attachment may not be greater than 10 MB.',
        ];
    }
}
