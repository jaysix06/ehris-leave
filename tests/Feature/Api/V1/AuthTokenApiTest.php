<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\postJson;

beforeEach(function () {
    if (! Schema::hasTable('tbl_user')) {
        Schema::create('tbl_user', function (Blueprint $table): void {
            $table->increments('userId');
            $table->unsignedInteger('hrId')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('lastname')->nullable();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('extname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('job_title')->nullable();
            $table->string('role')->nullable();
            $table->boolean('active')->default(true);
            $table->date('date_created')->nullable();
            $table->string('fullname')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });
    }
});

test('api login returns sanctum bearer token', function () {
    User::query()->create([
        'hrId' => 1001001,
        'email' => 'tests@gmail.com',
        'password' => Hash::make('secret123'),
        'firstname' => 'Juan',
        'lastname' => 'Dela Cruz',
        'fullname' => 'Juan Dela Cruz',
        'role' => 'employee',
        'email_verified_at' => now(),
    ]);

    postJson('/api/auth/login', [
        'email' => 'tests@gmail.com',
        'password' => 'secret123',
        'device_name' => 'postman-local',
    ])->assertOk()
        ->assertJsonStructure([
            'token_type',
            'access_token',
            'user' => ['id', 'hrId', 'email', 'fullname', 'role'],
        ])
        ->assertJsonPath('token_type', 'Bearer');
});

test('api login rejects invalid credentials', function () {
    User::query()->create([
        'hrId' => 1001002,
        'email' => 'wrongpass@gmail.com',
        'password' => Hash::make('secret123'),
        'firstname' => 'Wrong',
        'lastname' => 'Pass',
        'fullname' => 'Wrong Pass',
        'role' => 'employee',
        'email_verified_at' => now(),
    ]);

    postJson('/api/auth/login', [
        'email' => 'wrongpass@gmail.com',
        'password' => 'not-correct',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'The provided credentials are incorrect.');
});

test('api logout revokes current token', function () {
    $user = User::query()->create([
        'hrId' => 1001003,
        'email' => 'logout@gmail.com',
        'password' => Hash::make('secret123'),
        'firstname' => 'Log',
        'lastname' => 'Out',
        'fullname' => 'Log Out',
        'role' => 'employee',
        'email_verified_at' => now(),
    ]);

    $loginResponse = postJson('/api/auth/login', [
        'email' => 'logout@gmail.com',
        'password' => 'secret123',
    ])->assertOk();

    $token = (string) $loginResponse->json('access_token');

    postJson('/api/auth/logout', [], [
        'Authorization' => "Bearer {$token}",
    ])->assertOk()
        ->assertJsonPath('message', 'Token revoked.');

    expect(
        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->count()
    )->toBe(0);
});
