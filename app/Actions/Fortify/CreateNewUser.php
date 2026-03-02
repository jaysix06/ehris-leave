<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'fullname' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'date_created' => now()->toDateString(),
        ]);

        // Log user creation
        ActivityLogService::logCreate(
            'User',
            "Created user account: {$user->email}",
            $user->userId
        );

        return $user;
    }
}
