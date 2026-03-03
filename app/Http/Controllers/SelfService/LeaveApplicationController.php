<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmpOfficialInfo;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    private const LEAVE_TABLE = 'tbl_leave_applications';

    private const WORKFLOW_PENDING_RM = 'pending_rm';

    private const WORKFLOW_PENDING_HR = 'pending_hr';

    private const WORKFLOW_PENDING_SDS = 'pending_sds';

    private const WORKFLOW_APPROVED = 'approved';

    private const WORKFLOW_DISAPPROVED = 'disapproved';

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
                ->select('leave_type', 'id')
                ->whereNotNull('leave_type')
                ->where('leave_type', '!=', '')
                ->distinct()
                ->pluck('leave_type')
                ->map(fn ($type) => trim((string) $type))
                ->filter(fn ($type) => $type !== '')
                ->values()
                ->all();
        }

        $mandatoryLeaveSummary = null;
        if ($authUser !== null && Schema::hasTable(self::LEAVE_TABLE)) {
            $currentYear = (int) now()->format('Y');
            $hrid = (int) ($authUser->hrId ?? 0);

            if ($hrid > 0) {
                $usedMandatoryDays = (int) DB::table(self::LEAVE_TABLE)
                    ->where('employee_hrid', $hrid)
                    ->whereYear('leave_start_date', $currentYear)
                    ->whereIn('leave_type', ['Vacation Leave', 'Mandatory/Force Leave', 'Mandatory Leave', 'Forced Leave'])
                    ->sum('leave_days');

                $forfeitedDays = 0;
                if (Schema::hasTable('tbl_leave_history')) {
                    $forfeitedDays = (int) DB::table('tbl_leave_history')
                        ->where('hrid', $hrid)
                        ->where('type', 'Mandatory Leave Forfeiture')
                        ->whereYear('credits_from', $currentYear)
                        ->sum(DB::raw('CAST(no_of_days AS UNSIGNED)'));
                }

                $mandatoryLeaveSummary = [
                    'year' => $currentYear,
                    'usedDays' => min($usedMandatoryDays, 5),
                    'remainingDays' => max(5 - $usedMandatoryDays, 0),
                    'forfeitedDays' => max($forfeitedDays, 0),
                ];
            }
        }

        return Inertia::render('SelfService/LeaveApplication', [
            'leaveEmployee' => $leaveEmployee,
            'leaveTypes' => $leaveTypes,
            'mandatoryLeaveSummary' => $mandatoryLeaveSummary,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type' => ['required', 'string', 'max:255'],
            'leave_start_date' => ['required', 'date'],
            'leave_end_date' => ['required', 'date', 'after_or_equal:leave_start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reason_specify' => ['nullable', 'string', 'max:2000'],
            'commutation' => ['nullable', 'string', 'max:255'],
            'leave_for_mode' => ['nullable', 'string', 'max:100'],
            'consultation_availed' => ['nullable', 'in:yes,no'],
            'destination_scope' => ['nullable', 'in:within_ph,abroad'],
            'destination_details' => ['nullable', 'string', 'max:255'],
            'travel_authority_no' => ['nullable', 'string', 'max:100'],
            'is_mandatory_leave' => ['nullable', 'boolean'],
            'is_emergency_spl' => ['nullable', 'boolean'],
            'emergency_reason' => ['nullable', 'string', 'max:1000'],
            'is_timing_override' => ['nullable', 'boolean'],
            'timing_override_reason' => ['nullable', 'string', 'max:1000'],
            'accident_date' => ['nullable', 'date'],
            'surgery_date' => ['nullable', 'date'],
            'calamity_date' => ['nullable', 'date'],
            'calamity_type' => ['nullable', 'string', 'max:100'],
            'calamity_area' => ['nullable', 'string', 'max:255'],
            'residence_address_snapshot' => ['nullable', 'string', 'max:255'],
            'solo_parent_id_no' => ['nullable', 'string', 'max:100'],
            'solo_parent_id_valid_until' => ['nullable', 'date'],
            'study_contract_id' => ['nullable', 'string', 'max:100'],
            'is_private_physician' => ['nullable', 'boolean'],
            'supervisor_notes' => ['nullable', 'string', 'max:2000'],
            'separation_type' => ['nullable', 'string', 'max:50'],
            'separation_effective_date' => ['nullable', 'date'],
            'credits_monetized' => ['nullable', 'numeric', 'min:0'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'affidavit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'proof_of_delivery' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'police_report' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'government_physician_concurrence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'study_leave_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'solo_parent_id_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'monetization_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'separation_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'adoption_placement_authority' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'supporting_documents' => ['nullable', 'array'],
            'supporting_documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $leaveType = trim((string) $data['leave_type']);
        $start = Carbon::parse($data['leave_start_date'])->startOfDay();
        $end = Carbon::parse($data['leave_end_date'])->startOfDay();
        $today = now()->startOfDay();
        $applicationDate = now()->startOfDay();
        $days = $start->diffInDays($end) + 1;

        $isTimingOverride = (bool) ($data['is_timing_override'] ?? false);
        $timingOverrideReason = trim((string) ($data['timing_override_reason'] ?? ''));
        $isEmergencySpl = (bool) ($data['is_emergency_spl'] ?? false);
        $emergencyReason = trim((string) ($data['emergency_reason'] ?? ''));
        $hasGenericSupportingDocs = $request->hasFile('supporting_documents');

        $isType = static function (string $current, array $allowed): bool {
            $normalized = strtolower(trim($current));
            foreach ($allowed as $candidate) {
                if ($normalized === strtolower(trim($candidate))) {
                    return true;
                }
            }

            return false;
        };

        if ($data['leave_type'] === 'Sick Leave') {
            $filedInAdvance = $start->gt($today);
            $requiresSupportingDoc = $filedInAdvance || $days > 5;

            if ($requiresSupportingDoc) {
                $hasMedical = $request->hasFile('medical_certificate');
                $hasAffidavit = $request->hasFile('affidavit');
                $hasSickLeaveSupport = $hasMedical || $hasAffidavit || $hasGenericSupportingDocs;
                $consultation = $data['consultation_availed'] ?? null;

                if ($consultation === 'yes' && ! ($hasMedical || $hasGenericSupportingDocs)) {
                    throw ValidationException::withMessages([
                        'medical_certificate' => 'Medical certificate is required when medical consultation was availed.',
                    ]);
                }

                if ($consultation === 'no' && ! ($hasAffidavit || $hasGenericSupportingDocs)) {
                    throw ValidationException::withMessages([
                        'affidavit' => 'Affidavit is required when medical consultation was not availed.',
                    ]);
                }

                if ($consultation === null && ! $hasSickLeaveSupport) {
                    throw ValidationException::withMessages([
                        'medical_certificate' => 'Please upload a medical certificate or an affidavit.',
                    ]);
                }
            }
        }

        if ($data['leave_type'] === 'Paternity Leave') {
            if ($days > 7) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Paternity Leave cannot exceed 7 days per application.',
                ]);
            }

            if (! $request->hasFile('proof_of_delivery')) {
                throw ValidationException::withMessages([
                    'proof_of_delivery' => 'Proof of child\'s delivery is required for Paternity Leave (e.g. birth certificate, medical certificate, or marriage contract).',
                ]);
            }
        }

        if ($isType($leaveType, ['Vacation Leave'])) {
            if ($start->lt($today->copy()->addDays(5)) && ! $isTimingOverride) {
                throw ValidationException::withMessages([
                    'leave_start_date' => 'Vacation Leave must be filed at least 5 days in advance unless override is justified.',
                ]);
            }

            if ($start->lt($today->copy()->addDays(5)) && $isTimingOverride && $timingOverrideReason === '') {
                throw ValidationException::withMessages([
                    'timing_override_reason' => 'Please provide a reason for the advance-filing override.',
                ]);
            }

            if (empty($data['destination_scope'])) {
                throw ValidationException::withMessages([
                    'destination_scope' => 'Please indicate if destination is within the Philippines or abroad.',
                ]);
            }
        }

        if ($isType($leaveType, ['Mandatory/Force Leave', 'Mandatory / Force Leave', 'Mandatory/Forced Leave', 'Mandatory Leave', 'Forced Leave'])) {
            if ($days > 5) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Mandatory/Forced Leave cannot exceed 5 days.',
                ]);
            }
        }

        if ($isType($leaveType, ['Maternity Leave'])) {
            if ($days > 105) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Maternity Leave cannot exceed 105 days.',
                ]);
            }

            if (! $request->hasFile('medical_certificate') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'medical_certificate' => 'Please upload proof of pregnancy (ultrasound or doctor certificate).',
                ]);
            }
        }

        if ($isType($leaveType, ['Special Privilege Leave'])) {
            if ($days > 3) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Special Privilege Leave cannot exceed 3 days.',
                ]);
            }

            if ($start->lt($today->copy()->addDays(7)) && ! $isEmergencySpl) {
                throw ValidationException::withMessages([
                    'leave_start_date' => 'Special Privilege Leave must be filed at least 7 days in advance unless emergency is declared.',
                ]);
            }

            if ($isEmergencySpl && $emergencyReason === '') {
                throw ValidationException::withMessages([
                    'emergency_reason' => 'Emergency reason is required for Special Privilege Leave emergency filing.',
                ]);
            }

            if (empty($data['destination_scope'])) {
                throw ValidationException::withMessages([
                    'destination_scope' => 'Please indicate if destination is within the Philippines or abroad.',
                ]);
            }
        }

        if ($isType($leaveType, ['Solo Parent Leave'])) {
            if ($days > 7) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Solo Parent Leave cannot exceed 7 days.',
                ]);
            }

            if ($start->lt($today->copy()->addDays(5)) && ! $isTimingOverride) {
                throw ValidationException::withMessages([
                    'leave_start_date' => 'Solo Parent Leave should be filed at least 5 days in advance unless override is justified.',
                ]);
            }

            if ($start->lt($today->copy()->addDays(5)) && $isTimingOverride && $timingOverrideReason === '') {
                throw ValidationException::withMessages([
                    'timing_override_reason' => 'Please provide a reason for the filing-timing override.',
                ]);
            }

            if (! $request->hasFile('solo_parent_id_attachment') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'solo_parent_id_attachment' => 'Updated Solo Parent ID attachment is required.',
                ]);
            }
        }

        if ($isType($leaveType, ['Study Leave'])) {
            if ($days > 180) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Study Leave cannot exceed 6 months (180 days).',
                ]);
            }

            if (! $request->hasFile('study_leave_contract') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'study_leave_contract' => 'Signed study leave contract is required.',
                ]);
            }
        }

        if ($isType($leaveType, ['VAWC Leave', '10-Day VAWC Leave'])) {
            if ($days > 10) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'VAWC Leave cannot exceed 10 days.',
                ]);
            }

            $hasProtectionOrderProof = $hasGenericSupportingDocs;
            $hasPoliceReport = $request->hasFile('police_report');
            $hasMedical = $request->hasFile('medical_certificate');

            if (! $hasProtectionOrderProof && ! ($hasPoliceReport && $hasMedical)) {
                throw ValidationException::withMessages([
                    'supporting_documents' => 'Upload a protection-order related document, or upload both police report and medical certificate.',
                ]);
            }
        }

        if ($isType($leaveType, ['Rehabilitation Leave', 'Rehabilitation Privilege'])) {
            if ($days > 180) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Rehabilitation Leave cannot exceed 6 months (180 days).',
                ]);
            }

            if (empty($data['accident_date'])) {
                throw ValidationException::withMessages([
                    'accident_date' => 'Accident date is required for Rehabilitation Leave.',
                ]);
            }

            $accidentDate = Carbon::parse($data['accident_date'])->startOfDay();
            if ($applicationDate->gt($accidentDate->copy()->addDays(7)) && ! $isTimingOverride) {
                throw ValidationException::withMessages([
                    'accident_date' => 'Rehabilitation Leave must be filed within 7 days from accident date unless override is justified.',
                ]);
            }

            if ($applicationDate->gt($accidentDate->copy()->addDays(7)) && $isTimingOverride && $timingOverrideReason === '') {
                throw ValidationException::withMessages([
                    'timing_override_reason' => 'Please provide the justification for late Rehabilitation Leave filing.',
                ]);
            }

            if (! $request->hasFile('medical_certificate')) {
                throw ValidationException::withMessages([
                    'medical_certificate' => 'Medical certificate is required for Rehabilitation Leave.',
                ]);
            }

            if (! $hasGenericSupportingDocs && ! $request->hasFile('police_report')) {
                throw ValidationException::withMessages([
                    'supporting_documents' => 'Please upload the letter request/report documents (or police report if applicable).',
                ]);
            }

            if ((bool) ($data['is_private_physician'] ?? false) && ! $request->hasFile('government_physician_concurrence')) {
                throw ValidationException::withMessages([
                    'government_physician_concurrence' => 'Government physician concurrence is required when attending physician is private.',
                ]);
            }
        }

        if ($isType($leaveType, ['Special Leave Benefits for Women'])) {
            if ($days > 60) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Special Leave Benefits for Women cannot exceed 2 months (60 days).',
                ]);
            }

            if (! empty($data['surgery_date'])) {
                $surgeryDate = Carbon::parse($data['surgery_date'])->startOfDay();
                if ($start->gte($today) && $applicationDate->gt($surgeryDate->copy()->subDays(5)) && $applicationDate->lt($surgeryDate)) {
                    throw ValidationException::withMessages([
                        'surgery_date' => 'Advance filing should be at least 5 days before surgery date.',
                    ]);
                }
            }

            if (! $hasGenericSupportingDocs && ! $request->hasFile('medical_certificate')) {
                throw ValidationException::withMessages([
                    'supporting_documents' => 'Please upload required gynecological surgery medical documents.',
                ]);
            }
        }

        if ($isType($leaveType, ['Special Emergency (Calamity) Leave'])) {
            if ($days > 5) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Special Emergency (Calamity) Leave cannot exceed 5 days.',
                ]);
            }

            if (empty($data['calamity_date'])) {
                throw ValidationException::withMessages([
                    'calamity_date' => 'Calamity date is required.',
                ]);
            }

            $calamityDate = Carbon::parse($data['calamity_date'])->startOfDay();
            if ($start->lt($calamityDate) || $end->gt($calamityDate->copy()->addDays(30))) {
                throw ValidationException::withMessages([
                    'leave_start_date' => 'Calamity leave must be taken within 30 days from the calamity date.',
                ]);
            }

            if (! Schema::hasTable(self::LEAVE_TABLE)) {
                throw ValidationException::withMessages([
                    'leave_type' => 'Calamity leave cannot be checked because leave application table is unavailable.',
                ]);
            }

            $existingCalamityDays = (int) DB::table(self::LEAVE_TABLE)
                ->where('employee_hrid', $request->user()?->hrId)
                ->where('leave_type', 'Special Emergency (Calamity) Leave')
                ->whereYear('leave_start_date', $start->year)
                ->sum('leave_days');

            if ($existingCalamityDays + $days > 5) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Special Emergency (Calamity) Leave yearly total cannot exceed 5 days.',
                ]);
            }

            if (Schema::hasColumn(self::LEAVE_TABLE, 'calamity_date')) {
                $hasDifferentEvent = DB::table(self::LEAVE_TABLE)
                    ->where('employee_hrid', $request->user()?->hrId)
                    ->where('leave_type', 'Special Emergency (Calamity) Leave')
                    ->whereYear('leave_start_date', $start->year)
                    ->whereNotNull('calamity_date')
                    ->whereDate('calamity_date', '!=', $calamityDate->toDateString())
                    ->exists();

                if ($hasDifferentEvent) {
                    throw ValidationException::withMessages([
                        'calamity_date' => 'Special Emergency Leave can only be granted for one calamity event per year.',
                    ]);
                }
            }
        }

        if ($isType($leaveType, ['Monetization of Leave Credits'])) {
            if (empty($data['credits_monetized'])) {
                throw ValidationException::withMessages([
                    'credits_monetized' => 'Please provide the number of credits to monetize.',
                ]);
            }

            $availableCredits = null;
            if (Schema::hasTable('tbl_emp_official_info')) {
                $availableCredits = EmpOfficialInfo::query()
                    ->where('hrid', $request->user()?->hrId)
                    ->value('leave_balance');
            }

            if (is_numeric($availableCredits)) {
                $available = (float) $availableCredits;
                $requestedMonetized = (float) $data['credits_monetized'];
                if ($requestedMonetized > $available) {
                    throw ValidationException::withMessages([
                        'credits_monetized' => 'Credits monetized cannot exceed available leave balance.',
                    ]);
                }

                if ($requestedMonetized >= ($available * 0.5) && ! $request->hasFile('monetization_letter') && ! $hasGenericSupportingDocs) {
                    throw ValidationException::withMessages([
                        'monetization_letter' => 'Letter request is required when monetization is 50% or more of available credits.',
                    ]);
                }
            } elseif (! $request->hasFile('monetization_letter') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'monetization_letter' => 'Letter request attachment is required when available credits cannot be validated.',
                ]);
            }
        }

        if ($isType($leaveType, ['Terminal Leave'])) {
            if (empty($data['separation_type'])) {
                throw ValidationException::withMessages([
                    'separation_type' => 'Separation type is required for Terminal Leave.',
                ]);
            }

            if (empty($data['separation_effective_date'])) {
                throw ValidationException::withMessages([
                    'separation_effective_date' => 'Effective separation date is required for Terminal Leave.',
                ]);
            }

            if (! $request->hasFile('separation_proof') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'separation_proof' => 'Please upload resignation/retirement/separation proof.',
                ]);
            }
        }

        if ($isType($leaveType, ['Adoption Leave'])) {
            if ($days > 60) {
                throw ValidationException::withMessages([
                    'leave_end_date' => 'Adoption Leave currently follows a default cap of 60 days (configurable per agency policy).',
                ]);
            }

            if (! $request->hasFile('adoption_placement_authority') && ! $hasGenericSupportingDocs) {
                throw ValidationException::withMessages([
                    'adoption_placement_authority' => 'Authenticated Pre-Adoptive Placement Authority is required.',
                ]);
            }
        }

        if (! Schema::hasTable(self::LEAVE_TABLE)) {
            throw ValidationException::withMessages([
                'leave_type' => 'Cannot submit leave application because leave application table is missing.',
            ]);
        }

        $authUser = $request->user();
        $profile = null;
        $officialInfo = null;
        if ($authUser !== null && Schema::hasTable('tbl_user')) {
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
        }

        $hrid = (int) ($profile?->hrId ?? $authUser?->hrId ?? 0);
        if ($hrid > 0 && Schema::hasTable('tbl_emp_official_info')) {
            $officialInfo = EmpOfficialInfo::query()->where('hrid', $hrid)->first();
        }

        $salaryAmount = null;
        if ($officialInfo !== null) {
            $salaryAmount = $officialInfo->salary_actual ?? $officialInfo->salary_authorized ?? null;
        }

        $attachmentMeta = [];
        foreach ([
            'medical_certificate',
            'affidavit',
            'proof_of_delivery',
            'police_report',
            'government_physician_concurrence',
            'study_leave_contract',
            'solo_parent_id_attachment',
            'monetization_letter',
            'separation_proof',
            'adoption_placement_authority',
        ] as $fileField) {
            if ($request->hasFile($fileField)) {
                $attachmentMeta[$fileField] = $request->file($fileField)->store('leave-attachments');
            }
        }

        if ($request->hasFile('supporting_documents')) {
            $attachmentMeta['supporting_documents'] = [];
            foreach ((array) $request->file('supporting_documents', []) as $uploadedFile) {
                if ($uploadedFile === null) {
                    continue;
                }
                $attachmentMeta['supporting_documents'][] = $uploadedFile->store('leave-attachments');
            }
        }

        $primaryAttachment = null;
        foreach ($attachmentMeta as $value) {
            if (is_string($value) && trim($value) !== '') {
                $primaryAttachment = $value;
                break;
            }
            if (is_array($value)) {
                foreach ($value as $path) {
                    if (is_string($path) && trim($path) !== '') {
                        $primaryAttachment = $path;
                        break 2;
                    }
                }
            }
        }

        $nameSource = $profile?->fullname ?: trim(implode(' ', array_filter([
            $profile?->firstname,
            $profile?->middlename,
            $profile?->lastname,
            $profile?->extname,
        ])));

        $firstname = trim((string) ($profile?->firstname ?? ''));
        $middlename = trim((string) ($profile?->middlename ?? ''));
        $lastname = trim((string) ($profile?->lastname ?? ''));
        $extension = trim((string) ($profile?->extname ?? ''));
        if ($nameSource !== '' && $firstname === '' && $lastname === '') {
            $parts = preg_split('/\s+/', $nameSource) ?: [];
            $firstname = (string) array_shift($parts);
            $lastname = count($parts) > 0 ? (string) array_pop($parts) : '';
            $middlename = count($parts) > 0 ? implode(' ', $parts) : '';
        }

        $unifiedReason = null;
        if (! empty($data['reason'])) {
            $unifiedReason = mb_substr(trim((string) $data['reason']), 0, 255);
        }

        $destinationScope = $data['destination_scope'] ?? null;
        $reasonForLeave = $destinationScope === 'within_ph' ? 'within' : ($destinationScope === 'abroad' ? 'abroad' : null);

        $rmAssigneeHrid = $this->resolveReportingManagerHrid($officialInfo);

        $payload = [
            'employee_hrid' => $hrid > 0 ? $hrid : null,
            'employee_id' => $officialInfo?->employee_id ?? null,
            'rm_assignee_hrid' => $rmAssigneeHrid,
            'leave_type' => $leaveType,
            'leave_for' => (string) ($request->input('leave_for_mode', 'Within selected date range')),
            'leave_start_date' => $start->toDateString(),
            'leave_end_date' => $end->toDateString(),
            'leave_days' => $days,
            'reason_for_leave' => $reasonForLeave,
            'reason_text' => $request->filled('reason_specify')
                ? mb_substr(trim((string) $request->input('reason_specify')), 0, 2000)
                : $unifiedReason,
            'commutation' => ! empty($data['commutation']) ? strtolower((string) $data['commutation']) : null,
            'supervisor_reviewer_notes' => $data['supervisor_notes'] ?? null,
            'supporting_documents' => $attachmentMeta !== [] ? json_encode($attachmentMeta) : null,
            'date_applied' => now(),
            'workflow_status' => self::WORKFLOW_PENDING_RM,
            'rm_status' => 'pending',
            'hr_status' => 'pending',
            'sds_status' => 'pending',
            // Keep this for calamity policy checks.
            'calamity_date' => $data['calamity_date'] ?? null,
        ];

        DB::table(self::LEAVE_TABLE)->insert($payload);

        return back()->with('status', 'Leave application submitted.');
    }

    public function approvals(Request $request): JsonResponse
    {
        if (! Schema::hasTable(self::LEAVE_TABLE)) {
            return response()->json(['data' => []]);
        }

        $authRole = strtolower((string) ($request->user()?->role ?? ''));
        $authHrid = (int) ($request->user()?->hrId ?? 0);
        $limit = max(1, min((int) $request->integer('limit', 100), 500));

        $query = DB::table(self::LEAVE_TABLE)
            ->orderByDesc('date_applied')
            ->limit($limit);

        if (str_contains($authRole, 'reporting manager')) {
            $query->where('workflow_status', self::WORKFLOW_PENDING_RM);
            if (Schema::hasColumn(self::LEAVE_TABLE, 'rm_assignee_hrid') && $authHrid > 0) {
                $query->where('rm_assignee_hrid', $authHrid);
            }
        } elseif (str_contains($authRole, 'hr')) {
            $query->where('workflow_status', self::WORKFLOW_PENDING_HR);
        } elseif (str_contains($authRole, 'sds')) {
            $query->where('workflow_status', self::WORKFLOW_PENDING_SDS);
        } else {
            // Non-approver roles cannot see approver queues.
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function decide(Request $request, int $id): JsonResponse
    {
        if (! Schema::hasTable(self::LEAVE_TABLE)) {
            return response()->json(['message' => 'Leave application table not found.'], 422);
        }

        $payload = $request->validate([
            'decision' => ['required', 'in:approve,disapprove'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $leave = DB::table(self::LEAVE_TABLE)
            ->where('leave_application_id', $id)
            ->first();

        if (! $leave) {
            return response()->json(['message' => 'Leave application not found.'], 404);
        }

        $authRole = strtolower((string) ($request->user()?->role ?? ''));
        $authHrid = (int) ($request->user()?->hrId ?? 0);
        $decision = $payload['decision'];
        $remarks = $payload['remarks'] ?? null;
        $now = now();
        $updates = [];

        if (str_contains($authRole, 'reporting manager')) {
            if ($leave->workflow_status !== self::WORKFLOW_PENDING_RM) {
                return response()->json(['message' => 'This request is not pending RM approval.'], 422);
            }
            if (isset($leave->rm_assignee_hrid) && (int) $leave->rm_assignee_hrid > 0 && (int) $leave->rm_assignee_hrid !== $authHrid) {
                return response()->json(['message' => 'You are not assigned to this leave request.'], 403);
            }

            $updates['rm_status'] = $decision === 'approve' ? 'approved' : 'disapproved';
            $updates['rm_action_at'] = $now;
            $updates['rm_acted_by'] = $authHrid > 0 ? $authHrid : null;
            $updates['rm_remarks'] = $remarks;
            $updates['workflow_status'] = $decision === 'approve'
                ? self::WORKFLOW_PENDING_HR
                : self::WORKFLOW_DISAPPROVED;
        } elseif (str_contains($authRole, 'hr')) {
            if ($leave->workflow_status !== self::WORKFLOW_PENDING_HR) {
                return response()->json(['message' => 'This request is not pending HR approval.'], 422);
            }

            $updates['hr_status'] = $decision === 'approve' ? 'approved' : 'disapproved';
            $updates['hr_action_at'] = $now;
            $updates['hr_acted_by'] = $authHrid > 0 ? $authHrid : null;
            $updates['hr_remarks'] = $remarks;
            $updates['workflow_status'] = $decision === 'approve'
                ? self::WORKFLOW_PENDING_SDS
                : self::WORKFLOW_DISAPPROVED;
        } elseif (str_contains($authRole, 'sds')) {
            if ($leave->workflow_status !== self::WORKFLOW_PENDING_SDS) {
                return response()->json(['message' => 'This request is not pending SDS approval.'], 422);
            }

            $updates['sds_status'] = $decision === 'approve' ? 'approved' : 'disapproved';
            $updates['sds_action_at'] = $now;
            $updates['sds_acted_by'] = $authHrid > 0 ? $authHrid : null;
            $updates['sds_remarks'] = $remarks;
            $updates['workflow_status'] = $decision === 'approve'
                ? self::WORKFLOW_APPROVED
                : self::WORKFLOW_DISAPPROVED;
        } else {
            return response()->json(['message' => 'You are not authorized to decide this request.'], 403);
        }

        DB::table(self::LEAVE_TABLE)
            ->where('leave_application_id', $id)
            ->update(array_merge($updates, ['updated_at' => $now]));

        return response()->json(['message' => 'Decision saved.']);
    }

    private function resolveReportingManagerHrid(?EmpOfficialInfo $officialInfo): ?int
    {
        if ($officialInfo === null || ! Schema::hasTable('tbl_emp_official_info')) {
            return null;
        }

        $reportingQuery = EmpOfficialInfo::query()->where('role', 'Reporting Manager');

        $departmentId = trim((string) ($officialInfo->department_id ?? ''));
        $rawOffice = trim((string) ($officialInfo->office ?? ''));

        if ($departmentId !== '' && ctype_digit($departmentId)) {
            $reportingQuery->where('department_id', $departmentId);
        }

        if ($rawOffice !== '') {
            $reportingQuery->where('office', $rawOffice);
        }

        $reportingRecord = $reportingQuery->select(['hrid'])->first();
        $hrid = (int) ($reportingRecord->hrid ?? 0);

        return $hrid > 0 ? $hrid : null;
    }
}
