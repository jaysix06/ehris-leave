<?php

use App\Models\LocatorSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function ensureLocatorSlipSupportTables(): void
{
    if (! Schema::hasTable('tbl_locator_slips')) {
        Schema::create('tbl_locator_slips', function ($table): void {
            $table->id();
            $table->string('control_no')->unique();
            $table->unsignedBigInteger('hrid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('rm_assignee_hrid')->nullable();
            $table->date('date_of_filing');
            $table->string('employee_name');
            $table->string('position_designation')->nullable();
            $table->string('permanent_station')->nullable();
            $table->string('purpose_of_travel');
            $table->string('travel_type');
            $table->date('travel_date');
            $table->time('time_out')->nullable();
            $table->time('time_in')->nullable();
            $table->string('destination');
            $table->string('workflow_status')->default('pending_rm');
            $table->string('rm_status')->default('pending');
            $table->unsignedBigInteger('rm_acted_by')->nullable();
            $table->timestamp('rm_action_at')->nullable();
            $table->text('rm_remarks')->nullable();
            $table->string('status')->default('Pending RM Approval');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('tbl_emp_official_info')) {
        Schema::create('tbl_emp_official_info', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable();
            $table->string('job_title')->nullable();
            $table->string('office')->nullable();
            $table->string('department_id')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_department')) {
        Schema::create('tbl_department', function ($table): void {
            $table->id();
            $table->string('department_id')->nullable();
            $table->string('department_name')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_reporting_manager')) {
        Schema::create('tbl_reporting_manager', function ($table): void {
            $table->id();
            $table->string('department_id')->nullable();
            $table->string('manager_name')->nullable();
        });
    }
}

it('renders the locator slip page for authenticated users', function () {
    ensureLocatorSlipSupportTables();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45001,
        'firstname' => 'Paul Kerwin',
        'middlename' => 'R.',
        'lastname' => 'Reyes',
        'fullname' => 'Paul Kerwin R. Reyes',
        'job_title' => 'Teacher III',
        'department_id' => 92001,
    ]);

    DB::table('tbl_department')->insert([
        'department_id' => '92001',
        'department_name' => 'Ozamiz City Central School',
    ]);

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '99001',
    ]);

    DB::table('tbl_emp_official_info')->insert([
        'hrid' => 45001,
        'job_title' => 'Teacher III',
        'office' => 'Office Override That Should Not Win',
        'department_id' => '92001',
    ]);

    $this->actingAs($user)
        ->get('/self-service/locator-slip')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SelfService/LocatorSlip')
            ->where('employeeProfile.name', 'Paul Kerwin R. Reyes')
            ->where('employeeProfile.position', 'Teacher III')
            ->where('employeeProfile.station', 'Ozamiz City Central School')
            ->where('filingDate', now()->toDateString())
        );
});

it('stores locator slip requests in the locator slip table', function () {
    ensureLocatorSlipSupportTables();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45001,
        'firstname' => 'Paul Kerwin',
        'middlename' => 'R.',
        'lastname' => 'Reyes',
        'fullname' => 'Paul Kerwin R. Reyes',
        'job_title' => 'Teacher III',
        'department_id' => 92001,
    ]);

    DB::table('tbl_department')->insert([
        'department_id' => '92001',
        'department_name' => 'Ozamiz City Central School',
    ]);

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '99001',
    ]);

    DB::table('tbl_emp_official_info')->insert([
        'hrid' => 45001,
        'job_title' => 'Teacher III',
        'office' => 'Office Override That Should Not Win',
        'department_id' => '92001',
    ]);

    $this->actingAs($user)
        ->post('/self-service/locator-slip', [
            'purpose_of_travel' => 'Attend parents meeting for SPA recital',
            'travel_type' => 'official_time',
            'travel_date' => '2026-02-10',
            'time_out' => '09:30',
            'time_in' => '11:30',
            'destination' => 'OCNHS',
        ])
        ->assertRedirect('/request-status/my-requests')
        ->assertSessionHas('status', 'Locator slip request submitted successfully.');

    $this->assertDatabaseHas('tbl_locator_slips', [
        'hrid' => 45001,
        'user_id' => $user->getKey(),
        'rm_assignee_hrid' => 99001,
        'employee_name' => 'Paul Kerwin R. Reyes',
        'position_designation' => 'Teacher III',
        'permanent_station' => 'Ozamiz City Central School',
        'purpose_of_travel' => 'Attend parents meeting for SPA recital',
        'travel_type' => 'official_time',
        'travel_date' => '2026-02-10',
        'time_out' => '09:30:00',
        'time_in' => '11:30:00',
        'destination' => 'OCNHS',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
    ]);

    expect(LocatorSlip::query()->first()?->control_no)->toStartWith('LS-');
});
