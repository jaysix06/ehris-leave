<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MyDetails\PdsExportHandler;
use App\Models\Affiliation;
use App\Models\Awards;
use App\Models\Document;
use App\Models\EmpCivilServiceInfo;
use App\Models\EmpContactInfo;
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
                    'query' => fn () => DB::table('tbl_emp_contact_info as c')
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
                        ->first(),
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

            // Enrich contact info with resolved barangay/province names so the
            // UI (and exports) can show names instead of raw IDs.
            if (isset($data['contactInfo']) && $data['contactInfo'] instanceof EmpContactInfo) {
                $contact = $data['contactInfo'];

                $residentialBarangayName = null;
                $residentialProvinceName = null;
                $permanentBarangayName = null;
                $permanentProvinceName = null;

                if (! empty($contact->barangay) && Schema::hasTable('tbl_barangay')) {
                    $residentialBarangayName = DB::table('tbl_barangay')
                        ->where('barangay_id', $contact->barangay)
                        ->value('barangay_name');
                }

                if (! empty($contact->province) && Schema::hasTable('tbl_province')) {
                    $residentialProvinceName = DB::table('tbl_province')
                        ->where('province_id', $contact->province)
                        ->value('province_name');
                }

                if (! empty($contact->barangay1) && Schema::hasTable('tbl_barangay')) {
                    $permanentBarangayName = DB::table('tbl_barangay')
                        ->where('barangay_id', $contact->barangay1)
                        ->value('barangay_name');
                }

                if (! empty($contact->province1) && Schema::hasTable('tbl_province')) {
                    $permanentProvinceName = DB::table('tbl_province')
                        ->where('province_id', $contact->province1)
                        ->value('province_name');
                }

                $contact->residential_barangay_name = $residentialBarangayName;
                $contact->residential_province_name = $residentialProvinceName;
                $contact->permanent_barangay_name = $permanentBarangayName;
                $contact->permanent_province_name = $permanentProvinceName;
            }

            if (isset($data['officialInfo']) && $data['officialInfo'] instanceof EmpOfficialInfo) {
                $official = $data['officialInfo'];
                $managerHrid = null;

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

        return Inertia::render('MyDetails', array_merge(
            [
                'profile' => $dbProfile,
                'educationUpdateUrl' => route('my-details.education.store'),
            ],
            $data,
        ));
    }

    public function exportPdsExcel(Request $request): StreamedResponse
    {
        $authUser = $request->user();

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
        $education = Schema::hasTable('tbl_emp_education_info')
            ? DB::table('tbl_emp_education_info')->where('hrid', $hrid)->orderBy('id')->get()
            : collect();
        $workExperience = Schema::hasTable('tbl_emp_work_experience_info')
            ? DB::table('tbl_emp_work_experience_info')->where('hrid', $hrid)->orderBy('id')->get()
            : collect();
        $eligibility = Schema::hasTable('tbl_emp_civil_service_info')
            ? DB::table('tbl_emp_civil_service_info')->where('hrid', $hrid)->orderBy('id')->get()
            : collect();

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
            'spouse' => $spouse,
            'father' => $father,
            'mother' => $mother,
            'children' => $children,
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
}
