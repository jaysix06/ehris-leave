<?php

use App\Models\EmployeeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function ensureRequestStatusRequestsTable(): void
{
    if (! Schema::hasTable('tbl_requests')) {
        Schema::create('tbl_requests', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('hrid')->nullable();
            $table->string('purpose', 100)->nullable();
            $table->string('attachment', 100)->nullable();
            $table->string('status', 100)->nullable();
            $table->string('type_of_request', 100)->nullable();
            $table->string('reason', 100)->nullable();
            $table->string('running_year', 10)->nullable();
            $table->string('remarks', 255)->nullable();
        });
    }
}

it('requires authentication to access my requests', function () {
    $this->get('/request-status/my-requests')->assertRedirect('/login');
});

it('renders the my requests page for authenticated users', function () {
    ensureRequestStatusRequestsTable();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45002,
    ]);

    EmployeeRequest::query()->create([
        'hrid' => 45002,
        'purpose' => 'School visitation',
        'attachment' => null,
        'status' => 'On Process',
        'type_of_request' => 'Locator Slip',
        'reason' => 'Monitoring activity in a partner school.',
        'running_year' => '2026',
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
            ->where('requests.0.reason', 'Monitoring activity in a partner school.')
            ->where('requests.0.status', 'On Process')
        );
});
