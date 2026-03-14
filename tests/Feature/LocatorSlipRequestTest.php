<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function ensureEmployeeRequestsTable(): void
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

it('renders the locator slip page for authenticated users', function () {
    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
    ]);

    $this->actingAs($user)
        ->get('/self-service/locator-slip')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('SelfService/LocatorSlip')
        );
});

it('stores locator slip requests in the employee requests table', function () {
    ensureEmployeeRequestsTable();

    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 45001,
    ]);

    $this->actingAs($user)
        ->post('/self-service/locator-slip', [
            'purpose' => 'Attend an offsite coordination meeting',
            'reason' => 'Need approval for official travel outside the office.',
        ])
        ->assertRedirect('/request-status/my-requests')
        ->assertSessionHas('status', 'Locator slip request submitted successfully.');

    $this->assertDatabaseHas('tbl_requests', [
        'hrid' => 45001,
        'purpose' => 'Attend an offsite coordination meeting',
        'reason' => 'Need approval for official travel outside the office.',
        'status' => 'On Process',
        'type_of_request' => 'Locator Slip',
    ]);
});
