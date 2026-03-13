<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
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
            ->has('overviewStats', fn (AssertableInertia $stats) => $stats
                ->where('activeEmployees', fn ($value) => is_numeric($value))
                ->where('pendingRequests', fn ($value) => is_numeric($value))
                ->where('currentlyClockedIn', fn ($value) => is_numeric($value))
                ->where('todayActivityLogs', fn ($value) => is_numeric($value))
            )
            ->has('activePopups')
            ->has('showPopups')
        );
});

it('renders live overview stat counts from the database', function () {
    if (! Schema::hasTable('tbl_attendance')) {
        Schema::create('tbl_attendance', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable();
            $table->dateTime('time_in')->nullable();
            $table->string('time_in_remarks')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->string('time_out_remarks')->nullable();
        });
    }

    if (! Schema::hasTable('activity_log')) {
        Schema::create('activity_log', function ($table): void {
            $table->id('log_id');
            $table->unsignedBigInteger('fk_user_id')->nullable();
            $table->string('activity')->nullable();
            $table->string('module')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    $baselineActiveEmployees = User::query()->where('active', true)->count();
    $baselinePendingRequests = \Illuminate\Support\Facades\DB::table('tbl_leave_applications')
        ->whereIn('workflow_status', ['pending_rm', 'pending_hr', 'pending_sds'])
        ->count();
    $baselineCurrentlyClockedIn = \App\Models\Attendance::query()
        ->whereNull('time_out')
        ->distinct('hrid')
        ->count('hrid');
    $baselineTodayActivityLogs = \Illuminate\Support\Facades\DB::table('activity_log')
        ->whereDate('created_at', now()->toDateString())
        ->count();

    $activeUser = User::factory()->create([
        'active' => true,
        'hrId' => 7001,
    ]);
    User::factory()->create(['active' => true, 'hrId' => 7002]);
    User::factory()->create(['active' => false, 'hrId' => 7003]);

    \App\Models\Attendance::query()->create([
        'hrid' => 7001,
        'time_in' => now()->subHour(),
        'time_out' => null,
    ]);
    \App\Models\Attendance::query()->create([
        'hrid' => 7002,
        'time_in' => now()->subHours(2),
        'time_out' => null,
    ]);
    \App\Models\Attendance::query()->create([
        'hrid' => 7002,
        'time_in' => now()->subHours(6),
        'time_out' => now()->subHours(1),
    ]);

    \Illuminate\Support\Facades\DB::table('tbl_leave_applications')->insert([
        [
            'employee_hrid' => 7001,
            'employee_id' => 'EMP-7001',
            'leave_type' => 'Vacation Leave',
            'leave_for' => 'Self',
            'leave_start_date' => now()->toDateString(),
            'leave_end_date' => now()->addDay()->toDateString(),
            'leave_days' => 2,
            'date_applied' => now(),
            'workflow_status' => 'pending_rm',
            'rm_status' => 'pending',
            'hr_status' => 'pending',
            'sds_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'employee_hrid' => 7002,
            'employee_id' => 'EMP-7002',
            'leave_type' => 'Sick Leave',
            'leave_for' => 'Self',
            'leave_start_date' => now()->toDateString(),
            'leave_end_date' => now()->addDays(2)->toDateString(),
            'leave_days' => 3,
            'date_applied' => now(),
            'workflow_status' => 'pending_hr',
            'rm_status' => 'approved',
            'hr_status' => 'pending',
            'sds_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'employee_hrid' => 7003,
            'employee_id' => 'EMP-7003',
            'leave_type' => 'Emergency Leave',
            'leave_for' => 'Self',
            'leave_start_date' => now()->toDateString(),
            'leave_end_date' => now()->addDays(3)->toDateString(),
            'leave_days' => 4,
            'date_applied' => now(),
            'workflow_status' => 'approved',
            'rm_status' => 'approved',
            'hr_status' => 'approved',
            'sds_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $nextLogId = (int) \Illuminate\Support\Facades\DB::table('activity_log')->max('log_id') + 1;
    \Illuminate\Support\Facades\DB::table('activity_log')->insert([
        [
            'log_id' => $nextLogId,
            'fk_user_id' => $activeUser->userId,
            'activity' => 'Logged in',
            'module' => 'Authentication',
            'created_at' => now(),
        ],
        [
            'log_id' => $nextLogId + 1,
            'fk_user_id' => $activeUser->userId,
            'activity' => 'Viewed dashboard',
            'module' => 'Dashboard',
            'created_at' => now(),
        ],
        [
            'log_id' => $nextLogId + 2,
            'fk_user_id' => $activeUser->userId,
            'activity' => 'Old log',
            'module' => 'Dashboard',
            'created_at' => now()->subDay(),
        ],
    ]);

    $this->actingAs($activeUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('overviewStats.activeEmployees', $baselineActiveEmployees + 2)
            ->where('overviewStats.pendingRequests', $baselinePendingRequests + 2)
            ->where('overviewStats.currentlyClockedIn', $baselineCurrentlyClockedIn + 2)
            ->where('overviewStats.todayActivityLogs', $baselineTodayActivityLogs + 2)
        );
});
