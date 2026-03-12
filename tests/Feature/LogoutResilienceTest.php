<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

it('logs out successfully even if activity logging fails', function () {
    $user = User::factory()->create(['active' => true]);

    Schema::dropIfExists('activity_log');

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
