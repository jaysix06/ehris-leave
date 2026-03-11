<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

it('requires authentication to access the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('renders the dashboard with attendance props for authenticated users', function () {
    $user = User::factory()->create(['active' => true]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('dashboardAttendance', fn (AssertableInertia $attendance) => $attendance
                ->where('isClockedIn', fn ($value) => is_bool($value))
                ->where('hoursWorkedThisWeek', fn ($value) => is_string($value))
                ->where('lastTimeIn', fn ($value) => is_string($value) || is_null($value))
                ->where('lastTimeOut', fn ($value) => is_string($value) || is_null($value))
            )
            ->has('dashboardAttendanceTrends.recentTimeline', 7)
            ->where('dashboardAttendanceTrends.monthlyLateCount', fn ($value) => is_numeric($value))
            ->where('dashboardAttendanceTrends.monthlyUndertimeCount', fn ($value) => is_numeric($value))
            ->has('activePopups')
            ->has('showPopups')
        );
});
