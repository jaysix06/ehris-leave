<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelfService\StoreLocatorSlipRequest;
use App\Models\Department;
use App\Models\EmpOfficialInfo;
use App\Models\LocatorSlip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LocatorSlipController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('SelfService/LocatorSlip', [
            'employeeProfile' => $this->employeeProfile(request()->user()),
            'filingDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreLocatorSlipRequest $request): RedirectResponse
    {
        if (! Schema::hasTable('tbl_locator_slips')) {
            return back()->withErrors([
                'purpose_of_travel' => 'Locator slip requests are unavailable because the locator slip table is missing.',
            ]);
        }

        $authUser = $request->user();
        $employeeProfile = $this->employeeProfile($authUser);
        $resolvedHrid = $this->resolveHrid($authUser);
        $officialInfo = null;

        if ($resolvedHrid !== null && $resolvedHrid > 0 && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()
                ->where('hrid', $resolvedHrid)
                ->first();
        }

        $slip = LocatorSlip::query()->create([
            'control_no' => $this->nextControlNumber(),
            'hrid' => $resolvedHrid,
            'user_id' => $authUser?->getKey(),
            'rm_assignee_hrid' => $this->resolveReportingManagerHrid($officialInfo),
            'date_of_filing' => now()->toDateString(),
            'employee_name' => $employeeProfile['name'],
            'position_designation' => $employeeProfile['position'],
            'permanent_station' => $employeeProfile['station'],
            'purpose_of_travel' => trim((string) $request->input('purpose_of_travel')),
            'travel_type' => (string) $request->input('travel_type'),
            'travel_date' => (string) $request->input('travel_date'),
            'time_out' => $request->filled('time_out') ? (string) $request->input('time_out') : null,
            'time_in' => $request->filled('time_in') ? (string) $request->input('time_in') : null,
            'destination' => trim((string) $request->input('destination')),
            'workflow_status' => 'pending_rm',
            'rm_status' => 'pending',
            'status' => 'Pending RM Approval',
            'remarks' => null,
        ]);

        $slip->forceFill([
            'control_no' => $this->formatControlNumber((int) $slip->id),
        ])->save();

        return redirect()
            ->route('request-status.my-requests')
            ->with('status', 'Locator slip request submitted successfully.');
    }

    /**
     * @return array{name: string, position: string, station: string}
     */
    private function employeeProfile(mixed $authUser): array
    {
        $profile = null;
        if ($authUser !== null && ! empty($authUser->email) && Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select([
                    'userId',
                    'hrId',
                    'fullname',
                    'firstname',
                    'middlename',
                    'lastname',
                    'extname',
                    'job_title',
                    'department_id',
                ])
                ->where('email', (string) $authUser->email)
                ->first();
        }

        $hrid = (int) ($profile?->hrId ?? $authUser?->hrId ?? 0);
        $officialInfo = null;

        if ($hrid > 0 && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
        }

        $name = trim((string) ($profile?->fullname ?? ''));
        if ($name === '') {
            $name = trim((string) implode(' ', array_filter([
                trim((string) ($profile?->firstname ?? '')),
                trim((string) ($profile?->middlename ?? '')),
                trim((string) ($profile?->lastname ?? '')),
                trim((string) ($profile?->extname ?? '')),
            ])));
        }

        if ($name === '') {
            $name = trim((string) ($authUser?->name ?? $authUser?->email ?? ''));
        }

        $position = trim((string) ($profile?->job_title ?? $officialInfo?->job_title ?? ''));
        if ($position === '') {
            $position = 'Not assigned';
        }

        return [
            'name' => $name,
            'position' => $position,
            'station' => $this->resolvePermanentStation($officialInfo, $profile),
        ];
    }

    private function resolveHrid(mixed $authUser): ?int
    {
        if ($authUser === null) {
            return null;
        }

        $directHrid = (int) ($authUser->hrId ?? $authUser->hrid ?? 0);
        if ($directHrid > 0) {
            return $directHrid;
        }

        $userId = (int) ($authUser->userId ?? $authUser->id ?? $authUser?->getKey() ?? 0);
        if (Schema::hasTable('tbl_user')) {
            if ($userId > 0) {
                $hridByUserId = User::query()
                    ->where('userId', $userId)
                    ->value('hrId');

                if ($hridByUserId !== null && (int) $hridByUserId > 0) {
                    return (int) $hridByUserId;
                }
            }

            foreach (array_filter([
                (string) ($authUser->email ?? ''),
                (string) ($authUser->personal_email ?? ''),
            ]) as $emailCandidate) {
                $hridByEmail = User::query()
                    ->where('email', $emailCandidate)
                    ->orWhere('personal_email', $emailCandidate)
                    ->value('hrId');

                if ($hridByEmail !== null && (int) $hridByEmail > 0) {
                    return (int) $hridByEmail;
                }
            }
        }

        if (Schema::hasTable('tbl_emp_official_info')) {
            if (Schema::hasColumn('tbl_emp_official_info', 'email')) {
                foreach (array_filter([
                    (string) ($authUser->email ?? ''),
                    (string) ($authUser->personal_email ?? ''),
                ]) as $emailCandidate) {
                    $officialHrid = DB::table('tbl_emp_official_info')
                        ->where('email', $emailCandidate)
                        ->value('hrid');

                    if ($officialHrid !== null && (int) $officialHrid > 0) {
                        return (int) $officialHrid;
                    }
                }
            }

            $nameCandidates = array_filter([
                trim((string) ($authUser->fullname ?? '')),
                trim((string) ($authUser->name ?? '')),
            ]);

            foreach ($nameCandidates as $nameCandidate) {
                $officialHrid = DB::table('tbl_emp_official_info')
                    ->whereRaw(
                        "LOWER(TRIM(CONCAT_WS(' ', firstname, middlename, lastname, extension))) = ?",
                        [strtolower($nameCandidate)]
                    )
                    ->value('hrid');

                if ($officialHrid !== null && (int) $officialHrid > 0) {
                    return (int) $officialHrid;
                }
            }
        }

        return $userId > 0 ? $userId : null;
    }

    private function resolveReportingManagerHrid(?EmpOfficialInfo $officialInfo): ?int
    {
        if ($officialInfo === null || ! Schema::hasTable('tbl_emp_official_info')) {
            return null;
        }

        $rawReportingManager = trim((string) ($officialInfo->reporting_manager ?? ''));
        if ($rawReportingManager !== '' && ctype_digit($rawReportingManager)) {
            $hrid = (int) $rawReportingManager;

            return $hrid > 0 ? $hrid : null;
        }

        if ($rawReportingManager !== '') {
            $managerByName = DB::table('tbl_emp_official_info')
                ->select('hrid')
                ->whereRaw(
                    "LOWER(TRIM(CONCAT_WS(' ', firstname, middlename, lastname, extension))) = ?",
                    [strtolower($rawReportingManager)]
                )
                ->first();

            $hridFromName = (int) ($managerByName->hrid ?? 0);
            if ($hridFromName > 0) {
                return $hridFromName;
            }
        }

        $departmentId = trim((string) ($officialInfo->department_id ?? ''));
        if ($departmentId !== '' && ctype_digit($departmentId) && Schema::hasTable('tbl_reporting_manager')) {
            $mappedHrid = DB::table('tbl_reporting_manager')
                ->whereRaw('CAST(department_id AS UNSIGNED) = ?', [(int) $departmentId])
                ->value('manager_name');

            if ($mappedHrid !== null && ctype_digit((string) $mappedHrid)) {
                $hrid = (int) $mappedHrid;
                if ($hrid > 0) {
                    return $hrid;
                }
            }
        }

        return null;
    }

    private function resolvePermanentStation(?EmpOfficialInfo $officialInfo, ?User $profile): string
    {
        $departmentId = trim((string) ($officialInfo?->department_id ?? $profile?->department_id ?? ''));
        if ($departmentId !== '' && Schema::hasTable('tbl_department')) {
            $departmentName = Department::query()
                ->where('department_id', $departmentId)
                ->value('department_name');
            if (is_string($departmentName) && trim($departmentName) !== '') {
                return trim($departmentName);
            }
        }

        $rawOffice = trim((string) ($officialInfo?->office ?? ''));
        if ($rawOffice !== '') {
            return $rawOffice;
        }

        return 'Not assigned';
    }

    private function nextControlNumber(): string
    {
        return $this->formatControlNumber(((int) LocatorSlip::query()->max('id')) + 1);
    }

    private function formatControlNumber(int $id): string
    {
        return 'LS-'.now()->format('Y').'-'.str_pad((string) max($id, 1), 5, '0', STR_PAD_LEFT);
    }
}
