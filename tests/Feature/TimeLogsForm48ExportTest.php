<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('requires authentication to export Form 48', function () {
    $this->get('/self-service/time-logs/export/form-48?month=3&year='.date('Y'))
        ->assertRedirect('/login');
});

it('returns PDF for authenticated user with valid month and year', function () {
    $user = User::factory()->create(['active' => true]);
    $year = (int) date('Y');
    $month = (int) date('n');

    $response = $this->actingAs($user)
        ->get('/self-service/time-logs/export/form-48?month='.$month.'&year='.$year);

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString('form-48_', $response->headers->get('content-disposition') ?? '');
});

it('validates month and year parameters for Form 48 export', function () {
    $user = User::factory()->create(['active' => true]);

    $this->actingAs($user)
        ->get('/self-service/time-logs/export/form-48')
        ->assertSessionHasErrors(['month', 'year']);

    $this->actingAs($user)
        ->get('/self-service/time-logs/export/form-48?month=13&year=2025')
        ->assertSessionHasErrors(['month']);

    $this->actingAs($user)
        ->get('/self-service/time-logs/export/form-48?month=1&year=1999')
        ->assertSessionHasErrors(['year']);
});
