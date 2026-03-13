<?php

namespace App\Http\Requests\Utilities;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendAnnouncementEmailRequest extends FormRequest
{
    public function rules(): array
    {
        $validRoles = Role::roleNames();

        return [
            'recipient_scope' => ['required', 'string', Rule::in(['all', 'role'])],
            'only_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::in($validRoles)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'only_active' => $this->boolean('only_active', true),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $scope = (string) $this->input('recipient_scope');
            $roles = $this->input('roles');

            if ($scope === 'role' && (! is_array($roles) || count($roles) === 0)) {
                $validator->errors()->add('roles', 'Please select at least one role.');
            }
        });
    }
}

