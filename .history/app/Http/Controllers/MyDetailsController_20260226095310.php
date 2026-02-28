<?php

namespace App\Http\Controllers;

use App\Models\Affiliation;
use App\Models\Awards;
use App\Models\Document;
use App\Models\EmpContactInfo;
use App\Models\EmpEducationInfo;
use App\Models\EmpOfficialInfo;
use App\Models\EmpPersonalInfo;
use App\Models\EmpServiceRecord;
use App\Models\EmpTraining;
use App\Models\EmpWorkExperienceInfo;
use App\Models\Expertise;
use App\Models\LeaveHistory;
use App\Models\Performance;
use App\Models\Researches;
use App\Models\User;
use App\Models\EmpCivilServiceInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;

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
            $hrid = $dbProfile?->hrId ?? $authUser->hrId ?? null;
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
                'tbl_emp_contact_info' => [
                    'key' => 'contactInfo',
                    'type' => 'single',
                    'query' => fn () => EmpContactInfo::query()->where('hrid', $hrid)->first(),
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
        }

        return Inertia::render('MyDetails', array_merge(
            [
                'profile' => $dbProfile,
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

        $hrid = $dbProfile->hrId ?? $authUser?->hrId;

        abort_if(! $hrid, 404, 'Employee profile not found.');

        $templatePath = $this->resolvePdsTemplatePath();
        abort_if(! $templatePath, 404, 'DepEd PDS template file not found.');

        $officialInfo = Schema::hasTable('tbl_emp_official_info')
            ? DB::table('tbl_emp_official_info')->where('hrid', $hrid)->first()
            : null;
        $personalInfo = Schema::hasTable('tbl_emp_personal_info')
            ? DB::table('tbl_emp_personal_info')->where('hrid', $hrid)->first()
            : null;
        $contactInfo = Schema::hasTable('tbl_emp_contact_info')
            ? DB::table('tbl_emp_contact_info')->where('hrid', $hrid)->first()
            : null;
        $family = Schema::hasTable('tbl_emp_family_info')
            ? DB::table('tbl_emp_family_info')->where('hrid', $hrid)->get()
            : collect();

        $spouse = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'spouse');
        $father = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'father');
        $mother = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'mother');
        $children = $family
            ->filter(fn ($row) => in_array(strtolower((string) ($row->relationship ?? '')), ['child', 'children'], true))
            ->values();

        \PHPExcel_Settings::setZipClass(\PHPExcel_Settings::PCLZIP);
        $reader = new \PHPExcel_Reader_Excel2007();
        $excel = $reader->load($templatePath);
        $sheet = $excel->setActiveSheetIndex(0);

        // Core identity / personal data mapping for DepEd Annex H (Revised 2025) sheet "C1".
        $cellMap = [
            'D10' => $officialInfo->lastname ?? $dbProfile->lastname ?? null,
            'D11' => $officialInfo->firstname ?? $dbProfile->firstname ?? null,
            'D12' => $officialInfo->middlename ?? $dbProfile->middlename ?? null,
            'M11' => $officialInfo->extension ?? $dbProfile->extname ?? null,
            'D13' => $this->formatDate($personalInfo->dob ?? null),
            'D15' => $personalInfo->pob ?? null,
            'D16' => $personalInfo->gender ?? null,
            'D17' => $personalInfo->civil_stat ?? null,
            'I13' => $personalInfo->citizenship ?? null,
            'I16' => $personalInfo->dual_citizenship ?? null,

            'D22' => $personalInfo->height ?? null,
            'D24' => $personalInfo->weight ?? null,
            'D25' => $personalInfo->blood_type ?? null,
            'D27' => $personalInfo->gsis ?? null,
            'D29' => $personalInfo->pag_ibig ?? $personalInfo->pagibig ?? null,
            'D31' => $personalInfo->philhealth ?? null,

            'D32' => $contactInfo->phone_num ?? null,
            'D33' => $contactInfo->mobile_num ?? null,
            'I34' => $contactInfo->email ?? ($officialInfo->email ?? $dbProfile->email ?? null),

            'D36' => $spouse->lastname ?? null,
            'D37' => $spouse->firstname ?? null,
            'H37' => $spouse->extension ?? null,
            'D38' => $spouse->middlename ?? null,
            'D39' => $spouse->occupation ?? null,
            'D40' => $spouse->employer_name ?? null,
            'D41' => $spouse->business_add ?? null,
            'D42' => $spouse->tel_num ?? null,

            'D43' => $father->lastname ?? null,
            'D44' => $father->firstname ?? null,
            'H44' => $father->extension ?? null,
            'D45' => $father->middlename ?? null,

            'D47' => $mother->lastname ?? null,
            'D48' => $mother->firstname ?? null,
            'D49' => $mother->middlename ?? null,
        ];

        foreach ($cellMap as $cell => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $sheet->setCellValueExplicit($cell, (string) $value, \PHPExcel_Cell_DataType::TYPE_STRING);
        }

        // Children list (Annex H C1: names in column I, DOB in column M).
        foreach ($children->take(12)->values() as $index => $child) {
            $row = 37 + $index;
            $childName = trim(implode(' ', array_filter([
                $child->firstname ?? null,
                $child->middlename ?? null,
                $child->lastname ?? null,
                $child->extension ?? null,
            ])));

            if ($childName !== '') {
                $sheet->setCellValueExplicit("I{$row}", $childName, \PHPExcel_Cell_DataType::TYPE_STRING);
            }

            $dob = $this->formatDate($child->dob ?? null);
            if ($dob !== null) {
                $sheet->setCellValueExplicit("M{$row}", $dob, \PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }

        $filename = 'PDS_'.$hrid.'_'.now()->format('Ymd_His').'.xlsx';
        $writer = new \PHPExcel_Writer_Excel2007($excel);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolvePdsTemplatePath(): ?string
    {
        $envPath = (string) env('PDS_TEMPLATE_PATH', '');
        if ($envPath !== '' && is_file($envPath)) {
            return $envPath;
        }

        $userProfile = (string) env('USERPROFILE', '');
        $downloadsCandidates = [];
        if ($userProfile !== '') {
            $downloadsCandidates = [
                $userProfile.'\\Downloads\\ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet (1).xlsx',
                $userProfile.'\\Downloads\\ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet.xlsx',
                $userProfile.'\\Downloads\\deped-pds-template.xlsx',
            ];
        }

        $candidates = [
            storage_path('app/templates/ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet (1).xlsx'),
            storage_path('app/templates/ANNEX-H-1-CS-Form-No.-212-Revised-2025-Personal-Data-Sheet.xlsx'),
            storage_path('app/templates/deped-pds-template.xlsx'),
            storage_path('app/templates/DEPED-PDS.xlsx'),
            public_path('templates/deped-pds-template.xlsx'),
            public_path('deped-pds-template.xlsx'),
            ...$downloadsCandidates,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
