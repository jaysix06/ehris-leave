<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmpOfficialInfo;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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

            $reportingManager = null;
            if ($officialInfo !== null && Schema::hasTable('tbl_emp_official_info')) {
                $reportingQuery = EmpOfficialInfo::query()->where('role', 'Reporting Manager');

                $departmentId = trim((string) ($officialInfo->department_id ?? ''));
                $rawOffice = trim((string) ($officialInfo->office ?? ''));

                if ($departmentId !== '' && ctype_digit($departmentId)) {
                    $reportingQuery->where('department_id', $departmentId);
                } elseif ($officeSchool !== null && Schema::hasTable('tbl_department')) {
                    $deptId = Department::query()
                        ->where('department_name', $officeSchool)
                        ->value('department_id');
                    if ($deptId !== null) {
                        $reportingQuery->where('department_id', (string) $deptId);
                    }
                }

                if ($rawOffice !== '') {
                    $reportingQuery->where('office', $rawOffice);
                } elseif ($officeSchool !== null && Schema::hasTable('tbl_office')) {
                    $officeId = Office::query()
                        ->where('office_name', $officeSchool)
                        ->value('office_Id');
                    if ($officeId !== null) {
                        $reportingQuery->where('office', (string) $officeId);
                    } else {
                        $reportingQuery->where('office', $officeSchool);
                    }
                } elseif ($officeSchool !== null) {
                    $reportingQuery->where('office', $officeSchool);
                }

                $reportingRecord = $reportingQuery->first();
                if ($reportingRecord !== null) {
                    $nameParts = array_filter([
                        $reportingRecord->firstname ?? null,
                        $reportingRecord->middlename ?? null,
                        $reportingRecord->lastname ?? null,
                        $reportingRecord->extension ?? null,
                    ], fn ($part) => is_string($part) && trim($part) !== '');
                    $reportingManager = $nameParts !== [] ? trim(implode(' ', $nameParts)) : null;
                }
            }

            $leaveEmployee = [
                'name' => $fullName !== '' ? $fullName : ($authUser->name ?? null),
                'position' => $officialInfo->job_title ?? $profile?->job_title ?? null,
                'officeSchool' => $officeSchool,
                'reportingManager' => $reportingManager,
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type' => ['required', 'string', 'max:255'],
            'leave_start_date' => ['required', 'date'],
            'leave_end_date' => ['required', 'date', 'after_or_equal:leave_start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'commutation' => ['nullable', 'string', 'max:255'],
            'consultation_availed' => ['nullable', 'in:yes,no'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'affidavit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'birth_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'paternity_medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'marriage_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($data['leave_type'] === 'Sick Leave') {
            $start = Carbon::parse($data['leave_start_date'])->startOfDay();
            $end = Carbon::parse($data['leave_end_date'])->startOfDay();
            $today = now()->startOfDay();
            $filedInAdvance = $start->gt($today);
            $days = $start->diffInDays($end) + 1;
            $requiresSupportingDoc = $filedInAdvance || $days > 5;

            if ($requiresSupportingDoc) {
                $hasMedical = $request->hasFile('medical_certificate');
                $hasAffidavit = $request->hasFile('affidavit');
                $consultation = $data['consultation_availed'] ?? null;

                if ($consultation === 'yes' && ! $hasMedical) {
                    throw ValidationException::withMessages([
                        'medical_certificate' => 'Medical certificate is required when medical consultation was availed.',
                    ]);
                }

                if ($consultation === 'no' && ! $hasAffidavit) {
                    throw ValidationException::withMessages([
                        'affidavit' => 'Affidavit is required when medical consultation was not availed.',
                    ]);
                }

                if ($consultation === null && ! ($hasMedical || $hasAffidavit)) {
                    throw ValidationException::withMessages([
                        'medical_certificate' => 'Please upload a medical certificate or an affidavit.',
                    ]);
                }
            }
        }

        if ($data['leave_type'] === 'Paternity Leave') {
            $start = Carbon::parse($data['leave_start_date'])->startOfDay();
            $end = Carbon::parse($data['leave_end_date'])->startOfDay();
            $days = $start->diffInDays($end) + 1;

            if ($days > 7) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Paternity Leave cannot exceed 7 days per application.',
                ]);
            }

            $hasBirthCertificate = $request->hasFile('birth_certificate');
            $hasPaternityMedical = $request->hasFile('paternity_medical_certificate');
            $hasMarriageContract = $request->hasFile('marriage_contract');

            if (! $hasBirthCertificate) {
                throw ValidationException::withMessages([
                    'birth_certificate' => 'Birth certificate is required for Paternity Leave.',
                ]);
            }

            if (! ($hasPaternityMedical || $hasMarriageContract)) {
                throw ValidationException::withMessages([
                    'paternity_medical_certificate' => 'Upload either a medical certificate (proof of delivery) or a marriage contract for Paternity Leave.',
                ]);
            }
        }

        return back()->with('status', 'Leave application submitted.');
    }
}
