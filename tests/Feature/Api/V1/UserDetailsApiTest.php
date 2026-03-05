<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

test('user details api requires sanctum authentication', function () {
    getJson('/api/user-details')
        ->assertUnauthorized();
});

test('sanctum authenticated user can fetch user details', function () {
    $user = User::factory()->make([
        'hrId' => 1001001,
        'email' => 'sample@example.com',
        'firstname' => 'Sample',
        'lastname' => 'User',
        'fullname' => 'Sample User',
        'email_verified_at' => now(),
    ]);
    $user->forceFill([
        'userId' => 1,
    ]);

    Sanctum::actingAs($user);

    getJson('/api/user-details')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'profile',
                'officialInfo',
                'personalInfo',
                'contactInfo',
            ],
        ])
        ->assertJsonPath('data.profile.email', 'sample@example.com')
        ->assertJsonPath('data.profile.hrId', 1001001);
});
