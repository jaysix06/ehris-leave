<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmpOfficialInfo;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    public function show(Request $request)
    {
        $authUser = $request->user();
        $leaveEmployee = null;
        $leaveTypes = [];

        if ($authUser && Schema::hasTable('tbl_user')) {
            $profile = User::query()
                ->select([
                    'hrId',
                    'email',
                    'lastname',
                    'firstname',
                    'middlename',
                    'extname',
                    'job_title',
                    'fullname',
                ])
                ->where('email', $authUser->email)
                ->first();

            $hrid = $profile?->hrId ?? $authUser->hrId ?? null;
            $officialInfo = null;

            if (Schema::hasTable('tbl_emp_official_info')) {
                if ($hrid !== null) {
                    $officialInfo = EmpOfficialInfo::query()
                        ->where('hrid', $hrid)
                        ->first();
                }

                if ($officialInfo === null) {
                    $officialInfo = EmpOfficialInfo::query()
                        ->where('email', $authUser->email)
                        ->first();
                }
            }

            $officeSchool = null;
            if ($officialInfo !== null) {
                $rawOffice = trim((string) ($officialInfo->office ?? ''));
                if ($rawOffice !== '') {
                    $officeSchool = $rawOffice;
                }

                $departmentId = trim((string) ($officialInfo->department_id ?? ''));
                if ($departmentId !== '' && ctype_digit($departmentId) && Schema::hasTable('tbl_department')) {
                    $departmentName = Department::query()
                        ->where('department_id', (int) $departmentId)
                        ->value('department_name');
                    if (is_string($departmentName) && trim($departmentName) !== '') {
                        $officeSchool = trim($departmentName);
                    }
                }

                if (
                    ($officeSchool === null || ctype_digit(trim((string) $officeSchool)))
                    && $rawOffice !== ''
                    && ctype_digit($rawOffice)
                    && Schema::hasTable('tbl_office')
                ) {
                    $officeName = Office::query()
                        ->where('office_Id', (int) $rawOffice)
                        ->value('office_name');

                    if (is_string($officeName) && trim($officeName) !== '') {
                        $officeSchool = trim($officeName);
                    }
                }
            }

            $fullName = trim((string) ($profile?->fullname ?? ''));
            if ($fullName === '') {
                $nameParts = array_filter([
                    $profile?->firstname ?? null,
                    $profile?->middlename ?? null,
                    $profile?->lastname ?? null,
                    $profile?->extname ?? null,
                ], fn ($part) => is_string($part) && trim($part) !== '');

                $fullName = $nameParts !== [] ? trim(implode(' ', $nameParts)) : '';
            }

            $leaveEmployee = [
                'name' => $fullName !== '' ? $fullName : ($authUser->name ?? null),
                'position' => $officialInfo->job_title ?? $profile?->job_title ?? null,
                'officeSchool' => $officeSchool,
                'salaryGrade' => $officialInfo->salary_grade ?? null,
                'salaryStep' => $officialInfo->salary_step ?? $officialInfo->step ?? null,
                'salaryAmount' => $officialInfo->salary_actual ?? $officialInfo->salary_authorized ?? null,
            ];
        }

        if (Schema::hasTable('tbl_leave_type')) {
            $leaveTypes = LeaveType::query()
                ->select('leave_type')
                ->whereNotNull('leave_type')
                ->where('leave_type', '!=', '')
                ->distinct()
                ->orderBy('id')
                ->pluck('leave_type')
                ->map(fn ($type) => trim((string) $type))
                ->filter(fn ($type) => $type !== '')
                ->values()
                ->all();
        }

        return Inertia::render('SelfService/LeaveApplication', [
            'leaveEmployee' => $leaveEmployee,
            'leaveTypes' => $leaveTypes,
        ]);
    }
}
