<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MyDetails\PdsExportHandler;
use App\Models\Affiliation;
use App\Models\Awards;
use App\Models\Document;
use App\Models\EmpCivilServiceInfo;
use App\Models\EmpEducationInfo;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\EmpServiceRecord;
use App\Models\EmpTraining;
use App\Models\EmpWorkExperienceInfo;
use App\Models\Expertise;
use App\Models\FamilyInfo;
use App\Models\LeaveHistory;
use App\Models\Performance;
use App\Models\Researches;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyDetailsController extends Controller
{
    public function show(Request $request)
    {
        $authUser = $request->user();
        $dbProfile = null;
        $hrid = null;

        $data = [
            'officialInfo' => null,
            'personalInfo' => null,
            'contactInfo' => null,
            'family' => [],
            'education' => [],
            'workExperience' => [],
            'eligibility' => [],
            'serviceRecord' => [],
            'leaveHistory' => [],
            'documents' => [],
            'voluntaryWork' => [],
            'training' => [],
            'awards' => [],
            'performance' => [],
            'researches' => [],
            'expertise' => [],
            'affiliation' => [],
        ];

        if ($authUser && Schema::hasTable('tbl_user')) {
            $dbProfile = User::query()
                ->select([
                    'hrId',
                    'email',
                    'personal_email',
                    'lastname',
                    'firstname',
                    'middlename',
                    'extname',
                    'avatar',
                    'job_title',
                    'role',
                    'fullname',
                ])
                ->where('email', $authUser->email)
                ->first();
            $hrid = $dbProfile?->hrId ?? $authUser?->hrId ?? $authUser?->id ?? null;
        }

        if ($hrid !== null) {
            $tables = [
                'tbl_emp_official_info' => [
                    'key' => 'officialInfo',
                    'type' => 'single',
                    'query' => fn () => EmpOfficialInfo::query()->where('hrid', $hrid)->first(),
                ],
                'tbl_emp_personal_info' => [
                    'key' => 'personalInfo',
                    'type' => 'single',
                    'query' => fn () => EmpPersonalInfo::query()->where('hrid', $hrid)->first(),
                ],
                'tbl_emp_family_info' => [
                    'key' => 'family',
                    'type' => 'collection',
                    'query' => fn () => FamilyInfo::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_contact_info' => [
                    'key' => 'contactInfo',
                    'type' => 'single',
                    'query' => fn () => DB::table('tbl_emp_contact_info')->where('hrid', $hrid)->first(),
                ],
                'tbl_emp_education_info' => [
                    'key' => 'education',
                    'type' => 'collection',
                    'query' => fn () => EmpEducationInfo::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_work_experience_info' => [
                    'key' => 'workExperience',
                    'type' => 'collection',
                    'query' => fn () => EmpWorkExperienceInfo::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_civil_service_info' => [
                    'key' => 'eligibility',
                    'type' => 'collection',
                    'query' => fn () => EmpCivilServiceInfo::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_service_record' => [
                    'key' => 'serviceRecord',
                    'type' => 'collection',
                    'query' => fn () => EmpServiceRecord::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_leave_history' => [
                    'key' => 'leaveHistory',
                    'type' => 'collection',
                    'query' => fn () => LeaveHistory::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_document' => [
                    'key' => 'documents',
                    'type' => 'collection',
                    'query' => fn () => Document::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_voluntary_work' => [
                    'key' => 'voluntaryWork',
                    'type' => 'collection',
                    'query' => fn () => DB::table('tbl_emp_voluntary_work')->where('hrid', $hrid)->get(),
                ],
                'tbl_emp_training' => [
                    'key' => 'training',
                    'type' => 'collection',
                    'query' => fn () => EmpTraining::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_awards' => [
                    'key' => 'awards',
                    'type' => 'collection',
                    'query' => fn () => Awards::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_performance' => [
                    'key' => 'performance',
                    'type' => 'collection',
                    'query' => fn () => Performance::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_researches' => [
                    'key' => 'researches',
                    'type' => 'collection',
                    'query' => fn () => Researches::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_expertise' => [
                    'key' => 'expertise',
                    'type' => 'collection',
                    'query' => fn () => Expertise::query()->where('hrid', $hrid)->get(),
                ],
                'tbl_affiliation' => [
                    'key' => 'affiliation',
                    'type' => 'collection',
                    'query' => fn () => Affiliation::query()->where('hrid', $hrid)->get(),
                ],
            ];

            foreach ($tables as $table => $config) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                try {
                    $result = $config['query']();
                    if ($config['type'] === 'collection') {
                        $result = $result->all();
                    }
                    $data[$config['key']] = $result;
                } catch (\Throwable $e) {
                    // skip if table missing or query fails
                }
            }

            if ($data['voluntaryWork'] === [] && Schema::hasTable('tbl_voluntary_work')) {
                $data['voluntaryWork'] = DB::table('tbl_voluntary_work')->where('hrid', $hrid)->get()->all();
            }
            if ($data['voluntaryWork'] === [] && $data['affiliation'] !== []) {
                $data['voluntaryWork'] = $data['affiliation'];
            }

            // Resolve stored lookup codes/ids to display names for existing users.
            if (isset($data['contactInfo']) && is_object($data['contactInfo'])) {
                $contact = $data['contactInfo'];
                $contact->residential_barangay_name = $this->resolveLookupNameByValue(
                    'tbl_barangay',
                    $contact->barangay ?? null,
                    ['barangay_name'],
                    ['barangay_code', 'barangay_id'],
                );
                $contact->residential_city_name = $this->resolveLookupNameByValue(
                    'tbl_municipality',
                    $contact->city_municipality ?? null,
                    ['municipal_name'],
                    ['municipal_code', 'municipal_id'],
                );
                $contact->residential_province_name = $this->resolveLookupNameByValue(
                    'tbl_province',
                    $contact->province ?? null,
                    ['province_name'],
                    ['province_code', 'province_id'],
                );
                $contact->residential_region_name = $this->resolveLookupNameByValue(
                    'tbl_region',
                    $contact->region ?? null,
                    ['region_name', 'name'],
                    ['region_code', 'region_id'],
                );

                $contact->permanent_barangay_name = $this->resolveLookupNameByValue(
                    'tbl_barangay',
                    $contact->barangay1 ?? null,
                    ['barangay_name'],
                    ['barangay_code', 'barangay_id'],
                );
                $contact->permanent_city_name = $this->resolveLookupNameByValue(
                    'tbl_municipality',
                    $contact->city_municipality1 ?? null,
                    ['municipal_name'],
                    ['municipal_code', 'municipal_id'],
                );
                $contact->permanent_province_name = $this->resolveLookupNameByValue(
                    'tbl_province',
                    $contact->province1 ?? null,
                    ['province_name'],
                    ['province_code', 'province_id'],
                );
                $contact->permanent_region_name = $this->resolveLookupNameByValue(
                    'tbl_region',
                    $contact->region1 ?? null,
                    ['region_name', 'name'],
                    ['region_code', 'region_id'],
                );
            }

            if (isset($data['officialInfo']) && $data['officialInfo'] instanceof EmpOfficialInfo) {
                $official = $data['officialInfo'];
                $managerHrid = null;

                $businessUnitName = null;
                if (! empty($official->business_id) && Schema::hasTable('tbl_business')) {
                    $businessQuery = DB::table('tbl_business')->select('BusinessUnit');
                    if (Schema::hasColumn('tbl_business', 'BusinessUnitId')) {
                        $businessQuery->where('BusinessUnitId', $official->business_id);
                    } elseif (Schema::hasColumn('tbl_business', 'business_id')) {
                        $businessQuery->where('business_id', $official->business_id);
                    }
                    $businessUnitName = $businessQuery->value('BusinessUnit');
                }
                if (is_string($businessUnitName) && trim($businessUnitName) !== '') {
                    $official->business_unit_name = trim($businessUnitName);
                    $official->division_office_name = trim($businessUnitName);
                }

                $departmentName = null;
                if (Schema::hasTable('tbl_department') && Schema::hasColumn('tbl_department', 'department_name')) {
                    if (! empty($official->department_id)) {
                        $departmentName = DB::table('tbl_department')
                            ->where('department_id', $official->department_id)
                            ->value('department_name');
                    }
                    if (($departmentName === null || $departmentName === '') && ! empty($official->office) && ctype_digit(trim((string) $official->office))) {
                        $departmentName = DB::table('tbl_department')
                            ->where('department_id', (int) $official->office)
                            ->value('department_name');
                    }
                }
                if (is_string($departmentName) && trim($departmentName) !== '') {
                    $official->department_name = trim($departmentName);
                }

                $rawReportingManager = trim((string) ($official->reporting_manager ?? ''));
                if ($rawReportingManager !== '' && ctype_digit($rawReportingManager)) {
                    $managerHrid = (int) $rawReportingManager;
                }

                // Fallback: infer manager HRID from department mapping table.
                if ($managerHrid === null && ! empty($official->department_id) && Schema::hasTable('tbl_reporting_manager')) {
                    $mappedHrid = DB::table('tbl_reporting_manager')
                        ->whereRaw('CAST(department_id AS UNSIGNED) = ?', [(int) $official->department_id])
                        ->value('manager_name');
                    if ($mappedHrid !== null && ctype_digit((string) $mappedHrid)) {
                        $managerHrid = (int) $mappedHrid;
                    }
                }

                if ($managerHrid !== null) {
                    $managerRow = DB::table('tbl_emp_official_info')
                        ->select(['firstname', 'middlename', 'lastname', 'extension'])
                        ->where('hrid', $managerHrid)
                        ->first();

                    if ($managerRow) {
                        $official->reporting_manager = trim((string) implode(' ', array_filter([
                            trim((string) ($managerRow->firstname ?? '')),
                            trim((string) ($managerRow->middlename ?? '')),
                            trim((string) ($managerRow->lastname ?? '')),
                            trim((string) ($managerRow->extension ?? '')),
                        ], fn ($part) => $part !== '')));
                    }
                }
            }
        }

        $officialOptions = [
            'salaryGrades' => [],
            'steps' => [],
            'positions' => [],
            'departments' => [],
            'divisionOffices' => [],
            'roles' => [],
            'employmentStatuses' => [],
        ];

        $contactOptions = [
            'provinces' => [],
            'barangays' => [],
            'municipalities' => [],
        ];

        if (Schema::hasTable('tbl_salary_grade') && Schema::hasColumn('tbl_salary_grade', 'salary_grade')) {
            $officialOptions['salaryGrades'] = DB::table('tbl_salary_grade')
                ->whereNotNull('salary_grade')
                ->pluck('salary_grade')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_step') && Schema::hasColumn('tbl_step', 'step')) {
            $officialOptions['steps'] = DB::table('tbl_step')
                ->whereNotNull('step')
                ->pluck('step')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_job_title') && Schema::hasColumn('tbl_job_title', 'job_title')) {
            $officialOptions['positions'] = DB::table('tbl_job_title')
                ->whereNotNull('job_title')
                ->pluck('job_title')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_department') && Schema::hasColumn('tbl_department', 'department_name')) {
            $officialOptions['departments'] = DB::table('tbl_department')
                ->whereNotNull('department_name')
                ->pluck('department_name')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_business') && Schema::hasColumn('tbl_business', 'BusinessUnit')) {
            $officialOptions['divisionOffices'] = DB::table('tbl_business')
                ->whereNotNull('BusinessUnit')
                ->pluck('BusinessUnit')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_user') && Schema::hasColumn('tbl_user', 'role')) {
            $officialOptions['roles'] = DB::table('tbl_user')
                ->whereNotNull('role')
                ->distinct()
                ->pluck('role')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_employment_status') && Schema::hasColumn('tbl_employment_status', 'emp_status')) {
            $officialOptions['employmentStatuses'] = DB::table('tbl_employment_status')
                ->whereNotNull('emp_status')
                ->pluck('emp_status')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        } elseif (Schema::hasTable('tbl_emp_official_info') && Schema::hasColumn('tbl_emp_official_info', 'employ_status')) {
            $officialOptions['employmentStatuses'] = DB::table('tbl_emp_official_info')
                ->whereNotNull('employ_status')
                ->distinct()
                ->pluck('employ_status')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (Schema::hasTable('tbl_province') && Schema::hasColumn('tbl_province', 'province_name')) {
            $contactOptions['provinces'] = DB::table('tbl_province')
                ->whereNotNull('province_name')
                ->select(['province_name', 'province_code'])
                ->get()
                ->map(function ($row) {
                    $name = trim((string) $row->province_name);

                    return [
                        'name' => $name,
                        'province_code' => $row->province_code !== null ? (int) $row->province_code : null,
                    ];
                })
                ->filter(fn (array $row) => $row['name'] !== '' && $row['province_code'] !== null)
                ->unique(fn (array $row) => $row['province_code'])
                ->sortBy('name')
                ->values()
                ->all();
        }

        return Inertia::render('MyDetails', array_merge(
            [
                'profile' => $dbProfile,
                'educationUpdateUrl' => route('my-details.education.store'),
                'familyUpdateUrl' => route('my-details.family.store'),
                'officialUpdateUrl' => route('my-details.official.store'),
                'personalUpdateUrl' => route('my-details.personal.store'),
                'municipalitiesLookupUrl' => route('my-details.municipalities'),
                'barangaysLookupUrl' => route('my-details.barangays'),
                'eligibilityUpdateUrl' => route('my-details.eligibility.store'),
                'workExperienceUpdateUrl' => route('my-details.work-experience.store'),
                'voluntaryWorkUpdateUrl' => route('my-details.voluntary-work.store'),
                'trainingUpdateUrl' => route('my-details.training.store'),
                'awardsUpdateUrl' => route('my-details.awards.store'),
                'canEditOfficialInfo' => $this->canEditOfficialInfo($authUser, $dbProfile),
                'officialOptions' => $officialOptions,
                'contactOptions' => $contactOptions,
            ],
            $data,
        ));
    }

    public function municipalities(Request $request): JsonResponse
    {
        $provinceCode = $request->query('province_code');
        $normalizedProvinceCode = is_scalar($provinceCode) ? trim((string) $provinceCode) : '';

        if (
            $normalizedProvinceCode === ''
            || ! ctype_digit($normalizedProvinceCode)
            || ! Schema::hasTable('tbl_municipality')
            || ! Schema::hasColumn('tbl_municipality', 'municipal_name')
            || ! Schema::hasColumn('tbl_municipality', 'municipal_code')
            || ! Schema::hasColumn('tbl_municipality', 'province_code')
        ) {
            return response()->json([
                'municipalities' => [],
            ]);
        }

        $municipalities = DB::table('tbl_municipality')
            ->where('province_code', (int) $normalizedProvinceCode)
            ->whereNotNull('municipal_name')
            ->select(['municipal_id', 'municipal_code', 'municipal_name'])
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => $row->municipal_id !== null ? (int) $row->municipal_id : null,
                    'municipal_code' => $row->municipal_code !== null ? (int) $row->municipal_code : null,
                    'name' => trim((string) $row->municipal_name),
                ];
            })
            ->filter(fn (array $row): bool => $row['name'] !== '' && $row['municipal_code'] !== null)
            ->unique(fn (array $row): int => $row['municipal_code'])
            ->sortBy('name')
            ->values()
            ->all();

        return response()->json([
            'municipalities' => $municipalities,
        ]);
    }

    public function barangays(Request $request): JsonResponse
    {
        $municipalCode = $request->query('municipal_code');
        $normalizedMunicipalCode = is_scalar($municipalCode) ? trim((string) $municipalCode) : '';

        if (
            $normalizedMunicipalCode === ''
            || ! ctype_digit($normalizedMunicipalCode)
            || ! Schema::hasTable('tbl_barangay')
            || ! Schema::hasColumn('tbl_barangay', 'barangay_name')
            || ! Schema::hasColumn('tbl_barangay', 'municipal_code')
        ) {
            return response()->json([
                'barangays' => [],
            ]);
        }

        $barangays = DB::table('tbl_barangay')
            ->where('municipal_code', (int) $normalizedMunicipalCode)
            ->whereNotNull('barangay_name')
            ->select(['barangay_id', 'barangay_name'])
            ->get()
            ->map(function ($row): array {
                return [
                    'id' => $row->barangay_id !== null ? (int) $row->barangay_id : null,
                    'name' => trim((string) $row->barangay_name),
                ];
            })
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->unique(fn (array $row): string => strtolower($row['name']))
            ->sortBy('name')
            ->values()
            ->all();

        return response()->json([
            'barangays' => $barangays,
        ]);
    }

    public function exportPdsExcel(Request $request): StreamedResponse
    {
        $authUser = $request->user();
        $hasIncludeParams = $request->has('includePhoto') || $request->has('includeSignature');
        $includePhoto = $hasIncludeParams ? $request->query('includePhoto') === '1' : true;
        $includeSignature = $hasIncludeParams ? $request->query('includeSignature') === '1' : true;

        $dbProfile = DB::table('tbl_user')
            ->where('email', $authUser?->email)
            ->first();

        $hrid = $dbProfile->hrId
            ?? $authUser?->hrId
            ?? $authUser?->id
            ?? null;

        $handler = new PdsExportHandler;
        $templatePath = $handler->resolvePdsTemplatePath();
        abort_if(! $templatePath, 404, 'DepEd PDS template file not found.');

        $officialInfo = Schema::hasTable('tbl_emp_official_info')
            ? DB::table('tbl_emp_official_info')->where('hrid', $hrid)->first()
            : null;
        $personalInfo = Schema::hasTable('tbl_emp_personal_info')
            ? DB::table('tbl_emp_personal_info')->where('hrid', $hrid)->first()
            : null;
        $contactInfo = Schema::hasTable('tbl_emp_contact_info')
            ? DB::table('tbl_emp_contact_info as c')
                ->leftJoin('tbl_barangay as rb', 'rb.barangay_id', '=', 'c.barangay')
                ->leftJoin('tbl_province as rp', 'rp.province_id', '=', 'c.province')
                ->leftJoin('tbl_barangay as pb', 'pb.barangay_id', '=', 'c.barangay1')
                ->leftJoin('tbl_province as pp', 'pp.province_id', '=', 'c.province1')
                ->where('c.hrid', $hrid)
                ->select([
                    'c.*',
                    'rb.barangay_name as residential_barangay_name',
                    'rp.province_name  as residential_province_name',
                    'pb.barangay_name as permanent_barangay_name',
                    'pp.province_name  as permanent_province_name',
                ])
                ->first()
            : null;
        $family = Schema::hasTable('tbl_emp_family_info')
            ? DB::table('tbl_emp_family_info')->where('hrid', $hrid)->get()
            : collect();
        $education = $this->getOrderedRowsByHrid('tbl_emp_education_info', $hrid, ['id', 'education_id']);
        $workExperience = $this->getOrderedRowsByHrid('tbl_emp_work_experience_info', $hrid, ['id', 'work_experience_id']);
        $eligibility = $this->getOrderedRowsByHrid('tbl_emp_civil_service_info', $hrid, ['id', 'civil_service_id']);
        $voluntaryWork = Schema::hasTable('tbl_emp_voluntary_work')
            ? $this->getOrderedRowsByHrid('tbl_emp_voluntary_work', $hrid, ['id', 'voluntary_work_id'])
            : (Schema::hasTable('tbl_voluntary_work')
                ? $this->getOrderedRowsByHrid('tbl_voluntary_work', $hrid, ['id', 'voluntary_work_id'])
                : $this->getOrderedRowsByHrid('tbl_affiliation', $hrid, ['id', 'affiliation_id']));
        $training = $this->getOrderedRowsByHrid('tbl_emp_training', $hrid, ['id', 'training_id']);
        $awards = $this->getOrderedRowsByHrid('tbl_awards', $hrid, ['id', 'award_id']);
        $expertise = $this->getOrderedRowsByHrid('tbl_expertise', $hrid, ['id', 'expertise_id']);
        $affiliation = $this->getOrderedRowsByHrid('tbl_affiliation', $hrid, ['id', 'affiliation_id']);

        $spouse = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'spouse');
        $father = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'father');
        $mother = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'mother');
        $children = $family
            ->filter(fn ($row) => in_array(strtolower((string) ($row->relationship ?? '')), ['child', 'children'], true))
            ->values();

        $data = [
            'hrid' => $hrid,
            'dbProfile' => $dbProfile,
            'officialInfo' => $officialInfo,
            'personalInfo' => $personalInfo,
            'contactInfo' => $contactInfo,
            'family' => $family,
            'education' => $education,
            'workExperience' => $workExperience,
            'eligibility' => $eligibility,
            'voluntaryWork' => $voluntaryWork,
            'training' => $training,
            'awards' => $awards,
            'expertise' => $expertise,
            'affiliation' => $affiliation,
            'spouse' => $spouse,
            'father' => $father,
            'mother' => $mother,
            'children' => $children,
            'includePhoto' => $includePhoto,
            'includeSignature' => $includeSignature,
        ];

        $outputPath = $handler->export($templatePath, $data);
        $filename = 'PDS_'.$hrid.'_'.now()->format('Ymd_His').'.xlsx';

        // Only the temporary export file (and its temp dir) is removed after streaming.
        // The PDS template file (e.g. PDS.xlsx) is never modified or deleted.
        return response()->streamDownload(function () use ($outputPath): void {
            readfile($outputPath);
            @unlink($outputPath);
            $tempDir = dirname($outputPath);
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<int, string>  $orderColumns
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function getOrderedRowsByHrid(string $table, mixed $hrid, array $orderColumns): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable($table)) {
            return collect();
        }

        $query = DB::table($table)->where('hrid', $hrid);

        foreach ($orderColumns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $query->orderBy($column)->get();
            }
        }

        return $query->get();
    }

    /**
     * Update education background (III. EDUCATIONAL BACKGROUND) for the authenticated user.
     */
    public function updateEducation(Request $request)
    {
        $request->validate([
            'education' => ['required', 'array'],
            'education.*.education_level' => ['nullable', 'string', 'max:255'],
            'education.*.school_name' => ['nullable', 'string', 'max:255'],
            'education.*.course' => ['nullable', 'string', 'max:255'],
            'education.*.from_year' => ['nullable', 'string', 'max:50'],
            'education.*.to_year' => ['nullable', 'string', 'max:50'],
            'education.*.year_graduated' => ['nullable', 'string', 'max:50'],
            'education.*.highest_grade' => ['nullable', 'string', 'max:255'],
            'education.*.scholarship' => ['nullable', 'string', 'max:255'],
        ]);

        $authUser = $request->user();
        $hrid = $authUser?->hrId ?? null;
        if ($hrid === null && $authUser && Schema::hasTable('tbl_user')) {
            $row = DB::table('tbl_user')->where('email', $authUser->email)->first();
            $hrid = $row->hrId ?? null;
        }
        if ($hrid === null) {
            return back()->withErrors(['education' => 'Unable to determine employee (HR ID).']);
        }

        $payload = collect($request->input('education', []))
            ->filter(function ($row) {
                $school = trim((string) ($row['school_name'] ?? ''));
                $course = trim((string) ($row['course'] ?? ''));
                $year = trim((string) ($row['year_graduated'] ?? ''));

                return $school !== '' || $course !== '' || $year !== '';
            })
            ->map(function ($row) use ($hrid) {
                return [
                    'hrid' => $hrid,
                    'education_level' => $row['education_level'] ?? null,
                    'school_name' => $row['school_name'] ?? null,
                    'course' => $row['course'] ?? null,
                    'from_year' => $row['from_year'] ?? null,
                    'to_year' => $row['to_year'] ?? null,
                    'year_graduated' => $row['year_graduated'] ?? null,
                    'highest_grade' => $row['highest_grade'] ?? null,
                    'scholarship' => $row['scholarship'] ?? null,
                ];
            })
            ->values()
            ->all();

        EmpEducationInfo::query()->where('hrid', $hrid)->delete();
        foreach ($payload as $row) {
            EmpEducationInfo::query()->create($row);
        }

        return back();
    }

    public function updateOfficialInfo(Request $request)
    {
        $authUser = $request->user();
        $profile = $authUser && Schema::hasTable('tbl_user')
            ? User::query()->where('email', $authUser->email)->first()
            : null;
        $hrid = $profile?->hrId ?? $authUser?->hrId ?? $authUser?->id ?? null;

        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        if (! $this->canEditOfficialInfo($authUser, $profile)) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Only HR Manager can edit official information.']);
        }

        $data = $request->validate([
            'employee_id' => ['nullable', 'string', 'max:64'],
            'prefix_name' => ['nullable', 'string', 'max:32'],
            'firstname' => ['nullable', 'string', 'max:128'],
            'middlename' => ['nullable', 'string', 'max:128'],
            'lastname' => ['nullable', 'string', 'max:128'],
            'extension' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:255'],
            'item_no' => ['nullable', 'string', 'max:64'],
            'plantilla' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'employ_status' => ['nullable', 'string', 'max:64'],
            'salary_grade' => ['nullable', 'string', 'max:64'],
            'step' => ['nullable', 'string', 'max:64'],
            'date_of_joining' => ['nullable', 'string', 'max:64'],
            'date_of_promotion' => ['nullable', 'string', 'max:64'],
            'year_experience' => ['nullable', 'string', 'max:64'],
            'role' => ['nullable', 'string', 'max:64'],
            'division_code' => ['nullable', 'string', 'max:255'],
            'business_id' => ['nullable', 'string', 'max:255'],
            'office' => ['nullable', 'string', 'max:255'],
            'reporting_manager' => ['nullable', 'string', 'max:255'],
        ]);

        if (! Schema::hasTable('tbl_emp_official_info')) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Official info table not found.']);
        }

        $canEditRole = $this->canEditOfficialRole($authUser, $profile);
        $payload = [];
        foreach ($data as $key => $value) {
            if ($key === 'role' && ! $canEditRole) {
                continue;
            }
            if (Schema::hasColumn('tbl_emp_official_info', $key)) {
                $payload[$key] = $value === '' ? null : $value;
            }
        }

        if ($payload !== []) {
            $base = ['hrid' => $hrid];
            if ($profile?->email && Schema::hasColumn('tbl_emp_official_info', 'email')) {
                $base['email'] = $profile->email;
            }
            DB::table('tbl_emp_official_info')->updateOrInsert(
                ['hrid' => $hrid],
                array_merge($base, $payload),
            );
        }

        if (Schema::hasTable('tbl_user') && array_key_exists('email', $data)) {
            $officialEmail = $data['email'] === '' ? null : $data['email'];

            User::query()
                ->whereKey($profile?->getKey() ?? $authUser?->getAuthIdentifier())
                ->update(['email' => $officialEmail]);
        }

        return redirect()->route('my-details')->with('success', 'Official information updated.');
    }

    public function updatePersonalInfo(Request $request)
    {
        $authUser = $request->user();
        $profile = $authUser && Schema::hasTable('tbl_user')
            ? User::query()->where('email', $authUser->email)->first()
            : null;
        $hrid = $profile?->hrId ?? $authUser?->hrId ?? $authUser?->id ?? null;

        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $data = $request->validate([
            'dob' => ['nullable', 'string', 'max:32'],
            'pob' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:32'],
            'civil_stat' => ['nullable', 'string', 'max:64'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'blood_type' => ['nullable', 'string', 'max:16'],
            'citizenship' => ['nullable', 'string', 'max:64'],
            'dual_citizenship' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:64'],
            'umid' => ['nullable', 'string', 'max:64'],
            'pag_ibig' => ['nullable', 'string', 'max:64'],
            'philhealth' => ['nullable', 'string', 'max:64'],
            'philsys' => ['nullable', 'string', 'max:64'],
            'tin' => ['nullable', 'string', 'max:64'],
            'agency_emp_num' => ['nullable', 'string', 'max:64'],
            'prc_no' => ['nullable', 'string', 'max:64'],
            'sss' => ['nullable', 'string', 'max:64'],
            'gsis' => ['nullable', 'string', 'max:64'],
            'gsis_bp' => ['nullable', 'string', 'max:64'],
            'house_block_lotnum' => ['nullable', 'string', 'max:255'],
            'street_add' => ['nullable', 'string', 'max:255'],
            'subdivision_village' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city_municipality' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:32'],
            'house_block_lotnum1' => ['nullable', 'string', 'max:255'],
            'street_add1' => ['nullable', 'string', 'max:255'],
            'subdivision_village1' => ['nullable', 'string', 'max:255'],
            'barangay1' => ['nullable', 'string', 'max:255'],
            'city_municipality1' => ['nullable', 'string', 'max:255'],
            'province1' => ['nullable', 'string', 'max:255'],
            'zip_code1' => ['nullable', 'string', 'max:32'],
            'phone_num' => ['nullable', 'string', 'max:64'],
            'mobile_num' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        if (Schema::hasTable('tbl_emp_personal_info')) {
            $personalPayload = [];
            $assignFirst = function (string $dataKey, array $columns) use (&$personalPayload, $data): void {
                if (! array_key_exists($dataKey, $data)) {
                    return;
                }
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tbl_emp_personal_info', $column)) {
                        $personalPayload[$column] = $data[$dataKey] === '' ? null : $data[$dataKey];

                        return;
                    }
                }
            };

            foreach ([
                'dob',
                'pob',
                'gender',
                'civil_stat',
                'height',
                'weight',
                'blood_type',
                'citizenship',
                'dual_citizenship',
                'country',
                'pag_ibig',
                'philhealth',
                'prc_no',
                'sss',
                'gsis',
                'gsis_bp',
            ] as $key) {
                if (array_key_exists($key, $data) && Schema::hasColumn('tbl_emp_personal_info', $key)) {
                    $personalPayload[$key] = $data[$key] === '' ? null : $data[$key];
                }
            }

            $assignFirst('umid', ['umid', 'umid_no', 'umid_num']);
            $assignFirst('philsys', ['philsys', 'philsys_no', 'philsys_num', 'psn']);
            $assignFirst('tin', ['tin', 'tin_no']);
            $assignFirst('agency_emp_num', ['agency_emp_num', 'agency_employee_no', 'agency_emp_no']);
            if ($personalPayload !== []) {
                DB::table('tbl_emp_personal_info')->updateOrInsert(
                    ['hrid' => $hrid],
                    array_merge(['hrid' => $hrid], $personalPayload),
                );
            }
        }

        if (Schema::hasTable('tbl_emp_contact_info')) {
            $contactPayload = [];
            $invalidLookupFields = [];
            foreach ([
                'house_block_lotnum',
                'street_add',
                'subdivision_village',
                'barangay',
                'city_municipality',
                'province',
                'zip_code',
                'house_block_lotnum1',
                'street_add1',
                'subdivision_village1',
                'barangay1',
                'city_municipality1',
                'province1',
                'zip_code1',
                'phone_num',
                'mobile_num',
                'email',
            ] as $key) {
                if (array_key_exists($key, $data) && Schema::hasColumn('tbl_emp_contact_info', $key)) {
                    $value = $data[$key] === '' ? null : $data[$key];
                    if (in_array($key, ['barangay', 'barangay1', 'city_municipality', 'city_municipality1', 'province', 'province1'], true)) {
                        $lookupResult = $this->normalizeLocationLookupValueForContactInfo($key, $value);
                        if (! $lookupResult['valid']) {
                            $invalidLookupFields[] = $key;
                        }
                        $value = $lookupResult['value'];
                    }

                    $contactPayload[$key] = $value;
                }
            }

            if ($invalidLookupFields !== []) {
                $fieldNames = collect($invalidLookupFields)
                    ->map(function (string $field): string {
                        return match ($field) {
                            'barangay', 'barangay1' => 'barangay',
                            'city_municipality', 'city_municipality1' => 'city/municipality',
                            'province', 'province1' => 'province',
                            default => $field,
                        };
                    })
                    ->unique()
                    ->values()
                    ->all();

                return redirect()->route('my-details')
                    ->withErrors([
                        'message' => 'Invalid '.implode(' and ', $fieldNames).' value. Please choose a valid one.',
                    ]);
            }

            if ($contactPayload !== []) {
                $base = ['hrid' => $hrid];
                if ($profile?->email && Schema::hasColumn('tbl_emp_contact_info', 'email')) {
                    $base['email'] = $profile->email;
                }
                DB::table('tbl_emp_contact_info')->updateOrInsert(
                    ['hrid' => $hrid],
                    array_merge($base, $contactPayload),
                );
            }
        }

        if (Schema::hasTable('tbl_user') && array_key_exists('email', $data)) {
            $personalEmail = $data['email'] === '' ? null : $data['email'];

            User::query()
                ->whereKey($profile?->getKey() ?? $authUser?->getAuthIdentifier())
                ->update(['personal_email' => $personalEmail]);
        }

        return redirect()->route('my-details')->with('success', 'Personal information updated.');
    }

    public function updateFamilyBackground(Request $request)
    {
        $authUser = $request->user();
        $profile = $authUser && Schema::hasTable('tbl_user')
            ? User::query()->where('email', $authUser->email)->first()
            : null;
        $hrid = $profile?->hrId ?? $authUser?->hrId ?? $authUser?->id ?? null;

        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        if (! Schema::hasTable('tbl_emp_family_info')) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Family info table not found.']);
        }

        $data = $request->validate([
            'family' => ['required', 'array'],
            'family.*.relationship' => ['nullable', 'string', 'max:64'],
            'family.*.lastname' => ['nullable', 'string', 'max:255'],
            'family.*.firstname' => ['nullable', 'string', 'max:255'],
            'family.*.middlename' => ['nullable', 'string', 'max:255'],
            'family.*.extension' => ['nullable', 'string', 'max:64'],
            'family.*.occupation' => ['nullable', 'string', 'max:255'],
            'family.*.employer_name' => ['nullable', 'string', 'max:255'],
            'family.*.business_add' => ['nullable', 'string', 'max:255'],
            'family.*.tel_num' => ['nullable', 'string', 'max:64'],
            'family.*.dob' => ['nullable', 'string', 'max:64'],
            'family.*.deceased' => ['nullable', 'string', 'max:64'],
        ]);

        $rows = collect($data['family'] ?? [])
            ->map(function (array $row) use ($hrid) {
                $payload = ['hrid' => $hrid];
                foreach ([
                    'relationship',
                    'lastname',
                    'firstname',
                    'middlename',
                    'extension',
                    'occupation',
                    'employer_name',
                    'business_add',
                    'tel_num',
                    'dob',
                    'deceased',
                ] as $key) {
                    if (Schema::hasColumn('tbl_emp_family_info', $key)) {
                        $val = $row[$key] ?? null;
                        $payload[$key] = $val === '' ? null : $val;
                    }
                }

                return $payload;
            })
            ->filter(function (array $row) {
                // Keep the row if it has any meaningful values besides hrid.
                foreach ($row as $key => $val) {
                    if ($key === 'hrid') {
                        continue;
                    }
                    if ($val !== null && trim((string) $val) !== '') {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();

        DB::table('tbl_emp_family_info')->where('hrid', $hrid)->delete();
        foreach ($rows as $row) {
            DB::table('tbl_emp_family_info')->insert($row);
        }

        return redirect()->route('my-details')->with('success', 'Family background updated.');
    }

    public function updateEligibility(Request $request)
    {
        $hrid = $this->resolveMyDetailsHrid($request);
        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $table = 'tbl_emp_civil_service_info';
        if (! Schema::hasTable($table)) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Eligibility table not found.']);
        }

        $data = $request->validate([
            'eligibility' => ['required', 'array'],
            'eligibility.*.title' => ['nullable', 'string', 'max:255'],
            'eligibility.*.rating' => ['nullable', 'string', 'max:64'],
            'eligibility.*.date_exam' => ['nullable', 'string', 'max:64'],
            'eligibility.*.place_exam' => ['nullable', 'string', 'max:255'],
            'eligibility.*.license_no' => ['nullable', 'string', 'max:128'],
        ]);

        $rows = collect($data['eligibility'] ?? [])
            ->map(function (array $row) use ($table, $hrid): array {
                return $this->mapMyDetailsRowToTable($table, $hrid, $row, [
                    'title' => ['title'],
                    'rating' => ['rating'],
                    'date_exam' => ['date_exam'],
                    'place_exam' => ['place_exam'],
                    'license_no' => ['license_no'],
                ]);
            })
            ->filter(fn (array $row): bool => $this->myDetailsRowHasValues($row))
            ->values()
            ->all();

        $this->replaceMyDetailsRowsForHrid($table, $hrid, $rows);

        return redirect()->route('my-details')->with('success', 'Eligibility updated.');
    }

    public function updateWorkExperience(Request $request)
    {
        $hrid = $this->resolveMyDetailsHrid($request);
        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $table = 'tbl_emp_work_experience_info';
        if (! Schema::hasTable($table)) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Work experience table not found.']);
        }

        $data = $request->validate([
            'workExperience' => ['required', 'array'],
            'workExperience.*.company_name' => ['nullable', 'string', 'max:255'],
            'workExperience.*.position_title' => ['nullable', 'string', 'max:255'],
            'workExperience.*.inclusive_date_from' => ['nullable', 'string', 'max:64'],
            'workExperience.*.inclusive_date_to' => ['nullable', 'string', 'max:64'],
            'workExperience.*.employment_status' => ['nullable', 'string', 'max:128'],
        ]);

        $rows = collect($data['workExperience'] ?? [])
            ->map(function (array $row) use ($table, $hrid): array {
                return $this->mapMyDetailsRowToTable($table, $hrid, $row, [
                    'company_name' => ['company_name', 'agency_office_company'],
                    'position_title' => ['position_title', 'position'],
                    'inclusive_date_from' => ['inclusive_date_from', 'date_from', 'start_date'],
                    'inclusive_date_to' => ['inclusive_date_to', 'date_to', 'end_date'],
                    'employment_status' => ['employment_status', 'status'],
                ]);
            })
            ->filter(fn (array $row): bool => $this->myDetailsRowHasValues($row))
            ->values()
            ->all();

        $this->replaceMyDetailsRowsForHrid($table, $hrid, $rows);

        return redirect()->route('my-details')->with('success', 'Work experience updated.');
    }

    public function updateVoluntaryWork(Request $request)
    {
        $hrid = $this->resolveMyDetailsHrid($request);
        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $table = $this->resolveFirstExistingMyDetailsTable([
            'tbl_emp_voluntary_work',
            'tbl_voluntary_work',
        ]);
        if ($table === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Voluntary work table not found.']);
        }

        $data = $request->validate([
            'voluntaryWork' => ['required', 'array'],
            'voluntaryWork.*.name_address_org' => ['nullable', 'string', 'max:255'],
            'voluntaryWork.*.inclusive_date_from' => ['nullable', 'string', 'max:64'],
            'voluntaryWork.*.inclusive_date_to' => ['nullable', 'string', 'max:64'],
            'voluntaryWork.*.number_hours' => ['nullable', 'string', 'max:64'],
            'voluntaryWork.*.position_nature_of_work' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = collect($data['voluntaryWork'] ?? [])
            ->map(function (array $row) use ($table, $hrid): array {
                return $this->mapMyDetailsRowToTable($table, $hrid, $row, [
                    'name_address_org' => ['name_address_org', 'organization', 'org_name', 'name_of_organization', 'affiliation'],
                    'inclusive_date_from' => ['inclusive_date_from', 'date_from', 'start_date'],
                    'inclusive_date_to' => ['inclusive_date_to', 'date_to', 'end_date'],
                    'number_hours' => ['number_hours', 'hours', 'no_of_hours'],
                    'position_nature_of_work' => ['position_nature_of_work', 'position', 'nature_of_work', 'position_title'],
                ]);
            })
            ->filter(fn (array $row): bool => $this->myDetailsRowHasValues($row))
            ->values()
            ->all();

        $this->replaceMyDetailsRowsForHrid($table, $hrid, $rows);

        return redirect()->route('my-details')->with('success', 'Voluntary work updated.');
    }

    public function updateTraining(Request $request)
    {
        $hrid = $this->resolveMyDetailsHrid($request);
        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $table = 'tbl_emp_training';
        if (! Schema::hasTable($table)) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Training table not found.']);
        }

        $data = $request->validate([
            'training' => ['required', 'array'],
            'training.*.training_title' => ['nullable', 'string', 'max:255'],
            'training.*.training_venue' => ['nullable', 'string', 'max:255'],
            'training.*.start_date' => ['nullable', 'string', 'max:64'],
            'training.*.end_date' => ['nullable', 'string', 'max:64'],
            'training.*.number_hours' => ['nullable', 'string', 'max:64'],
        ]);

        $rows = collect($data['training'] ?? [])
            ->map(function (array $row) use ($table, $hrid): array {
                return $this->mapMyDetailsRowToTable($table, $hrid, $row, [
                    'training_title' => ['training_title', 'title', 'title_of_training'],
                    'training_venue' => ['training_venue', 'venue', 'conducted_sponsored_by'],
                    'start_date' => ['start_date', 'inclusive_date_from', 'date_from'],
                    'end_date' => ['end_date', 'inclusive_date_to', 'date_to'],
                    'number_hours' => ['number_hours', 'hours', 'no_of_hours'],
                ]);
            })
            ->filter(fn (array $row): bool => $this->myDetailsRowHasValues($row))
            ->values()
            ->all();

        $this->replaceMyDetailsRowsForHrid($table, $hrid, $rows);

        return redirect()->route('my-details')->with('success', 'Training updated.');
    }

    public function updateAwards(Request $request)
    {
        $hrid = $this->resolveMyDetailsHrid($request);
        if ($hrid === null) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Unable to identify employee.']);
        }

        $table = 'tbl_awards';
        if (! Schema::hasTable($table)) {
            return redirect()->route('my-details')
                ->withErrors(['message' => 'Awards table not found.']);
        }

        $data = $request->validate([
            'awards' => ['required', 'array'],
            'awards.*.award_title' => ['nullable', 'string', 'max:255'],
            'awards.*.category' => ['nullable', 'string', 'max:255'],
            'awards.*.school_year' => ['nullable', 'string', 'max:64'],
            'awards.*.award' => ['nullable', 'string', 'max:255'],
        ]);

        $rows = collect($data['awards'] ?? [])
            ->map(function (array $row) use ($table, $hrid): array {
                return $this->mapMyDetailsRowToTable($table, $hrid, $row, [
                    'award_title' => ['award_title', 'title', 'distinction'],
                    'category' => ['category'],
                    'school_year' => ['school_year', 'year'],
                    'award' => ['award'],
                ]);
            })
            ->filter(fn (array $row): bool => $this->myDetailsRowHasValues($row))
            ->values()
            ->all();

        $this->replaceMyDetailsRowsForHrid($table, $hrid, $rows);

        return redirect()->route('my-details')->with('success', 'Awards updated.');
    }

    private function resolveMyDetailsHrid(Request $request): mixed
    {
        $authUser = $request->user();
        if (! $authUser) {
            return null;
        }

        $profile = Schema::hasTable('tbl_user')
            ? User::query()->select('hrId')->where('email', $authUser->email)->first()
            : null;

        return $profile?->hrId ?? $authUser?->hrId ?? $authUser?->id ?? null;
    }

    private function resolveFirstExistingMyDetailsTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function replaceMyDetailsRowsForHrid(string $table, mixed $hrid, array $rows): void
    {
        DB::table($table)->where('hrid', $hrid)->delete();

        foreach ($rows as $row) {
            DB::table($table)->insert($row);
        }
    }

    private function mapMyDetailsRowToTable(string $table, mixed $hrid, array $row, array $fieldMap): array
    {
        $payload = ['hrid' => $hrid];

        foreach ($fieldMap as $inputKey => $candidateColumns) {
            $value = $row[$inputKey] ?? null;
            $resolvedValue = is_string($value) ? trim($value) : $value;
            if ($resolvedValue === '') {
                $resolvedValue = null;
            }

            foreach ($candidateColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $payload[$column] = $resolvedValue;

                    break;
                }
            }
        }

        return $payload;
    }

    private function myDetailsRowHasValues(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === 'hrid') {
                continue;
            }

            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function canEditOfficialRole(?User $authUser, ?User $profile): bool
    {
        return $this->canEditOfficialInfo($authUser, $profile);
    }

    /**
     * @return array{value: mixed, valid: bool}
     */
    private function normalizeLocationLookupValueForContactInfo(string $field, mixed $value): array
    {
        if ($value === null) {
            return ['value' => null, 'valid' => true];
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return ['value' => null, 'valid' => true];
        }

        $lookupConfig = match ($field) {
            'barangay', 'barangay1' => [
                [
                    'table' => 'tbl_barangay',
                    'id_column' => 'barangay_code',
                    'name_column' => 'barangay_name',
                ],
                [
                    'table' => 'tbl_barangay',
                    'id_column' => 'barangay_id',
                    'name_column' => 'barangay_name',
                ],
            ],
            'city_municipality', 'city_municipality1' => [
                [
                    'table' => 'tbl_municipality',
                    'id_column' => 'municipal_code',
                    'name_column' => 'municipal_name',
                ],
                [
                    'table' => 'tbl_municipality',
                    'id_column' => 'municipal_id',
                    'name_column' => 'municipal_name',
                ],
                [
                    'table' => 'tbl_municipality1',
                    'id_column' => 'municipal_code1',
                    'name_column' => 'municipal_name1',
                ],
                [
                    'table' => 'tbl_municipality1',
                    'id_column' => 'municipal_id',
                    'name_column' => 'municipal_name1',
                ],
            ],
            'province', 'province1' => [
                [
                    'table' => 'tbl_province',
                    'id_column' => 'province_code',
                    'name_column' => 'province_name',
                ],
                [
                    'table' => 'tbl_province',
                    'id_column' => 'province_id',
                    'name_column' => 'province_name',
                ],
            ],
            default => null,
        };

        if ($lookupConfig === null) {
            return ['value' => $normalized, 'valid' => true];
        }

        $columnType = Schema::getColumnType('tbl_emp_contact_info', $field);
        $expectsInteger = in_array($columnType, ['tinyint', 'smallint', 'mediumint', 'integer', 'bigint'], true);

        if (ctype_digit($normalized)) {
            return ['value' => (int) $normalized, 'valid' => true];
        }

        foreach ($lookupConfig as $candidate) {
            if (
                Schema::hasTable($candidate['table'])
                && Schema::hasColumn($candidate['table'], $candidate['id_column'])
                && Schema::hasColumn($candidate['table'], $candidate['name_column'])
            ) {
                $normalizedLower = strtolower($normalized);
                $alternates = [$normalizedLower];
                if (str_ends_with($normalizedLower, ' city')) {
                    $alternates[] = 'city of '.trim(substr($normalizedLower, 0, -5));
                } elseif (str_starts_with($normalizedLower, 'city of ')) {
                    $alternates[] = trim(substr($normalizedLower, 8)).' city';
                }

                $resolvedId = DB::table($candidate['table'])
                    ->where(function ($query) use ($candidate, $alternates): void {
                        foreach ($alternates as $index => $alternate) {
                            if ($index === 0) {
                                $query->whereRaw('LOWER('.$candidate['name_column'].') = ?', [$alternate]);
                            } else {
                                $query->orWhereRaw('LOWER('.$candidate['name_column'].') = ?', [$alternate]);
                            }
                        }
                    })
                    ->value($candidate['id_column']);

                if ($resolvedId !== null) {
                    return ['value' => (int) $resolvedId, 'valid' => true];
                }
            }
        }

        if ($expectsInteger) {
            return ['value' => null, 'valid' => false];
        }

        return ['value' => $normalized, 'valid' => true];
    }

    private function resolveLookupNameByValue(string $table, mixed $value, array $nameColumns, array $lookupColumns): ?string
    {
        $normalizedValue = is_scalar($value) ? trim((string) $value) : '';
        if ($normalizedValue === '' || ! Schema::hasTable($table)) {
            return null;
        }

        $nameColumn = collect($nameColumns)
            ->first(fn (string $column): bool => Schema::hasColumn($table, $column));

        if (! is_string($nameColumn)) {
            return null;
        }

        $resolvedValue = ctype_digit($normalizedValue) ? (int) $normalizedValue : $normalizedValue;

        foreach ($lookupColumns as $lookupColumn) {
            if (! Schema::hasColumn($table, $lookupColumn)) {
                continue;
            }

            $name = DB::table($table)
                ->where($lookupColumn, $resolvedValue)
                ->value($nameColumn);

            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return null;
    }

    private function canEditOfficialInfo(?User $authUser, ?User $profile): bool
    {
        $role = (string) ($authUser?->role ?? $profile?->role ?? '');
        if ($role === '') {
            return false;
        }

        $normalized = strtolower(trim($role));

        return str_contains($normalized, 'hr manager')
            || str_contains($normalized, 'human resources manager');
    }
}
