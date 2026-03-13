<?php

use App\Events\UserListUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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

    if (! Schema::hasTable('tbl_job_title')) {
        Schema::create('tbl_job_title', function ($table): void {
            $table->id();
            $table->string('job_title')->nullable();
            $table->string('job_shorten')->nullable();
        });
    }

    $jobTitle = 'Codex Test Title';
    $existingJobTitle = DB::table('tbl_job_title')
        ->where('job_title', $jobTitle)
        ->exists();

    if (! $existingJobTitle) {
        DB::table('tbl_job_title')->insert([
            'job_title' => $jobTitle,
            'job_shorten' => 'CTT',
        ]);
    }

    $this->actingAs($admin)
        ->get('/utilities/user-list')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Utilities/UserList')
            ->has('jobTitles')
            ->where('jobTitles', fn ($jobTitles) => collect($jobTitles)->contains($jobTitle))
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

it('returns online status based on unexpired authenticated sessions', function () {
    config()->set('session.driver', 'database');

    $admin = createAdminUser();
    $onlineUser = User::factory()->create([
        'firstname' => 'Online',
        'lastname' => 'Employee',
    ]);
    $offlineUser = User::factory()->create([
        'firstname' => 'Offline',
        'lastname' => 'Employee',
    ]);

    DB::table('sessions')->insert([
        'id' => 'online-user-session',
        'user_id' => $onlineUser->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test-payload',
        'last_activity' => now()->timestamp,
    ]);

    DB::table('sessions')->insert([
        'id' => 'expired-user-session',
        'user_id' => $offlineUser->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'test-payload',
        'last_activity' => now()->subMinutes(config('session.lifetime') + 1)->timestamp,
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/utilities/users?per_page=100')
        ->assertOk();

    $rows = collect($response->json('data'));

    expect($rows->firstWhere('id', $onlineUser->getKey()))->toMatchArray([
        'id' => $onlineUser->getKey(),
        'online' => true,
    ]);

    expect($rows->firstWhere('id', $offlineUser->getKey()))->toMatchArray([
        'id' => $offlineUser->getKey(),
        'online' => false,
    ]);
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

it('updates the personal email when editing a user', function () {
    Event::fake([UserListUpdated::class]);

    $admin = createAdminUser();
    $user = User::factory()->create([
        'personal_email' => 'before@example.com',
        'role' => 'Employee',
    ]);

    $this->actingAs($admin)
        ->patchJson("/utilities/users/{$user->getKey()}", [
            'personal_email' => 'after@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('personal_email', 'after@example.com');

    expect($user->fresh()->personal_email)->toBe('after@example.com');

    Event::assertDispatched(UserListUpdated::class, function (UserListUpdated $event) use ($user) {
        return $event->action === 'updated' && $event->userId === (int) $user->getKey();
    });
});

it('updates the user account email when editing a user', function () {
    Event::fake([UserListUpdated::class]);

    $admin = createAdminUser();
    $user = User::factory()->create([
        'active' => true,
        'email' => 'before.account@deped.gov.ph',
        'role' => 'Employee',
    ]);

    $this->actingAs($admin)
        ->patchJson("/utilities/users/{$user->getKey()}", [
            'email' => 'after.account@deped.gov.ph',
        ])
        ->assertOk()
        ->assertJsonPath('email', 'after.account@deped.gov.ph');

    expect($user->fresh()->email)->toBe('after.account@deped.gov.ph');

    Event::assertDispatched(UserListUpdated::class, function (UserListUpdated $event) use ($user) {
        return $event->action === 'updated' && $event->userId === (int) $user->getKey();
    });
});
