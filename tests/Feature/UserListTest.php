<?php

use App\Events\UserListUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;

uses(DatabaseTransactions::class);

function createAdminUser(): User
{
    return User::factory()->create([
        'active' => true,
        'role' => 'System Admin',
        'date_created' => now()->subDay()->toDateString(),
    ]);
}

it('requires authentication to access the user list', function () {
    $this->get('/utilities/user-list')->assertRedirect('/login');
});

it('renders the user list page for authenticated users', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->get('/utilities/user-list')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Utilities/UserList')
        );
});

it('returns datatables JSON for authenticated users', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->getJson('/utilities/users/datatables?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
});

it('returns datatables JSON containing created users', function () {
    $admin = createAdminUser();
    $user = User::factory()->create([
        'active' => false,
        'firstname' => 'TestFirstName',
        'lastname' => 'TestLastName',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/utilities/users/datatables?draw=1&start=0&length=100')
        ->assertOk();

    $data = $response->json('data');
    $hasUser = collect($data)->contains(fn (array $row) => str_contains($row['name'] ?? '', 'TestFirstName'));
    expect($hasUser)->toBeTrue();
});

it('returns summary stats for authenticated users', function () {
    $admin = createAdminUser();
    $today = now()->toDateString();

    $baselineInactiveAccounts = DB::table('tbl_user')->where('active', 0)->count();
    $baselineRegisteredToday = DB::table('tbl_user')->whereDate('date_created', $today)->count();

    User::factory()->create([
        'active' => false,
        'date_created' => $today,
    ]);
    User::factory()->create([
        'active' => false,
        'date_created' => $today,
    ]);
    User::factory()->create([
        'active' => true,
        'date_created' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->getJson('/utilities/user-list/summary-stats')
        ->assertOk()
        ->assertJsonPath('inactiveAccounts', $baselineInactiveAccounts + 2)
        ->assertJsonPath('registeredToday', $baselineRegisteredToday + 2)
        ->assertJsonPath('date', $today);
});

it('dispatches user-list realtime event when creating a user', function () {
    Event::fake([UserListUpdated::class]);

    $admin = createAdminUser();

    $this->actingAs($admin)
        ->postJson('/utilities/users', [
            'personal_email' => 'realtime-test@example.com',
            'firstname' => 'Realtime',
            'lastname' => 'Tester',
            'role' => 'Employee',
        ])
        ->assertCreated();

    Event::assertDispatched(UserListUpdated::class, function (UserListUpdated $event) {
        return $event->action === 'created' && is_int($event->userId);
    });
});
