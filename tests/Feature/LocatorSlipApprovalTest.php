<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function ensureLocatorSlipApprovalTables(): void
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

    if (! Schema::hasTable('tbl_reporting_manager')) {
        Schema::create('tbl_reporting_manager', function ($table): void {
            $table->id();
            $table->string('department_id')->nullable();
            $table->string('manager_name')->nullable();
        });
    }

    if (! Schema::hasTable('tbl_emp_official_info')) {
        Schema::create('tbl_emp_official_info', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('hrid')->nullable();
            $table->string('firstname')->nullable();
            $table->string('middlename')->nullable();
            $table->string('lastname')->nullable();
            $table->string('extension')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department_id')->nullable();
            $table->string('reporting_manager')->nullable();
        });
    }
}

it('shows the locator slip approvals page to reporting managers, hr, and admins', function () {
    ensureLocatorSlipApprovalTables();

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '9001',
    ]);

    $users = [
        User::factory()->create([
            'active' => true,
            'role' => 'Reporting Manager',
            'hrId' => 9001,
        ]),
        User::factory()->create([
            'active' => true,
            'role' => 'HR Staff',
            'hrId' => 9002,
        ]),
        User::factory()->create([
            'active' => true,
            'role' => 'System Admin',
            'hrId' => 9003,
        ]),
    ];

    foreach ($users as $user) {
        $this->actingAs($user)
            ->get('/employee-management/locator-slip-approvals')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('EmployeeManagement/LocatorSlipApprovals')
                ->where('accessDenied', false)
            );
    }
});

it('shows access denied to employees for locator slip approvals', function () {
    ensureLocatorSlipApprovalTables();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45001,
    ]);

    $this->actingAs($user)
        ->get('/employee-management/locator-slip-approvals')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('EmployeeManagement/LocatorSlipApprovals')
            ->where('accessDenied', true)
        );
});

it('returns canAct true for reporting managers and false for admins', function () {
    ensureLocatorSlipApprovalTables();

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '9001',
    ]);

    $rm = User::factory()->create([
        'active' => true,
        'role' => 'Reporting Manager',
        'hrId' => 9001,
    ]);

    $admin = User::factory()->create([
        'active' => true,
        'role' => 'System Admin',
        'hrId' => 9003,
    ]);

    $this->actingAs($rm)
        ->get('/employee-management/locator-slip-approvals')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canAct', true)
        );

    $this->actingAs($admin)
        ->get('/employee-management/locator-slip-approvals')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canAct', false)
        );
});

it('returns only assigned slips for reporting managers in datatables', function () {
    ensureLocatorSlipApprovalTables();

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '9001',
    ]);

    $rm = User::factory()->create([
        'active' => true,
        'role' => 'Reporting Manager',
        'hrId' => 9001,
    ]);

    DB::table('tbl_locator_slips')->insert([
        'control_no' => 'LS-TEST-RM-00010',
        'hrid' => 45001,
        'rm_assignee_hrid' => 9001,
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Teacher One',
        'purpose_of_travel' => 'Meeting',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-15',
        'destination' => 'Division Office',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tbl_locator_slips')->insert([
        'control_no' => 'LS-TEST-RM-00011',
        'hrid' => 45002,
        'rm_assignee_hrid' => 9002,
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Teacher Two',
        'purpose_of_travel' => 'Training',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-16',
        'destination' => 'Regional Office',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($rm)
        ->getJson('/api/employee-management/locator-slip-approvals/datatables')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['control_no'])->toBe('LS-TEST-RM-00010');
    expect($data[0]['can_act'])->toBeTrue();
});

it('returns all slips for system admins as view-only in datatables', function () {
    ensureLocatorSlipApprovalTables();

    $admin = User::factory()->create([
        'active' => true,
        'role' => 'System Admin',
        'hrId' => 9003,
    ]);

    DB::table('tbl_locator_slips')->insert([
        'control_no' => 'LS-TEST-ADM-00020',
        'hrid' => 45001,
        'rm_assignee_hrid' => 9001,
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Teacher One',
        'purpose_of_travel' => 'Meeting',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-15',
        'destination' => 'Division Office',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tbl_locator_slips')->insert([
        'control_no' => 'LS-TEST-ADM-00021',
        'hrid' => 45002,
        'rm_assignee_hrid' => 9002,
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Teacher Two',
        'purpose_of_travel' => 'Training',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-16',
        'destination' => 'Regional Office',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/api/employee-management/locator-slip-approvals/datatables?length=-1')
        ->assertOk();

    $data = $response->json('data');
    $controlNumbers = array_column($data, 'control_no');
    expect($controlNumbers)->toContain('LS-TEST-ADM-00020');
    expect($controlNumbers)->toContain('LS-TEST-ADM-00021');

    foreach ($data as $row) {
        expect($row['can_act'])->toBeFalse();
    }
});

it('allows the assigned reporting manager to approve a locator slip request', function () {
    ensureLocatorSlipApprovalTables();

    $manager = User::factory()->create([
        'active' => true,
        'role' => 'Reporting Manager',
        'hrId' => 9001,
    ]);

    DB::table('tbl_reporting_manager')->insert([
        'department_id' => '92001',
        'manager_name' => '9001',
    ]);

    DB::table('tbl_emp_official_info')->insert([
        'hrid' => 9001,
        'firstname' => 'School',
        'middlename' => null,
        'lastname' => 'Principal',
        'extension' => null,
        'job_title' => 'Principal II',
        'department_id' => '92001',
        'reporting_manager' => null,
    ]);

    $locatorSlipId = DB::table('tbl_locator_slips')->insertGetId([
        'control_no' => 'LS-2026-00001',
        'hrid' => 45001,
        'user_id' => null,
        'rm_assignee_hrid' => 9001,
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Teacher One',
        'position_designation' => 'Teacher I',
        'permanent_station' => 'Ozamiz City Central School',
        'purpose_of_travel' => 'Attend division meeting',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-15',
        'time_out' => '08:00:00',
        'time_in' => '12:00:00',
        'destination' => 'Division Office',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'remarks' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($manager)
        ->patch("/employee-management/locator-slip-approvals/{$locatorSlipId}/decision", [
            'decision' => 'approve',
            'remarks' => 'Approved by principal',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tbl_locator_slips', [
        'id' => $locatorSlipId,
        'workflow_status' => 'approved',
        'rm_status' => 'approved',
        'rm_acted_by' => 9001,
        'rm_remarks' => 'Approved by principal',
        'status' => 'Approved',
        'remarks' => 'Approved by principal',
    ]);
});
