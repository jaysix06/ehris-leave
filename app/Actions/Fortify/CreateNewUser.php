<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Mail\NewUserRegistrationAdminMail;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $profileRules = $this->profileRules();
        unset($profileRules['name']);
        $profileRules['firstname'] = ['required', 'string', 'max:255'];
        $profileRules['lastname'] = ['required', 'string', 'max:255'];
        $profileRules['middlename'] = ['nullable', 'string', 'max:255'];
        $profileRules['extname'] = ['nullable', 'string', 'max:50'];

        $validStatuses = EmploymentStatus::pluck('emp_status')->all();
        $validDistrictIds = BusinessUnit::pluck('BusinessUnitId')->map(fn ($id) => (string) $id)->all();
        $validStationIds = Department::pluck('department_id')->map(fn ($id) => (string) $id)->all();

        Validator::make($input, [
            ...$profileRules,
            'employment_status' => ['required', 'string', Rule::in($validStatuses)],
            'district' => ['required', Rule::in($validDistrictIds)],
            'station' => ['required', Rule::in($validStationIds)],
            'password' => $this->passwordRules(),
        ])->validate();

        // Build fullname from name components
        $fullname = trim(implode(' ', array_filter([
            trim((string) ($input['firstname'] ?? '')),
            trim((string) ($input['middlename'] ?? '')),
            trim((string) ($input['lastname'] ?? '')),
            trim((string) ($input['extname'] ?? '')),
        ]))) ?: trim((string) ($input['email'] ?? ''));

        $user = User::create([
            'fullname' => $fullname,
            'firstname' => $input['firstname'] ?? null,
            'middlename' => $input['middlename'] ?? null,
            'lastname' => $input['lastname'] ?? null,
            'extname' => $input['extname'] ?? null,
            'email' => $input['email'],
            'password' => $input['password'],
            'date_created' => now()->toDateString(),
            'active' => false,
            'role' => 'Employee',
            'department_id' => (int) $input['station'],
        ]);

        // After create, userId is set; use it as hrId so we can link Employee
        $user->hrId = $user->getKey();
        $user->save();

        $hrid = (int) $user->hrId;
        $nickname = trim((string) ($input['firstname'] ?? '')) ?: '—';

        // Sync to tbl_emp_official_info if the table exists (no Employee model in this project)
        if (Schema::hasTable('tbl_emp_official_info')) {
            DB::table('tbl_emp_official_info')->updateOrInsert(
                ['hrid' => $hrid],
                [
                    'id' => $hrid,
                    'hrid' => $hrid,
                    'firstname' => $input['firstname'],
                    'middlename' => $input['middlename'] ?? '',
                    'lastname' => $input['lastname'],
                    'extension' => $input['extname'] ?? '',
                    'nickname' => $nickname,
                    'employ_status' => $input['employment_status'],
                    'business_id' => (string) $input['district'],
                    'department_id' => (string) $input['station'],
                ]
            );
        }

        // Notify admin
        try {
            $adminEmail = (string) config('ehris.admin_email', 'gavino.tan@deped.gov.ph');
            $adminName = (string) config('ehris.admin_name', 'EHRIS Administrator');

            $district = BusinessUnit::where('BusinessUnitId', $input['district'])->first();
            $station = Department::where('department_id', $input['station'])->first();

            $payload = [
                'name' => $fullname,
                'email' => (string) $user->email,
                'hrid' => $user->hrId ? (int) $user->hrId : null,
                'district_id' => $input['district'] ?? null,
                'district_name' => $district?->BusinessUnit ? (string) $district->BusinessUnit : null,
                'station_id' => $input['station'] ?? null,
                'station_name' => $station?->department_name ? (string) $station->department_name : null,
                'requested_at' => now()->format('Y-m-d H:i'),
            ];

            Log::info('Sending NewUserRegistrationAdminMail', [
                'to' => $adminEmail,
                'name' => $adminName,
                'payload' => $payload,
            ]);

            // Explicitly pass email and name to avoid any ambiguity.
            Mail::to($adminEmail, $adminName)->send(new NewUserRegistrationAdminMail($payload));

            Log::info('NewUserRegistrationAdminMail sent successfully', [
                'to' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send NewUserRegistrationAdminMail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $user;
    }
}
