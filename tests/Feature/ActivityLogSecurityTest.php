<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTransactions::class);

beforeEach(function () {
    if (! Schema::hasTable('activity_log')) {
        Schema::create('activity_log', function ($table): void {
            $table->id('log_id');
            $table->unsignedBigInteger('fk_user_id')->nullable();
            $table->string('activity')->nullable();
            $table->string('module')->nullable();
            $table->string('severity', 20)->default('info');
            $table->string('event_type', 100)->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    } else {
        Schema::table('activity_log', function ($table): void {
            if (! Schema::hasColumn('activity_log', 'severity')) {
                $table->string('severity', 20)->default('info');
            }
            if (! Schema::hasColumn('activity_log', 'event_type')) {
                $table->string('event_type', 100)->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'target_user_id')) {
                $table->unsignedBigInteger('target_user_id')->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'http_method')) {
                $table->string('http_method', 10)->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'route_name')) {
                $table->string('route_name', 255)->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }
            if (! Schema::hasColumn('activity_log', 'context')) {
                $table->json('context')->nullable();
            }
        });
    }
});

function createSecurityAdmin(): User
{
    return User::factory()->create([
        'active' => true,
        'role' => 'System Admin',
        'email' => 'security.admin@deped.gov.ph',
        'date_created' => now()->subDay()->toDateString(),
    ]);
}

it('logs failed login attempts to the activity log', function () {
    User::factory()->create([
        'active' => true,
        'email' => 'valid.user@deped.gov.ph',
        'password' => Hash::make('correct-password'),
    ]);

    $this->from('/login')
        ->post('/login', [
            'email' => 'valid.user@deped.gov.ph',
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/login');

    $log = DB::table('activity_log')
        ->where('module', 'Authentication Security')
        ->latest('log_id')
        ->first();

    expect($log)->not->toBeNull();
    expect((string) $log->activity)->toContain('Authentication failed');
    expect((string) $log->activity)->toContain('reason: invalid_password');
    expect((string) $log->activity)->toContain('email: valid.user@deped.gov.ph');
    expect($log->severity)->toBe('warning');
    expect($log->event_type)->toBe('authentication_failed');
    expect($log->route_name)->toBe('login.store');
});

it('logs denied OTP requests for unknown accounts', function () {
    $this->from('/forgot-password')
        ->post(route('password.otp.send'), [
            'email' => 'missing.account@example.com',
        ])
        ->assertRedirect('/forgot-password');

    $log = DB::table('activity_log')
        ->where('module', 'Credential Recovery')
        ->latest('log_id')
        ->first();

    expect($log)->not->toBeNull();
    expect((string) $log->activity)->toContain('OTP request denied');
    expect((string) $log->activity)->toContain('reason: no_account_found');
    expect((string) $log->activity)->toContain('email: missing.account@example.com');
    expect($log->severity)->toBe('warning');
    expect($log->event_type)->toBe('credential_recovery');
    expect($log->http_method)->toBe('POST');
});

it('logs sensitive admin edits to user accounts', function () {
    $admin = createSecurityAdmin();
    $user = User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'email' => 'before.account@deped.gov.ph',
        'personal_email' => 'before.personal@example.com',
    ]);

    $this->actingAs($admin)
        ->patchJson("/utilities/users/{$user->getKey()}", [
            'email' => 'after.account@deped.gov.ph',
            'personal_email' => 'after.personal@example.com',
            'role' => 'Teacher',
        ])
        ->assertOk();

    $logs = DB::table('activity_log')
        ->where('module', 'User Management')
        ->where('fk_user_id', $admin->getKey())
        ->orderBy('log_id')
        ->pluck('activity');

    expect($logs)->toHaveCount(3);
    expect($logs->implode("\n"))->toContain('Sensitive user account field updated');
    expect($logs->implode("\n"))->toContain('target_user_id: '.$user->getKey());
    expect($logs->implode("\n"))->toContain('field: user_account');
    expect($logs->implode("\n"))->toContain('before: before.account@deped.gov.ph');
    expect($logs->implode("\n"))->toContain('after: after.account@deped.gov.ph');
    expect($logs->implode("\n"))->toContain('field: personal_email');
    expect($logs->implode("\n"))->toContain('after: after.personal@example.com');
    expect($logs->implode("\n"))->toContain('field: role');
    expect($logs->implode("\n"))->toContain('after: Teacher');

    $structuredLog = DB::table('activity_log')
        ->where('module', 'User Management')
        ->where('fk_user_id', $admin->getKey())
        ->where('target_user_id', $user->getKey())
        ->first();

    expect($structuredLog)->not->toBeNull();
    expect($structuredLog->severity)->toBe('warning');
    expect($structuredLog->event_type)->toBe('user_account_change');
});

it('logs user list exports', function () {
    $admin = createSecurityAdmin();

    $this->actingAs($admin)
        ->get('/utilities/users/export/excel')
        ->assertOk();

    $log = DB::table('activity_log')
        ->where('module', 'Report Export')
        ->where('fk_user_id', $admin->getKey())
        ->latest('log_id')
        ->first();

    expect($log)->not->toBeNull();
    expect((string) $log->activity)->toContain('Exported user list');
    expect((string) $log->activity)->toContain('format: csv');
    expect($log->severity)->toBe('info');
    expect($log->event_type)->toBe('data_export');
});

it('logs leave approval decisions with actor and target context', function () {
    $approver = User::factory()->create([
        'active' => true,
        'role' => 'Reporting Manager',
        'hrId' => 8001,
        'email' => 'rm.approver@deped.gov.ph',
    ]);

    User::factory()->create([
        'active' => true,
        'role' => 'Employee',
        'hrId' => 8002,
        'email' => 'employee.target@deped.gov.ph',
    ]);

    $leaveId = DB::table('tbl_leave_applications')->insertGetId([
        'employee_hrid' => 8002,
        'employee_id' => 'EMP-8002',
        'rm_assignee_hrid' => 8001,
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
    ]);

    $this->actingAs($approver)
        ->from('/self-service/leave-application')
        ->patch("/self-service/leave-application/{$leaveId}/decision", [
            'decision' => 'approve',
            'remarks' => 'Reviewed and approved.',
        ])
        ->assertRedirect('/self-service/leave-application');

    $log = DB::table('activity_log')
        ->where('module', 'Leave Request Management')
        ->where('fk_user_id', $approver->getKey())
        ->latest('log_id')
        ->first();

    expect($log)->not->toBeNull();
    expect((string) $log->activity)->toContain('Leave request decision recorded');
    expect((string) $log->activity)->toContain("leave_id: {$leaveId}");
    expect((string) $log->activity)->toContain('decision: approved');
    expect((string) $log->activity)->toContain('actor_role: RM');
    expect((string) $log->activity)->toContain('target_hrid: 8002');
    expect($log->event_type)->toBe('leave_workflow_decision');
    expect($log->target_user_id)->not->toBeNull();
});

it('returns structured activity log fields in the datatable response', function () {
    $admin = createSecurityAdmin();
    $target = User::factory()->create([
        'email' => 'target.user@deped.gov.ph',
        'personal_email' => 'target.personal@example.com',
        'fullname' => 'Target User',
    ]);

    DB::table('activity_log')->insert([
        'log_id' => (int) DB::table('activity_log')->max('log_id') + 1,
        'fk_user_id' => $admin->getKey(),
        'activity' => 'Structured activity sample',
        'module' => 'Security Monitoring',
        'severity' => 'warning',
        'event_type' => 'sample_event',
        'target_user_id' => $target->getKey(),
        'ip_address' => '127.0.0.1',
        'http_method' => 'POST',
        'route_name' => 'utilities.user-list.update',
        'user_agent' => 'Symfony',
        'context' => json_encode(['field' => 'email']),
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/utilities/activity-log/datatables?draw=1&start=0&length=10')
        ->assertOk();

    $row = collect($response->json('data'))
        ->first(fn (array $item): bool => ($item['event_type'] ?? null) === 'sample_event');

    expect($row)->not->toBeNull();
    expect($row['severity'])->toBe('WARNING');
    expect($row['actor'])->toContain('security.admin@deped.gov.ph');
    expect($row['target'])->toContain('target.user@deped.gov.ph');
    expect($row['request_source'])->toContain('utilities.user-list.update');
});

it('filters the activity log datatable by structured filter inputs', function () {
    $admin = createSecurityAdmin();

    DB::table('activity_log')->insert([
        [
            'log_id' => (int) DB::table('activity_log')->max('log_id') + 1,
            'fk_user_id' => $admin->getKey(),
            'activity' => 'Keep this warning row',
            'module' => 'Credential Recovery',
            'severity' => 'warning',
            'event_type' => 'credential_recovery',
            'target_user_id' => null,
            'ip_address' => '127.0.0.1',
            'http_method' => 'POST',
            'route_name' => 'password.otp.send',
            'user_agent' => 'Symfony',
            'context' => json_encode(['reason' => 'throttled']),
            'created_at' => now(),
        ],
        [
            'log_id' => (int) DB::table('activity_log')->max('log_id') + 2,
            'fk_user_id' => $admin->getKey(),
            'activity' => 'Do not include this info row',
            'module' => 'Report Export',
            'severity' => 'info',
            'event_type' => 'data_export',
            'target_user_id' => null,
            'ip_address' => '127.0.0.1',
            'http_method' => 'GET',
            'route_name' => 'utilities.user-list.export-excel',
            'user_agent' => 'Symfony',
            'context' => json_encode(['format' => 'csv']),
            'created_at' => now()->subDay(),
        ],
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/utilities/activity-log/datatables?draw=1&start=0&length=10&severity=warning&event_type=credential_recovery&module=Credential%20Recovery&date_from='.now()->toDateString().'&date_to='.now()->toDateString())
        ->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.activity'))->toBe('Keep this warning row');
    expect($response->json('data.0.severity'))->toBe('WARNING');
    expect($response->json('data.0.event_type'))->toBe('credential_recovery');
});
