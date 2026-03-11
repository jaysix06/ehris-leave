<?php

namespace App\Http\Requests\Utilities;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'string', 'in:Active,Inactive'],
            'links' => ['nullable', 'array', 'max:10'],
            'links.*.label' => ['nullable', 'string', 'max:100'],
            'links.*.url' => ['required_with:links.*.label', 'nullable', 'url', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'status.in' => 'The status must be either Active or Inactive.',
            'links.array' => 'The links must be a list of links.',
            'links.*.url.url' => 'Each link must be a valid URL.',
            'links.*.url.required_with' => 'A URL is required when a link label is provided.',
        ];
    }
}
