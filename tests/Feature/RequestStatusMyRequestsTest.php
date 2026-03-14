<?php

use App\Models\LocatorSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function ensureRequestStatusLocatorSlipTable(): void
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
}

it('requires authentication to access my requests', function () {
    $this->get('/request-status/my-requests')->assertRedirect('/login');
});

it('renders the my requests page for authenticated users', function () {
    ensureRequestStatusLocatorSlipTable();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45002,
    ]);

    LocatorSlip::query()->create([
        'control_no' => 'LS-2026-00001',
        'hrid' => 45002,
        'user_id' => $user->getKey(),
        'date_of_filing' => '2026-03-14',
        'employee_name' => 'Test Employee',
        'position_designation' => 'Teacher I',
        'permanent_station' => 'Sample School',
        'purpose_of_travel' => 'School visitation',
        'travel_type' => 'official_business',
        'travel_date' => '2026-03-15',
        'time_out' => '08:30:00',
        'time_in' => '11:30:00',
        'destination' => 'Partner School',
        'workflow_status' => 'pending_rm',
        'rm_status' => 'pending',
        'status' => 'Pending RM Approval',
        'remarks' => 'Waiting for approval',
    ]);

    $this->actingAs($user)
        ->get('/request-status/my-requests')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('RequestStatus/MyRequests')
            ->has('requests', 1)
            ->where('requests.0.type', 'Locator Slip')
            ->where('requests.0.purpose', 'School visitation')
            ->where('requests.0.reason', 'Official Business | 15 Mar 2026 | Time Out: 08:30 AM | Time In: 11:30 AM | Destination: Partner School')
            ->where('requests.0.status', 'Pending RM Approval')
        );
});
