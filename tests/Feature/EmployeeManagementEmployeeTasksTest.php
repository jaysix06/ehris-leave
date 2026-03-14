<?php

use App\Models\Attendance;
use App\Models\SelfServiceTask;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function createHrManagerUser(): User
{
    return User::factory()->create([
        'active' => true,
        'role' => 'HR Manager',
        'date_created' => now()->subDay()->toDateString(),
    ]);
}

function createRegularEmployeeUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'active' => true,
        'role' => 'Employee',
        'date_created' => now()->subDay()->toDateString(),
    ], $attributes));
}

function ensureEmployeeTasksSupportTables(): void
{
    if (! Schema::hasTable('tbl_emp_official_info')) {
        Schema::create('tbl_emp_official_info', function ($table): void {
            $table->unsignedBigInteger('hrid')->primary();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('lastname')->nullable();
            $table->string('extension')->nullable();
            $table->string('job_title')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_attendance')) {
        Schema::create('tbl_attendance', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid');
            $table->dateTime('time_in')->nullable();
            $table->string('time_in_remarks')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->string('time_out_remarks')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_self_service_tasks')) {
        Schema::create('tbl_self_service_tasks', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->string('priority');
            $table->date('due_date');
            $table->date('due_date_end')->nullable();
            $table->boolean('add_to_calendar')->default(true);
            $table->string('status')->default('In Progress');
            $table->text('accomplishment_report')->nullable();
            $table->timestamps();
        });
    }
}

it('requires authentication to access employee tasks', function () {
    $this->get('/employee-management/employee-tasks')->assertRedirect('/login');
});

it('shows an access denied state for non hr and non admin users', function () {
    ensureEmployeeTasksSupportTables();
    $employee = createRegularEmployeeUser();

    $this->actingAs($employee)
        ->get('/employee-management/employee-tasks')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('EmployeeManagement/EmployeeTasks')
            ->where('accessDenied', true)
            ->where('employees', [])
        );
});

it('renders employee task cards filtered by the selected date', function () {
    ensureEmployeeTasksSupportTables();
    $primaryEmployeeHrid = 2100000001;
    $secondaryEmployeeHrid = 2100000002;
    $selectedDate = '2099-03-10';

    $hrUser = createHrManagerUser();
    $employeeUser = createRegularEmployeeUser([
        'hrId' => $primaryEmployeeHrid,
        'firstname' => 'Ana',
        'lastname' => 'Santos',
        'fullname' => 'Ana Santos',
        'avatar' => "uploads/{$primaryEmployeeHrid}/{$primaryEmployeeHrid}.jpg",
        'job_title' => 'Administrative Assistant II',
    ]);
    $otherEmployeeUser = createRegularEmployeeUser([
        'hrId' => $secondaryEmployeeHrid,
        'firstname' => 'Ben',
        'lastname' => 'Rivera',
        'fullname' => 'Ben Rivera',
        'job_title' => 'Planning Officer I',
    ]);

    DB::table('tbl_emp_official_info')->upsert([
        [
            'hrid' => $primaryEmployeeHrid,
            'firstname' => 'Ana',
            'middlename' => 'L.',
            'lastname' => 'Santos',
            'extension' => null,
            'job_title' => 'Administrative Assistant II',
        ],
        [
            'hrid' => $secondaryEmployeeHrid,
            'firstname' => 'Ben',
            'middlename' => 'M.',
            'lastname' => 'Rivera',
            'extension' => null,
            'job_title' => 'Planning Officer I',
        ],
    ], ['hrid']);

    Attendance::query()->create([
        'hrid' => $primaryEmployeeHrid,
        'time_in' => "{$selectedDate} 08:05:00",
        'time_out' => "{$selectedDate} 17:02:00",
    ]);
    Attendance::query()->create([
        'hrid' => $secondaryEmployeeHrid,
        'time_in' => '2099-03-09 08:00:00',
        'time_out' => '2099-03-09 17:00:00',
    ]);

    SelfServiceTask::query()->create([
        'user_id' => $employeeUser->getKey(),
        'title' => 'Prepare division report',
        'description' => 'Finalize the division accomplishment report for submission.',
        'priority' => 'High',
        'due_date' => $selectedDate,
        'due_date_end' => null,
        'add_to_calendar' => true,
        'status' => 'In Progress',
    ]);
    SelfServiceTask::query()->create([
        'user_id' => $employeeUser->getKey(),
        'title' => 'Compile payroll attachments',
        'description' => 'Collect and verify payroll attachment requirements.',
        'priority' => 'Medium',
        'due_date' => '2099-03-08',
        'due_date_end' => $selectedDate,
        'add_to_calendar' => true,
        'status' => 'On Hold',
    ]);
    SelfServiceTask::query()->create([
        'user_id' => $otherEmployeeUser->getKey(),
        'title' => 'Outside selected date',
        'description' => 'This should not appear on the filtered page.',
        'priority' => 'Low',
        'due_date' => '2099-03-11',
        'due_date_end' => null,
        'add_to_calendar' => true,
        'status' => 'In Progress',
    ]);

    $response = $this->actingAs($hrUser)
        ->get("/employee-management/employee-tasks?date={$selectedDate}")
        ->assertOk();

    $response
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('EmployeeManagement/EmployeeTasks')
            ->where('accessDenied', false)
            ->where('selectedDate', $selectedDate)
            ->has('employees', 1)
            ->where('employees.0.name', 'Ana L. Santos')
            ->where('employees.0.role', 'Employee')
            ->where('employees.0.job_title', 'Administrative Assistant II')
            ->where('employees.0.avatar', "uploads/{$primaryEmployeeHrid}/{$primaryEmployeeHrid}.jpg")
            ->where('employees.0.clock_in', '08:05 AM')
            ->where('employees.0.clock_out', '05:02 PM')
            ->has('employees.0.tasks', 2)
            ->where('employees.0.tasks.0.title', 'Compile payroll attachments')
            ->where('employees.0.tasks.1.title', 'Prepare division report')
        );
});
