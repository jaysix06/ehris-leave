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

        // Prefer the HRID from tbl_user, then from the auth user, finally fall back to auth user ID.
        // If none exist, we still proceed (all fields will become "N/A"), we just use a generic label.
        $hrid = $dbProfile->hrId
            ?? $authUser?->hrId
            ?? $authUser?->id
            ?? null;

        $templatePath = $this->resolvePdsTemplatePath();
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

        $spouse = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'spouse');
        $father = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'father');
        $mother = $family->first(fn ($row) => strtolower((string) ($row->relationship ?? '')) === 'mother');
        $children = $family
            ->filter(fn ($row) => in_array(strtolower((string) ($row->relationship ?? '')), ['child', 'children'], true))
            ->values();

        // Core identity / personal data mapping for DepEd Annex H (Revised 2025) sheet "C1".
        $cellMap = [
            'D10' => $officialInfo->lastname ?? $dbProfile->lastname ?? null,
            'D11' => $officialInfo->firstname ?? $dbProfile->firstname ?? null,
            'D12' => $officialInfo->middlename ?? $dbProfile->middlename ?? null,
            // Name extension (JR, SR, III, etc.). Different tables sometimes
            // use either "extension" or "extname", so we check both.
            'L11' => $officialInfo->extension
                ?? ($officialInfo->extname ?? null)
                ?? ($dbProfile->extname ?? null),
            'D13' => $this->formatDate($personalInfo->dob ?? null),
            'D15' => $personalInfo->pob ?? null,
            'I13' => $personalInfo->citizenship ?? null,
            'I16' => $personalInfo->dual_citizenship ?? null,
            // Country text shown beside the dropdown under "Pls. indicate country:".
            // If no explicit country is stored, we try to derive it from the
            // citizenship string (e.g. "Filipino – Philippines").
            'M15' => $personalInfo->country
                ?? $this->deriveCountryFromCitizenship($personalInfo->citizenship ?? null),

            // 17. RESIDENTIAL ADDRESS
            'I17' => $contactInfo->house_block_lotnum ?? null,                       // House/Block/Lot No.
            'L17' => $contactInfo->street_add ?? null,                               // Street
            'I19' => $contactInfo->subdivision_village ?? null,                      // Subdivision/Village
            'L19' => $contactInfo->residential_barangay_name ?? null,                // Barangay (name)
            'I22' => $contactInfo->city_municipality ?? null,                        // City/Municipality
            'L22' => $contactInfo->residential_province_name ?? null,                // Province (name)
            'I24' => $contactInfo->zip_code ?? null,                                 // ZIP Code

            // 18. PERMANENT ADDRESS
            'D22' => $contactInfo->house_block_lotnum1 ?? null,                      // House/Block/Lot No.
            'I22' => $contactInfo->street_add1 ?? null,                              // Street
            'D23' => $contactInfo->subdivision_village1 ?? null,                     // Subdivision/Village
            'I23' => $contactInfo->permanent_barangay_name ?? null,                  // Barangay (name)
            'D24' => $contactInfo->city_municipality1 ?? null,                       // City/Municipality
            'I24' => $contactInfo->permanent_province_name ?? null,                  // Province (name)
            'D25' => $contactInfo->zip_code1 ?? null,                                // ZIP Code

            // Height and weight – ensure we include the proper units.
            'D22' => $this->formatHeight($personalInfo->height ?? null),
            'D24' => $this->formatWeight($personalInfo->weight ?? null),
            'D25' => $personalInfo->blood_type ?? null,
            'D29' => $personalInfo->pag_ibig ?? $personalInfo->pagibig ?? null,
            'D31' => $personalInfo->philhealth ?? null,

            'I32' => $contactInfo->phone_num ?? null,
            'I33' => $contactInfo->mobile_num ?? null,
            'I34' => $contactInfo->email ?? ($officialInfo->email ?? $dbProfile->email ?? null),

            'D36' => $spouse?->lastname ?? null,
            'D37' => $spouse?->firstname ?? null,
            // Spouse name extension (Jr., Sr.) in Family Background.
            'G37' => $spouse?->extension
                ?? ($spouse?->extname ?? null),
            'D38' => $spouse?->middlename ?? null,
            'D39' => $spouse?->occupation ?? null,
            'D40' => $spouse?->employer_name ?? null,
            'D41' => $spouse?->business_add ?? null,
            'D42' => $spouse?->tel_num ?? null,

            'D43' => $father?->lastname ?? null,
            'D44' => $father?->firstname ?? null,
            // Father's name extension (Jr., Sr.) in Family Background.
            'G44' => $father?->extension
                ?? ($father?->extname ?? null),
            'D45' => $father?->middlename ?? null,

            'D47' => $mother?->lastname ?? null,
            'D48' => $mother?->firstname ?? null,
            'D49' => $mother?->middlename ?? null,
        ];

        // If employee is not marked as married, DepEd guidance says to put
        // "N/A" in the first spouse cell (22. SPOUSE'S SURNAME).
        $civilStatus = (string) ($personalInfo->civil_stat ?? '');
        $civilKey = $this->normalizeCivilStatusToCheckbox($civilStatus) ?? '';
        if ($civilKey !== 'married') {
            $cellMap['D36'] = 'N/A';
        }

        foreach ($cellMap as $cell => $value) {
            $cellMap[$cell] = $this->pdsValue($value);
        }

        // Prefix Name Extension fields with a short label so the meaning of
        // "JR" or "N/A" is obvious when reading the sheet.
        foreach (['L11', 'G37', 'G44'] as $extCell) {
            if (! array_key_exists($extCell, $cellMap)) {
                continue;
            }

            $value = (string) $cellMap[$extCell];
            $cellMap[$extCell] = 'NAME EXTENSION (JR., SR)                                              '.$value;
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

            $cellMap["I{$row}"] = $this->pdsValue($childName !== '' ? $childName : null);

            $dob = $this->formatDate($child->dob ?? null);
            $cellMap["M{$row}"] = $this->pdsValue($dob);
        }

        $outputPath = $this->populateTemplateWorkbook(
            $templatePath,
            'C1',
            $cellMap,
            (string) ($personalInfo->gender ?? ''),
            (string) ($personalInfo->civil_stat ?? '')
        );
        $filename = 'PDS_'.$hrid.'_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($outputPath): void {
            readfile($outputPath);
            @unlink($outputPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolvePdsTemplatePath(): ?string
    {
        $projectTemplate = base_path('PDS.xlsx');
        if (is_file($projectTemplate)) {
            return $projectTemplate;
        }

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

    private function formatHeight(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);
        // If units already present, don't duplicate.
        if (stripos($stringValue, 'm') !== false) {
            return $stringValue;
        }

        return $stringValue.' m';
    }

    private function formatWeight(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);
        // If units already present, don't duplicate.
        if (stripos($stringValue, 'kg') !== false) {
            return $stringValue;
        }

        return $stringValue.' kg';
    }

    private function deriveCountryFromCitizenship(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);

        // Common pattern we use in the UI: "Filipino – Philippines"
        if (str_contains($stringValue, '–')) {
            $parts = array_map('trim', explode('–', $stringValue, 2));
            if (isset($parts[1]) && $parts[1] !== '') {
                return $parts[1];
            }
        }

        // Fallback: if it already looks like a country, just return it.
        return $stringValue;
    }

    private function pdsValue(mixed $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? 'N/A' : $stringValue;
    }

    private function populateTemplateWorkbook(
        string $templatePath,
        string $sheetName,
        array $cellMap,
        string $gender = '',
        string $civilStatus = ''
    ): string
    {
        if (! class_exists('PclZip')) {
            require_once base_path('vendor/phpoffice/phpexcel/Classes/PHPExcel/Shared/PCLZip/pclzip.lib.php');
        }

        if (! is_file($templatePath) || ! is_readable($templatePath)) {
            throw new \RuntimeException("PDS template is missing or unreadable: {$templatePath}");
        }

        $tempRoot = storage_path('app/tmp/pds_'.uniqid());
        $extractRoot = $tempRoot.'/extract';
        $outputPath = $tempRoot.'/output.xlsx';

        @mkdir($extractRoot, 0777, true);

        $zip = $this->initPclZip($templatePath);
        $extractResult = $zip->extract(
            PCLZIP_OPT_PATH,
            $extractRoot,
            PCLZIP_OPT_TEMP_FILE_OFF
        );
        if ($extractResult === 0) {
            throw new \RuntimeException('Failed to extract template: '.$zip->errorInfo(true));
        }

        $workbookXmlPath = $extractRoot.'/xl/workbook.xml';
        $relsXmlPath = $extractRoot.'/xl/_rels/workbook.xml.rels';
        if (!is_file($workbookXmlPath) || !is_file($relsXmlPath)) {
            throw new \RuntimeException('Invalid XLSX template structure.');
        }

        $workbookXml = file_get_contents($workbookXmlPath) ?: '';
        $relsXml = file_get_contents($relsXmlPath) ?: '';
        $sheetEntryPath = $this->resolveWorksheetEntryPath($workbookXml, $relsXml, $sheetName) ?? 'xl/worksheets/sheet1.xml';

        $sheetXmlPath = $extractRoot.'/'.$sheetEntryPath;
        if (!is_file($sheetXmlPath)) {
            throw new \RuntimeException("Worksheet file not found: {$sheetEntryPath}");
        }

        $sheetXml = file_get_contents($sheetXmlPath) ?: '';
        $updatedSheetXml = $this->setWorksheetCells($sheetXml, $cellMap);
        $updatedSheetXml = $this->setSheetControlChecks(
            $updatedSheetXml,
            $gender,
            $civilStatus,
            (string) ($cellMap['I13'] ?? ''),
            (string) ($cellMap['I16'] ?? '')
        );
        file_put_contents($sheetXmlPath, $updatedSheetXml);

        $worksheetRelsPath = dirname($sheetXmlPath).'/_rels/'.basename($sheetXmlPath).'.rels';
        if (is_file($worksheetRelsPath)) {
            $worksheetRelsXml = file_get_contents($worksheetRelsPath) ?: '';
            $this->setCtrlPropCheckboxes(
                $worksheetRelsXml,
                dirname($sheetXmlPath),
                $updatedSheetXml,
                $this->buildControlCheckStates(
                    $gender,
                    $civilStatus,
                    (string) ($cellMap['I13'] ?? ''),
                    (string) ($cellMap['I16'] ?? '')
                )
            );
            $vmlPaths = $this->resolveVmlPaths($worksheetRelsXml, dirname($sheetXmlPath));
            foreach ($vmlPaths as $vmlPath) {
                if (! is_file($vmlPath)) {
                    continue;
                }
                $vmlXml = file_get_contents($vmlPath) ?: '';
                $updatedVmlXml = $this->setVmlCheckboxes(
                    $vmlXml,
                    $gender,
                    $civilStatus,
                    (string) ($cellMap['I13'] ?? ''),
                    (string) ($cellMap['I16'] ?? '')
                );
                file_put_contents($vmlPath, $updatedVmlXml);
            }
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractRoot, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        $outputZip = $this->initPclZip($outputPath);
        $createResult = $outputZip->create(
            $files,
            PCLZIP_OPT_REMOVE_PATH,
            $extractRoot,
            PCLZIP_OPT_TEMP_FILE_OFF
        );
        if ($createResult === 0) {
            throw new \RuntimeException('Failed to create output workbook: '.$outputZip->errorInfo(true));
        }

        $this->deleteDirectory($extractRoot);
        @rmdir($tempRoot);

        return $outputPath;
    }

    private function initPclZip(string $archivePath): \PclZip
    {
        $zip = new \PclZip($archivePath);

        // PHP 8 may not invoke the legacy same-name constructor used by old PclZip versions.
        if ((string) ($zip->zipname ?? '') === '') {
            if (method_exists($zip, 'PclZip')) {
                $zip->PclZip($archivePath);
            } else {
                $zip->zipname = $archivePath;
            }
        }

        return $zip;
    }

    private function resolveWorksheetEntryPath(string $workbookXml, string $relsXml, string $sheetName): ?string
    {
        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);
        if (! $workbook || ! $rels) {
            return null;
        }

        $workbook->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $matches = $workbook->xpath("//s:sheet[@name='{$sheetName}']");
        if (! $matches || ! isset($matches[0])) {
            return null;
        }

        $ridAttr = $matches[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rid = (string) ($ridAttr['id'] ?? '');
        if ($rid === '') {
            return null;
        }

        $relMatches = $rels->xpath("//pr:Relationship[@Id='{$rid}']");
        if (! $relMatches || ! isset($relMatches[0])) {
            return null;
        }

        $target = (string) ($relMatches[0]['Target'] ?? '');
        if ($target === '') {
            return null;
        }

        $target = ltrim($target, '/');
        if (! str_starts_with($target, 'xl/')) {
            $target = 'xl/'.$target;
        }

        return $target;
    }

    private function setWorksheetCells(string $worksheetXml, array $cellMap): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($worksheetXml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $sheetData = $xpath->query('/s:worksheet/s:sheetData')->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return $worksheetXml;
        }

        foreach ($cellMap as $cellRef => $value) {
            preg_match('/([A-Z]+)(\d+)/', $cellRef, $parts);
            $rowNumber = isset($parts[2]) ? (int) $parts[2] : 0;
            if ($rowNumber <= 0) {
                continue;
            }

            $rowNode = $xpath->query("/s:worksheet/s:sheetData/s:row[@r='{$rowNumber}']")->item(0);
            if (! $rowNode instanceof \DOMElement) {
                $rowNode = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'row');
                $rowNode->setAttribute('r', (string) $rowNumber);
                $sheetData->appendChild($rowNode);
            }

            $cellNode = $xpath->query("s:c[@r='{$cellRef}']", $rowNode)->item(0);
            if (! $cellNode instanceof \DOMElement) {
                $cellNode = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'c');
                $cellNode->setAttribute('r', $cellRef);
                $rowNode->appendChild($cellNode);
            }

            while ($cellNode->firstChild) {
                $cellNode->removeChild($cellNode->firstChild);
            }

            $cellNode->setAttribute('t', 'inlineStr');
            $is = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'is');
            $t = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $t->appendChild($dom->createTextNode((string) $value));
            $is->appendChild($t);
            $cellNode->appendChild($is);
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    private function setSheetControlChecks(
        string $worksheetXml,
        string $gender,
        string $civilStatus,
        string $citizenship,
        string $dualCitizenship
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($worksheetXml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $checkStates = $this->buildControlCheckStates($gender, $civilStatus, $citizenship, $dualCitizenship);

        $controlNodes = $xpath->query('//*[local-name()="control"]');
        foreach ($controlNodes as $controlNode) {
            if (! $controlNode instanceof \DOMElement) {
                continue;
            }

            $controlName = $controlNode->getAttribute('name');
            if ($controlName === '' || ! array_key_exists($controlName, $checkStates)) {
                continue;
            }

            $controlPr = $xpath->query('*[local-name()="controlPr"]', $controlNode)->item(0);
            if (! $controlPr instanceof \DOMElement) {
                continue;
            }

            $controlPr->setAttribute('checked', $checkStates[$controlName] ? 'Checked' : 'Unchecked');
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    private function setCtrlPropCheckboxes(
        string $worksheetRelsXml,
        string $relsDirectory,
        string $worksheetXml,
        array $checkStates
    ): void {
        $rels = @simplexml_load_string($worksheetRelsXml);
        if (! $rels) {
            return;
        }

        $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $ctrlPropRelations = $rels->xpath("//pr:Relationship[contains(@Type, '/ctrlProp')]");
        if (! $ctrlPropRelations) {
            return;
        }

        $ctrlPropPathsByRid = [];
        foreach ($ctrlPropRelations as $relation) {
            $rid = (string) ($relation['Id'] ?? '');
            $target = (string) ($relation['Target'] ?? '');
            if ($rid === '' || $target === '') {
                continue;
            }

            $target = str_replace('\\', '/', $target);
            $resolved = realpath($relsDirectory.'/'.$target) ?: $relsDirectory.'/'.$target;
            $ctrlPropPathsByRid[$rid] = $resolved;
        }

        if ($ctrlPropPathsByRid === []) {
            return;
        }

        $sheetDom = new \DOMDocument('1.0', 'UTF-8');
        $sheetDom->loadXML($worksheetXml);
        $sheetXPath = new \DOMXPath($sheetDom);

        $controlStateByRid = [];
        $controlNodes = $sheetXPath->query('//*[local-name()="control"]');
        foreach ($controlNodes as $controlNode) {
            if (! $controlNode instanceof \DOMElement) {
                continue;
            }

            $controlName = $controlNode->getAttribute('name');
            if ($controlName === '' || ! array_key_exists($controlName, $checkStates)) {
                continue;
            }

            $rid = $controlNode->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            if ($rid === '') {
                $rid = $controlNode->getAttribute('r:id');
            }
            if ($rid === '') {
                continue;
            }

            $controlStateByRid[$rid] = (bool) $checkStates[$controlName];
        }

        foreach ($controlStateByRid as $rid => $shouldCheck) {
            $ctrlPropPath = $ctrlPropPathsByRid[$rid] ?? null;
            if (! $ctrlPropPath || ! is_file($ctrlPropPath)) {
                continue;
            }

            $ctrlPropXml = file_get_contents($ctrlPropPath);
            if (! is_string($ctrlPropXml) || trim($ctrlPropXml) === '') {
                continue;
            }

            $ctrlPropDom = new \DOMDocument('1.0', 'UTF-8');
            if (! @$ctrlPropDom->loadXML($ctrlPropXml)) {
                continue;
            }

            $ctrlPropXPath = new \DOMXPath($ctrlPropDom);
            $formControl = $ctrlPropXPath->query('//*[local-name()="formControlPr"]')->item(0);
            if (! $formControl instanceof \DOMElement) {
                continue;
            }

            $formControl->setAttribute('checked', $shouldCheck ? '1' : '0');
            file_put_contents($ctrlPropPath, $ctrlPropDom->saveXML() ?: $ctrlPropXml);
        }
    }

    private function buildControlCheckStates(
        string $gender,
        string $civilStatus,
        string $citizenship,
        string $dualCitizenship
    ): array {
        // Names are based on actual controls in PDS.xlsx sheet1.xml.
        $checkStates = [
            'Check Box 25' => false, // Male
            'Check Box 26' => false, // Female
            'Check Box 34' => false, // Single
            'Check Box 35' => false, // Married
            'Check Box 36' => false, // Widowed
            'Check Box 38' => false, // Separated
            'Check Box 37' => false, // Other/s
            'Check Box 21' => false, // Filipino
            'Check Box 22' => false, // Dual Citizenship
        ];

        $genderKey = $this->normalizeGenderToCheckbox($gender);
        if ($genderKey === 'male') {
            $checkStates['Check Box 25'] = true;
        } elseif ($genderKey === 'female') {
            $checkStates['Check Box 26'] = true;
        }

        $civilKey = $this->normalizeCivilStatusToCheckbox($civilStatus);
        if ($civilKey === 'single') {
            $checkStates['Check Box 34'] = true;
        } elseif ($civilKey === 'married') {
            $checkStates['Check Box 35'] = true;
        } elseif ($civilKey === 'widowed') {
            $checkStates['Check Box 36'] = true;
        } elseif ($civilKey === 'separated') {
            $checkStates['Check Box 38'] = true;
        } elseif ($civilKey === 'other/s') {
            $checkStates['Check Box 37'] = true;
        }

        $normalizedCitizenship = strtolower(trim($citizenship));
        if ($normalizedCitizenship !== '' && str_contains($normalizedCitizenship, 'filipino')) {
            $checkStates['Check Box 21'] = true;
        }

        $normalizedDual = strtolower(trim($dualCitizenship));
        if ($normalizedDual !== '' && $normalizedDual !== 'n/a') {
            $checkStates['Check Box 22'] = true;
        }

        return $checkStates;
    }

    private function resolveVmlPaths(string $worksheetRelsXml, string $relsDirectory): array
    {
        $rels = @simplexml_load_string($worksheetRelsXml);
        if (! $rels) {
            return [];
        }

        $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $matches = $rels->xpath("//pr:Relationship[contains(@Type, '/vmlDrawing')]");
        if (! $matches) {
            return [];
        }

        $paths = [];
        foreach ($matches as $rel) {
            $target = (string) ($rel['Target'] ?? '');
            if ($target === '') {
                continue;
            }
            $target = str_replace('\\', '/', $target);
            $paths[] = realpath($relsDirectory.'/'.$target) ?: $relsDirectory.'/'.$target;
        }

        return $paths;
    }

    private function setVmlCheckboxes(
        string $vmlXml,
        string $gender,
        string $civilStatus,
        string $citizenship,
        string $dualCitizenship
    ): string
    {
        $selected = [];
        $genderKey = $this->normalizeGenderToCheckbox($gender);
        if ($genderKey !== null) {
            $selected[$genderKey] = true;
        }
        $civilKey = $this->normalizeCivilStatusToCheckbox($civilStatus);
        if ($civilKey !== null) {
            $selected[$civilKey] = true;
        }
        $normalizedCitizenship = strtolower(trim($citizenship));
        if ($normalizedCitizenship !== '' && str_contains($normalizedCitizenship, 'filipino')) {
            $selected['filipino'] = true;
        }
        $normalizedDual = strtolower(trim($dualCitizenship));
        if ($normalizedDual !== '' && $normalizedDual !== 'n/a') {
            $selected['dual citizenship'] = true;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $dom->loadXML($vmlXml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
        $xpath->registerNamespace('x', 'urn:schemas-microsoft-com:office:excel');

        $shapes = $xpath->query('//v:shape[x:ClientData[@ObjectType="Checkbox"]]');
        foreach ($shapes as $shape) {
            if (! $shape instanceof \DOMElement) {
                continue;
            }

            $labelText = '';
            $textNodes = $xpath->query('v:textbox//text()', $shape);
            foreach ($textNodes as $textNode) {
                $labelText .= $textNode->nodeValue;
            }
            $labelKey = $this->detectCheckboxLabelKey($labelText);
            if ($labelKey === null) {
                continue;
            }

            $clientData = $xpath->query('x:ClientData[@ObjectType="Checkbox"]', $shape)->item(0);
            if (! $clientData instanceof \DOMElement) {
                continue;
            }

            $checkedNode = $xpath->query('x:Checked', $clientData)->item(0);
            $shouldCheck = isset($selected[$labelKey]);

            if ($shouldCheck && ! $checkedNode) {
                $checkedElement = $dom->createElementNS('urn:schemas-microsoft-com:office:excel', 'x:Checked');
                // Some Excel variants require explicit "1" text value.
                $checkedElement->appendChild($dom->createTextNode('1'));
                $clientData->appendChild($checkedElement);
            } elseif ($shouldCheck && $checkedNode instanceof \DOMElement) {
                while ($checkedNode->firstChild) {
                    $checkedNode->removeChild($checkedNode->firstChild);
                }
                $checkedNode->appendChild($dom->createTextNode('1'));
            }

            if (! $shouldCheck && $checkedNode) {
                $clientData->removeChild($checkedNode);
            }
        }

        return $dom->saveXML() ?: $vmlXml;
    }

    private function detectCheckboxLabelKey(string $label): ?string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? ''));

        $checks = [
            'dual citizenship' => 'dual citizenship',
            'filipino' => 'filipino',
            'other/s:' => 'other/s',
            'separated' => 'separated',
            'widowed' => 'widowed',
            'married' => 'married',
            'single' => 'single',
            'female' => 'female',
            'male' => 'male',
        ];

        foreach ($checks as $needle => $key) {
            if (str_contains($normalized, $needle)) {
                return $key;
            }
        }

        return null;
    }

    private function normalizeGenderToCheckbox(string $gender): ?string
    {
        $normalized = strtolower(trim($gender));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'female')) {
            return 'female';
        }
        if (str_contains($normalized, 'male')) {
            return 'male';
        }

        return null;
    }

    private function normalizeCivilStatusToCheckbox(string $civilStatus): ?string
    {
        $normalized = strtolower(trim($civilStatus));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'single')) {
            return 'single';
        }
        if (str_contains($normalized, 'married')) {
            return 'married';
        }
        if (str_contains($normalized, 'widowed')) {
            return 'widowed';
        }
        if (str_contains($normalized, 'separated')) {
            return 'separated';
        }
        if (str_contains($normalized, 'other')) {
            return 'other/s';
        }

        return null;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($directory);
    }
}
