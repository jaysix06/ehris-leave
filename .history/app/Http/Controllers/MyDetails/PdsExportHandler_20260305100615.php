<?php

namespace App\Http\Controllers\MyDetails;

use Illuminate\Support\Carbon;

class PdsExportHandler
{
    /**
     * Export PDS data to an xlsx file. Returns the path to the generated file.
     *
     * @param  array{hrid: mixed, dbProfile: object|null, officialInfo: object|null, personalInfo: object|null, contactInfo: object|null, family: \Illuminate\Support\Collection, education: \Illuminate\Support\Collection, workExperience: \Illuminate\Support\Collection, eligibility: \Illuminate\Support\Collection, spouse: object|null, father: object|null, mother: object|null, children: \Illuminate\Support\Collection, voluntaryWork?: \Illuminate\Support\Collection|array<int, mixed>, training?: \Illuminate\Support\Collection|array<int, mixed>, awards?: \Illuminate\Support\Collection|array<int, mixed>, expertise?: \Illuminate\Support\Collection|array<int, mixed>, affiliation?: \Illuminate\Support\Collection|array<int, mixed>}  $data
     */
    public function export(string $templatePath, array $data): string
    {
        $hrid = $data['hrid'];
        $dbProfile = $data['dbProfile'];
        $officialInfo = $data['officialInfo'];
        $personalInfo = $data['personalInfo'];
        $contactInfo = $data['contactInfo'];
        $spouse = $data['spouse'];
        $father = $data['father'];
        $mother = $data['mother'];
        $children = $data['children'];

        $cellMap = [
            'D10' => $officialInfo->lastname ?? $dbProfile->lastname ?? null,
            'D11' => $officialInfo->firstname ?? $dbProfile->firstname ?? null,
            'D12' => $officialInfo->middlename ?? $dbProfile->middlename ?? null,
            'L11' => $officialInfo->extension
                ?? ($officialInfo->extname ?? null)
                ?? ($dbProfile->extname ?? null),
            'D13' => $this->formatDate($personalInfo->dob ?? null),
            'D15' => $personalInfo->pob ?? null,
            'I13' => $personalInfo->citizenship ?? null,
            'I16' => $personalInfo->dual_citizenship ?? null,
            'M15' => $this->resolveCountryForPds(
                $personalInfo->country ?? null,
                $personalInfo->citizenship ?? null
            ),
            'I17' => $contactInfo->house_block_lotnum ?? null,
            'L17' => $contactInfo->street_add ?? null,
            'I19' => $contactInfo->subdivision_village ?? null,
            'L19' => $contactInfo->residential_barangay_name ?? null,
            'I22' => $contactInfo->city_municipality ?? null,
            'L22' => $contactInfo->residential_province_name ?? null,
            'I24' => $contactInfo->zip_code ?? null,
            'I25' => $contactInfo->house_block_lotnum1 ?? null,
            'L25' => $contactInfo->street_add1 ?? null,
            'I27' => $contactInfo->subdivision_village1 ?? null,
            'L27' => $contactInfo->permanent_barangay_name ?? null,
            'I29' => $contactInfo->city_municipality1 ?? null,
            'L29' => $contactInfo->permanent_province_name ?? null,
            'I31' => $contactInfo->zip_code1 ?? null,
            'D22' => $this->formatHeight($personalInfo->height ?? null),
            'D24' => $this->formatWeight($personalInfo->weight ?? null),
            'D25' => $personalInfo->blood_type ?? null,
            'D27' => $this->firstAvailableValue($personalInfo, ['umid', 'umid_no', 'umid_num']),
            'D29' => $personalInfo->pag_ibig ?? $personalInfo->pagibig ?? null,
            'D31' => $personalInfo->philhealth ?? null,
            'D32' => $this->firstAvailableValue($personalInfo, ['philsys', 'philsys_no', 'philsys_num', 'psn']),
            'D33' => $this->firstAvailableValue($personalInfo, ['tin', 'tin_no']),
            'D34' => $this->firstAvailableValue($personalInfo, ['agency_emp_num', 'agency_employee_no', 'agency_emp_no']),
            'I32' => $contactInfo->phone_num ?? null,
            'I33' => $contactInfo->mobile_num ?? null,
            'I34' => $contactInfo->email ?? ($officialInfo->email ?? $dbProfile->email ?? null),
            'D36' => $spouse?->lastname ?? null,
            'D37' => $spouse?->firstname ?? null,
            'G37' => $spouse?->extension ?? ($spouse?->extname ?? null),
            'D38' => $spouse?->middlename ?? null,
            'D39' => $spouse?->occupation ?? null,
            'D40' => $spouse?->employer_name ?? null,
            'D41' => $spouse?->business_add ?? null,
            'D42' => $spouse?->tel_num ?? null,
            'D43' => $father?->lastname ?? null,
            'D44' => $father?->firstname ?? null,
            'G44' => $father?->extension ?? ($father?->extname ?? null),
            'D45' => $father?->middlename ?? null,
            'D47' => $mother?->lastname ?? null,
            'D48' => $mother?->firstname ?? null,
            'D49' => $mother?->middlename ?? null,
        ];

        $civilStatus = (string) ($personalInfo->civil_stat ?? '');
        $civilKey = $this->normalizeCivilStatusToCheckbox($civilStatus) ?? '';
        if ($civilKey !== 'married') {
            $cellMap['D36'] = 'N/A';
        }

        foreach ($cellMap as $cell => $value) {
            $cellMap[$cell] = $this->pdsValue($value);
        }

        foreach (['L11', 'G37', 'G44'] as $extCell) {
            if (!array_key_exists($extCell, $cellMap)) {
                continue;
            }
        
            $label = 'NAME EXTENSION (JR., SR)';
            // Pads the label with spaces on the right side up to 70 characters
            $cellMap[$extCell] = str_pad($label, 70, ' ', STR_PAD_RIGHT) . $cellMap[$extCell];
        }

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

        $education = $data['education'];
        $educationRows = $this->buildEducationExportRows($education);
        // If template has 6 rows (54–59) but we only have 5 levels, add a 6th row so we write and style row 59 (F–G border).
        if (count($educationRows) === 5) {
            $educationRows[] = [
                'level_label' => 'GRADUATE STUDIES',
                'school_name' => null,
                'course' => null,
                'from_year' => null,
                'to_year' => null,
                'highest_grade' => null,
                'year_graduated' => null,
                'scholarship' => null,
            ];
        }
        $educationStartRow = 54;
        $currentRow = $educationStartRow;

        foreach ($educationRows as $row) {
            $cellMap["A{$currentRow}"] = '';
            $cellMap["B{$currentRow}"] = $this->pdsValue($row['level_label']);
            $cellMap["C{$currentRow}"] = '';
            $cellMap["D{$currentRow}"] = $this->pdsValue($row['school_name'] ?? null);
            $cellMap["G{$currentRow}"] = $this->pdsValue($row['course'] ?? null);
            $cellMap["J{$currentRow}"] = $this->pdsValue($row['from_year'] ?? null);
            $cellMap["K{$currentRow}"] = $this->pdsValue($row['to_year'] ?? null);
            $cellMap["L{$currentRow}"] = $this->pdsValue($row['highest_grade'] ?? null);
            $cellMap["M{$currentRow}"] = $this->pdsValue($row['year_graduated'] ?? null);
            $cellMap["N{$currentRow}"] = $this->pdsValue($row['scholarship'] ?? null);
            $currentRow++;
        }

        $c2Handler = new PdsC2Handler;
        $c2CellMap = $c2Handler->buildCellMap(
            $data['eligibility'],
            $data['workExperience'],
            fn ($value) => $this->formatDate($value),
            fn ($value) => $this->pdsValue($value)
        );
        $c3Handler = new \App\Http\Controllers\MyDetails\PdsC3Handler;
        $c3CellMap = $c3Handler->buildCellMap(
            $data['voluntaryWork'] ?? [],
            $data['training'] ?? [],
            $data['expertise'] ?? [],
            $data['awards'] ?? [],
            $data['affiliation'] ?? [],
            fn ($value) => $this->formatDate($value),
            fn ($value) => $this->pdsValue($value)
        );

        return $this->populateTemplateWorkbook(
            $templatePath,
            'C1',
            $cellMap,
            (string) ($personalInfo->gender ?? ''),
            (string) ($personalInfo->civil_stat ?? ''),
            [],
            count($educationRows),
            $c2CellMap,
            $c3CellMap
        );
    }

    public function resolvePdsTemplatePath(): ?string
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

        if (str_contains($stringValue, '–')) {
            $parts = array_map('trim', explode('–', $stringValue, 2));
            if (isset($parts[1]) && $parts[1] !== '') {
                return $parts[1];
            }
        }

        if ($this->isFilipinoCitizenship($stringValue)) {
            return 'Philippines';
        }

        return $stringValue;
    }

    private function resolveCountryForPds(mixed $country, mixed $citizenship): ?string
    {
        $countryValue = trim((string) ($country ?? ''));
        if ($countryValue !== '') {
            return $countryValue;
        }

        $derivedCountry = $this->deriveCountryFromCitizenship($citizenship);
        if ($derivedCountry !== null && trim($derivedCountry) !== '') {
            return $derivedCountry;
        }

        return $this->isFilipinoCitizenship((string) ($citizenship ?? '')) ? 'Philippines' : null;
    }

    private function firstAvailableValue(?object $source, array $keys): mixed
    {
        if (! is_object($source)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($source->{$key}) && trim((string) $source->{$key}) !== '') {
                return $source->{$key};
            }
        }

        return null;
    }

    private function pdsValue(mixed $value): string
    {
        if ($value === null) {
            return 'N/A';
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? 'N/A' : $stringValue;
    }

    /**
     * Keep only XML 1.0 allowed characters so Microsoft Excel 2016 opens the workbook without
     * "problem with some content". WPS and other apps may accept more; Excel is strict.
     * Allowed: tab, LF, CR, #x20-#xD7FF, #xE000-#xFFFD, and supplementary planes.
     */
    private function sanitizeForXml(string $s): string
    {
        $s = $s ?? '';
        $result = preg_replace_callback('/./u', function (array $m): string {
            $char = $m[0];
            $byte = strlen($char) === 1 ? ord($char) : null;
            if ($byte !== null) {
                if ($byte === 0x09 || $byte === 0x0A || $byte === 0x0D || ($byte >= 0x20 && $byte <= 0x7F)) {
                    return $char;
                }

                return '';
            }
            $codepoint = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
            $cp = $codepoint ? $codepoint[1] : 0;
            if ($cp >= 0x80 && $cp <= 0xD7FF) {
                return $char;
            }
            if ($cp >= 0xE000 && $cp <= 0xFFFD) {
                return $char;
            }
            if ($cp >= 0x10000 && $cp <= 0x10FFFF) {
                return $char;
            }

            return '';
        }, $s);

        return $result ?? $s;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $education
     * @return object{school_name: string|null, course: string|null, from_year: string|null, to_year: string|null, highest_grade: string|null, year_graduated: string|null, scholarship: string|null}
     */
    private function findEducationRecordByLevel($education, string $levelLabel): object
    {
        $normalized = strtoupper((string) $levelLabel);
        foreach ($education as $row) {
            $level = strtoupper(trim((string) ($row->education_level ?? '')));
            if ($level === '') {
                continue;
            }
            $matches = match (true) {
                $normalized === 'ELEMENTARY' => str_contains($level, 'ELEMENTARY'),
                $normalized === 'SECONDARY' => str_contains($level, 'SECONDARY') || str_contains($level, 'HIGH SCHOOL'),
                $normalized === 'VOCATIONAL / TRADE COURSE' => str_contains($level, 'VOCATIONAL') || str_contains($level, 'TRADE'),
                $normalized === 'GRADUATE STUDIES' => str_contains($level, 'MASTER') || str_contains($level, 'MASTERAL')
                    || ($level === 'GRADUATE STUDIES') || (str_contains($level, 'GRADUATE') && ! str_contains($level, 'COLLEGE')),
                $normalized === 'COLLEGE' => str_contains($level, 'COLLEGE'),
                default => $level === $normalized,
            };
            if ($matches) {
                return (object) [
                    'school_name' => $row->school_name ?? null,
                    'course' => $row->course ?? null,
                    'from_year' => $row->from_year ?? null,
                    'to_year' => $row->to_year ?? null,
                    'highest_grade' => $row->highest_grade ?? null,
                    'year_graduated' => $row->year_graduated ?? null,
                    'scholarship' => $row->scholarship ?? null,
                ];
            }
        }

        return (object) [
            'school_name' => null,
            'course' => null,
            'from_year' => null,
            'to_year' => null,
            'highest_grade' => null,
            'year_graduated' => null,
            'scholarship' => null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $education
     * @return array<int, object{school_name: string|null, course: string|null, from_year: string|null, to_year: string|null, highest_grade: string|null, year_graduated: string|null, scholarship: string|null}>
     */
    private function findAllEducationRecordsByLevel($education, string $levelLabel): array
    {
        $normalized = strtoupper((string) $levelLabel);
        $out = [];
        foreach ($education as $row) {
            $level = strtoupper(trim((string) ($row->education_level ?? '')));
            if ($level === '') {
                continue;
            }
            $matches = match (true) {
                $normalized === 'ELEMENTARY' => str_contains($level, 'ELEMENTARY'),
                $normalized === 'SECONDARY' => str_contains($level, 'SECONDARY') || str_contains($level, 'HIGH SCHOOL'),
                $normalized === 'VOCATIONAL / TRADE COURSE' => str_contains($level, 'VOCATIONAL') || str_contains($level, 'TRADE'),
                $normalized === 'GRADUATE STUDIES' => str_contains($level, 'MASTER') || str_contains($level, 'MASTERAL')
                    || ($level === 'GRADUATE STUDIES') || (str_contains($level, 'GRADUATE') && ! str_contains($level, 'COLLEGE')),
                $normalized === 'COLLEGE' => str_contains($level, 'COLLEGE'),
                default => $level === $normalized,
            };
            if ($matches) {
                $out[] = (object) [
                    'school_name' => $row->school_name ?? null,
                    'course' => $row->course ?? null,
                    'from_year' => $row->from_year ?? null,
                    'to_year' => $row->to_year ?? null,
                    'highest_grade' => $row->highest_grade ?? null,
                    'year_graduated' => $row->year_graduated ?? null,
                    'scholarship' => $row->scholarship ?? null,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $education
     * @return array<int, array{level_label: string, school_name: string|null, course: string|null, from_year: string|null, to_year: string|null, highest_grade: string|null, year_graduated: string|null, scholarship: string|null}>
     */
    private function buildEducationExportRows($education): array
    {
        $levelOrder = [
            'ELEMENTARY',
            'SECONDARY',
            'VOCATIONAL / TRADE COURSE',
            'COLLEGE',
            'GRADUATE STUDIES',
        ];
        $rows = [];
        foreach ($levelOrder as $levelLabel) {
            $records = $this->findAllEducationRecordsByLevel($education, $levelLabel);
            if (count($records) === 0) {
                $rows[] = [
                    'level_label' => $levelLabel,
                    'school_name' => null,
                    'course' => null,
                    'from_year' => null,
                    'to_year' => null,
                    'highest_grade' => null,
                    'year_graduated' => null,
                    'scholarship' => null,
                ];

                continue;
            }
            foreach ($records as $record) {
                $rows[] = [
                    'level_label' => $levelLabel,
                    'school_name' => $record->school_name,
                    'course' => $record->course,
                    'from_year' => $record->from_year,
                    'to_year' => $record->to_year,
                    'highest_grade' => $record->highest_grade,
                    'year_graduated' => $record->year_graduated,
                    'scholarship' => $record->scholarship,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $education
     * @return array<int, array{level_label: string, school_name: string|null, course: string|null, from_year: string|null, to_year: string|null, highest_grade: string|null, year_graduated: string|null, scholarship: string|null}>
     */
    private function buildEducationExportRowsWithGraduateStudiesInFifthRow($education): array
    {
        $rows = [];
        $empty = fn (string $level) => [
            'level_label' => $level,
            'school_name' => null,
            'course' => null,
            'from_year' => null,
            'to_year' => null,
            'highest_grade' => null,
            'year_graduated' => null,
            'scholarship' => null,
        ];
        $toRow = fn (object $r, string $level) => [
            'level_label' => $level,
            'school_name' => $r->school_name,
            'course' => $r->course,
            'from_year' => $r->from_year,
            'to_year' => $r->to_year,
            'highest_grade' => $r->highest_grade,
            'year_graduated' => $r->year_graduated,
            'scholarship' => $r->scholarship,
        ];

        $elementary = $this->findAllEducationRecordsByLevel($education, 'ELEMENTARY');
        $rows[] = count($elementary) > 0 ? $toRow($elementary[0], 'ELEMENTARY') : $empty('ELEMENTARY');

        $secondary = $this->findAllEducationRecordsByLevel($education, 'SECONDARY');
        $rows[] = count($secondary) > 0 ? $toRow($secondary[0], 'SECONDARY') : $empty('SECONDARY');

        $vocational = $this->findAllEducationRecordsByLevel($education, 'VOCATIONAL / TRADE COURSE');
        $rows[] = count($vocational) > 0 ? $toRow($vocational[0], 'VOCATIONAL / TRADE COURSE') : $empty('VOCATIONAL / TRADE COURSE');

        $college = $this->findAllEducationRecordsByLevel($education, 'COLLEGE');
        $graduate = $this->findAllEducationRecordsByLevel($education, 'GRADUATE STUDIES');

        $rows[] = count($college) > 0 ? $toRow($college[0], 'COLLEGE') : $empty('COLLEGE');

        $rows[] = count($graduate) > 0 ? $toRow($graduate[0], 'GRADUATE STUDIES') : $empty('GRADUATE STUDIES');

        for ($i = 1; $i < count($college); $i++) {
            $rows[] = $toRow($college[$i], 'COLLEGE');
        }
        for ($i = 1; $i < count($graduate); $i++) {
            $rows[] = $toRow($graduate[$i], 'GRADUATE STUDIES');
        }

        return $rows;
    }

    /**
     * @param  array<string, array{start: int, end: int}>  $educationMergeGroups
     * @param  array<string, string>  $c2CellMap
     * @param  array<string, string>  $c3CellMap
     */
    private function populateTemplateWorkbook(
        string $templatePath,
        string $sheetName,
        array $cellMap,
        string $gender = '',
        string $civilStatus = '',
        array $educationMergeGroups = [],
        int $educationRowCount = 5,
        array $c2CellMap = [],
        array $c3CellMap = []
    ): string {
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
        if (! is_file($workbookXmlPath) || ! is_file($relsXmlPath)) {
            throw new \RuntimeException('Invalid XLSX template structure.');
        }

        $workbookXml = file_get_contents($workbookXmlPath) ?: '';
        $relsXml = file_get_contents($relsXmlPath) ?: '';
        $sheetEntryPath = $this->resolveWorksheetEntryPath($workbookXml, $relsXml, $sheetName) ?? 'xl/worksheets/sheet1.xml';

        $sheetXmlPath = $extractRoot.'/'.$sheetEntryPath;
        if (! is_file($sheetXmlPath)) {
            throw new \RuntimeException("Worksheet file not found: {$sheetEntryPath}");
        }

        $sheetXml = file_get_contents($sheetXmlPath) ?: '';
        $extraRows = max(0, $educationRowCount - 5);
        if ($extraRows > 0) {
            $sheetXml = $this->insertRowsBefore($sheetXml, 59, $extraRows);
        }
        $educationStyleMap = $this->getEducationStyleMapFromTemplate($sheetXml);
        $educationStyleIndex = $this->getEducationRowStyleIndex($sheetXml);
        $educationEndRow = 54 + $educationRowCount - 1;

        // Keep styles.xml untouched for Excel 2016 compatibility.
        // We still apply existing template style indexes to cells, but avoid cloning/creating
        // additional xf/border records that can trigger style-part repair in older Excel.
        $fullBoxStyleForRow59 = null;

        $updatedSheetXml = $this->setWorksheetCells(
            $sheetXml,
            $cellMap,
            $educationStyleIndex,
            54,
            $educationEndRow,
            $educationStyleMap,
            $fullBoxStyleForRow59
        );
        $updatedSheetXml = $this->setWorksheetEducationMergeCells($updatedSheetXml, $educationMergeGroups);
        $updatedSheetXml = $this->setWorksheetEducationColumnDEFMerge($updatedSheetXml, 54, $educationEndRow);
        $updatedSheetXml = $this->setWorksheetEducationColumnGHIMerge($updatedSheetXml, 54, $educationEndRow);

        $hasMerges = false;
        foreach ($educationMergeGroups as $range) {
            if (($range['end'] ?? $range['start']) > $range['start']) {
                $hasMerges = true;
                break;
            }
        }
        $levelStyleForMerge = ($educationStyleMap !== null && isset($educationStyleMap['B'])) ? $educationStyleMap['B'][0] : $educationStyleIndex;
        if ($hasMerges) {
            if ($levelStyleForMerge !== null) {
                $updatedSheetXml = $this->setMergedLevelCellVerticalAlignment(
                    $updatedSheetXml,
                    $educationMergeGroups,
                    $levelStyleForMerge
                );
            }
        }

        $updatedSheetXml = $this->setSheetControlChecks(
            $updatedSheetXml,
            $gender,
            $civilStatus,
            (string) ($cellMap['I13'] ?? ''),
            (string) ($cellMap['I16'] ?? '')
        );
        file_put_contents($sheetXmlPath, $updatedSheetXml);

        if ($c2CellMap !== []) {
            $sheet2EntryPath = $this->resolveWorksheetEntryPath($workbookXml, $relsXml, 'C2');
            if ($sheet2EntryPath !== null) {
                $sheet2Path = $extractRoot.'/'.$sheet2EntryPath;
                if (is_file($sheet2Path)) {
                    $sheet2Xml = file_get_contents($sheet2Path) ?: '';
                    $sheet2Xml = (new PdsC2Handler)->applyCellMapToWorksheetXml($sheet2Xml, $c2CellMap);
                    file_put_contents($sheet2Path, $sheet2Xml);
                }
            }
        }
        if ($c3CellMap !== []) {
            $sheet3EntryPath = $this->resolveWorksheetEntryPath($workbookXml, $relsXml, 'C3');
            if ($sheet3EntryPath !== null) {
                $sheet3Path = $extractRoot.'/'.$sheet3EntryPath;
                if (is_file($sheet3Path)) {
                    $sheet3Xml = file_get_contents($sheet3Path) ?: '';
                    $sheet3Xml = (new \App\Http\Controllers\MyDetails\PdsC3Handler)->applyCellMapToWorksheetXml($sheet3Xml, $c3CellMap);
                    file_put_contents($sheet3Path, $sheet3Xml);
                }
            }
        }

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
            $this->setCountryDropDownSelection(
                $worksheetRelsXml,
                dirname($sheetXmlPath),
                $updatedSheetXml,
                (string) ($cellMap['M15'] ?? '')
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
        $files = $this->sortFilesForOoxmlZip($files, $extractRoot);

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

    /**
     * Sort file list so the repacked xlsx follows OOXML convention: [Content_Types].xml first,
     * then _rels/.rels, then the rest. Improves consistent opening in Excel 2016 vs 2022+.
     *
     * @param  array<int, string>  $files  Full paths to files under $extractRoot
     * @return array<int, string>
     */
    private function sortFilesForOoxmlZip(array $files, string $extractRoot): array
    {
        $extractRoot = rtrim(str_replace('\\', '/', $extractRoot), '/');
        usort($files, function (string $a, string $b) use ($extractRoot): int {
            $relA = str_replace('\\', '/', $a);
            $relA = $extractRoot !== '' && str_starts_with($relA, $extractRoot.'/')
                ? substr($relA, strlen($extractRoot) + 1)
                : $relA;
            $relB = str_replace('\\', '/', $b);
            $relB = $extractRoot !== '' && str_starts_with($relB, $extractRoot.'/')
                ? substr($relB, strlen($extractRoot) + 1)
                : $relB;
            $priority = function (string $r): int {
                if ($r === '[Content_Types].xml') {
                    return 0;
                }
                if ($r === '_rels/.rels') {
                    return 1;
                }

                return 2;
            };
            $pa = $priority($relA);
            $pb = $priority($relB);
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return strcmp($relA, $relB);
        });

        return $files;
    }

    private function initPclZip(string $archivePath): \PclZip
    {
        $zip = new \PclZip($archivePath);

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

    /** Education table columns we write (A–N). H and I give vertical borders between G–H and H–I when not merged. */
    private const EDUCATION_TABLE_COLUMNS = ['A', 'B', 'C', 'D', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];

    /**
     * @return array<string, array{0: int, 1: int}>|null
     */
    private function getEducationStyleMapFromTemplate(string $worksheetXml): ?array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return null;
        }

        $getRowStyles = function (int $rowNum) use ($xpath, $sheetData): ?array {
            $row = $xpath->query(".//*[local-name()='row'][@r='{$rowNum}']", $sheetData)->item(0);
            if (! $row instanceof \DOMElement) {
                return null;
            }
            $styles = [];
            foreach ($xpath->query("*[local-name()='c']", $row) as $cell) {
                if (! $cell instanceof \DOMElement) {
                    continue;
                }
                $r = $cell->getAttribute('r');
                $s = $cell->getAttribute('s');
                if ($r !== '' && $s !== '' && preg_match('/^([A-Z]+)\d+$/', $r, $m)) {
                    $styles[$m[1]] = (int) $s;
                }
            }

            return $styles;
        };

        $row54 = $getRowStyles(54);
        // Template may have 5 rows (54–58) or 6 rows (54–59). Use row 59 for last-row style (thick bottom border) when present.
        $lastRowStyles = $getRowStyles(59) ?? $getRowStyles(58);
        if ($row54 === null || $lastRowStyles === null) {
            return null;
        }

        $map = [];
        foreach (self::EDUCATION_TABLE_COLUMNS as $col) {
            if (isset($row54[$col], $lastRowStyles[$col])) {
                $map[$col] = [$row54[$col], $lastRowStyles[$col]];
            }
        }

        return $map !== [] ? $map : null;
    }

    private function getEducationRowStyleIndex(string $worksheetXml): ?int
    {
        $map = $this->getEducationStyleMapFromTemplate($worksheetXml);
        if ($map !== null && isset($map['B'])) {
            return $map['B'][0];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return null;
        }

        $row54 = $xpath->query(".//*[local-name()='row'][@r='54']", $sheetData)->item(0);
        if (! $row54 instanceof \DOMElement) {
            return null;
        }

        foreach ($xpath->query("*[local-name()='c']", $row54) as $cell) {
            if (! $cell instanceof \DOMElement) {
                continue;
            }
            $s = $cell->getAttribute('s');
            if ($s !== '') {
                return (int) $s;
            }
        }

        return null;
    }

    private function insertRowsBefore(string $worksheetXml, int $beforeRow, int $count): string
    {
        if ($count <= 0) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $xpath = new \DOMXPath($dom);
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath->registerNamespace('s', $ns);

        $shiftCellRef = function (string $ref) use ($beforeRow, $count): string {
            if (preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                $row = (int) $m[2];
                if ($row >= $beforeRow) {
                    return $m[1].($row + $count);
                }
            }
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $ref, $m)) {
                $r1 = (int) $m[2];
                $r2 = (int) $m[4];
                $r1New = $r1 >= $beforeRow ? $r1 + $count : $r1;
                $r2New = $r2 >= $beforeRow ? $r2 + $count : $r2;

                return $m[1].$r1New.':'.$m[3].$r2New;
            }

            return $ref;
        };

        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        if ($sheetData instanceof \DOMElement) {
            foreach ($xpath->query(".//*[local-name()='row']", $sheetData) as $rowNode) {
                if (! $rowNode instanceof \DOMElement) {
                    continue;
                }
                $r = (int) $rowNode->getAttribute('r');
                if ($r >= $beforeRow) {
                    $rowNode->setAttribute('r', (string) ($r + $count));
                }
                foreach ($xpath->query("*[local-name()='c']", $rowNode) as $cellNode) {
                    if (! $cellNode instanceof \DOMElement) {
                        continue;
                    }
                    $cellRef = $cellNode->getAttribute('r');
                    if ($cellRef !== '') {
                        $cellNode->setAttribute('r', $shiftCellRef($cellRef));
                    }
                }
            }
        }

        $mergeCells = $xpath->query("//*[local-name()='mergeCells']")->item(0);
        if ($mergeCells instanceof \DOMElement) {
            foreach ($xpath->query("*[local-name()='mergeCell']", $mergeCells) as $mergeCell) {
                if (! $mergeCell instanceof \DOMElement) {
                    continue;
                }
                $ref = $mergeCell->getAttribute('ref');
                if ($ref !== '') {
                    $mergeCell->setAttribute('ref', $shiftCellRef($ref));
                }
            }
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    /**
     * @param  array<string, string>  $cellMap
     * @param  array<string, array{0: int, 1: int}>|null  $educationStyleMap
     * @param  int|null  $fullBoxStyleForLastRow  If set, apply this style to D and G cells in the last education row (e.g. 59D, 59G) for a full box border.
     */
    private function setWorksheetCells(
        string $worksheetXml,
        array $cellMap,
        ?int $educationStyleIndex = null,
        int $educationStartRow = 54,
        int $educationEndRow = 59,
        ?array $educationStyleMap = null,
        ?int $fullBoxStyleForLastRow = null
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return $worksheetXml;
        }

        foreach ($cellMap as $cellRef => $value) {
            preg_match('/([A-Z]+)(\d+)/', $cellRef, $parts);
            $rowNumber = isset($parts[2]) ? (int) $parts[2] : 0;
            if ($rowNumber <= 0) {
                continue;
            }

            $rowNode = $xpath->query(".//*[local-name()='row'][@r='{$rowNumber}']", $sheetData)->item(0);
            if (! $rowNode instanceof \DOMElement) {
                $rowNode = $dom->createElementNS($ns, 'row');
                $rowNode->setAttribute('r', (string) $rowNumber);
                $prevRow = $rowNumber > 1 ? $xpath->query(".//*[local-name()='row'][@r='".($rowNumber - 1)."']", $sheetData)->item(0) : null;
                if ($prevRow instanceof \DOMElement) {
                    foreach ($prevRow->attributes as $attr) {
                        if ($attr->name === 'r') {
                            continue;
                        }
                        if ($attr->namespaceURI === null || $attr->namespaceURI === '') {
                            $rowNode->setAttribute($attr->name, $attr->value);

                            continue;
                        }
                        try {
                            $prefix = (string) ($attr->prefix ?? '');
                            $localName = (string) ($attr->localName ?? $attr->name);
                            $qualifiedName = $prefix !== '' ? $prefix.':'.$localName : $localName;
                            $rowNode->setAttributeNS($attr->namespaceURI, $qualifiedName, $attr->value);
                        } catch (\DOMException) {
                            // Skip attributes that cause namespace errors (e.g. xmlns)
                        }
                    }
                    $next = $prevRow->nextSibling;
                    if ($next !== null) {
                        $sheetData->insertBefore($rowNode, $next);
                    } else {
                        $sheetData->appendChild($rowNode);
                    }
                } else {
                    $sheetData->appendChild($rowNode);
                }
            }

            $cellNode = $xpath->query("*[local-name()='c'][@r='{$cellRef}']", $rowNode)->item(0);
            if (! $cellNode instanceof \DOMElement) {
                $cellNode = $dom->createElementNS($ns, 'c');
                $cellNode->setAttribute('r', $cellRef);
                $rowNode->appendChild($cellNode);
            }

            while ($cellNode->firstChild) {
                $cellNode->removeChild($cellNode->firstChild);
            }

            $cellNode->setAttribute('t', 'inlineStr');
            if ($rowNumber >= $educationStartRow && $rowNumber <= $educationEndRow) {
                // So row-level style and thickBot do not override cell borders on the last education row, clear them.
                if ($fullBoxStyleForLastRow !== null && $rowNumber === $educationEndRow) {
                    if ($rowNode->hasAttribute('s')) {
                        $rowNode->removeAttribute('s');
                    }
                    if ($rowNode->hasAttribute('thickBot')) {
                        $rowNode->removeAttribute('thickBot');
                    }
                }
                $styleToApply = null;
                $lastRowDefGhiCols = ['D', 'E', 'F', 'G', 'H', 'I'];
                if ($fullBoxStyleForLastRow !== null && $rowNumber === $educationEndRow && preg_match('/^([A-Z]+)\d+$/', $cellRef, $cm) && in_array($cm[1], $lastRowDefGhiCols, true)) {
                    $styleToApply = $fullBoxStyleForLastRow;
                }
                if ($styleToApply === null && $educationStyleMap !== null && preg_match('/^([A-Z]+)\d+$/', $cellRef, $m)) {
                    $col = $m[1];
                    $styleCol = isset($educationStyleMap[$col]) ? $col : (in_array($col, ['G', 'I'], true) && isset($educationStyleMap['G']) ? 'G' : null);
                    if ($styleCol !== null) {
                        $isLastRow = $rowNumber === $educationEndRow;
                        $styleToApply = $educationStyleMap[$styleCol][$isLastRow ? 1 : 0];
                    }
                }
                if ($styleToApply === null && $educationStyleIndex !== null) {
                    $styleToApply = $educationStyleIndex;
                }
                if ($styleToApply !== null) {
                    $cellNode->setAttribute('s', (string) $styleToApply);
                }
            }
            $is = $dom->createElementNS($ns, 'is');
            $t = $dom->createElementNS($ns, 't');
            $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            $cellText = $this->sanitizeForXml((string) $value);
            $t->appendChild($dom->createTextNode($cellText !== '' ? $cellText : ' '));
            $is->appendChild($t);
            $cellNode->appendChild($is);
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    /**
     * @param  array<string, array{start: int, end: int}>  $mergeGroups
     */
    private function setWorksheetEducationMergeCells(string $worksheetXml, array $mergeGroups): string
    {
        $ranges = [];
        foreach ($mergeGroups as $range) {
            if ($range['end'] > $range['start']) {
                $ranges[] = 'B'.$range['start'].':C'.$range['end'];
            }
        }
        if ($ranges === []) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $worksheet = $xpath->query("//*[local-name()='worksheet']")->item(0);
        if (! $worksheet instanceof \DOMElement) {
            return $worksheetXml;
        }
        $docNs = $worksheet->namespaceURI ?: $ns;

        $mergeCells = $xpath->query(".//*[local-name()='mergeCells']", $worksheet)->item(0);
        if (! $mergeCells instanceof \DOMElement) {
            $mergeCells = $dom->createElementNS($docNs, 'mergeCells');
            $mergeCells->setAttribute('count', (string) count($ranges));
            $sheetData = $xpath->query(".//*[local-name()='sheetData']", $worksheet)->item(0);
            if ($sheetData instanceof \DOMElement && $sheetData->nextSibling !== null) {
                $worksheet->insertBefore($mergeCells, $sheetData->nextSibling);
            } else {
                $worksheet->appendChild($mergeCells);
            }
        }

        $existingRefs = $this->deduplicateMergeCells($xpath, $mergeCells);
        foreach ($ranges as $ref) {
            if (isset($existingRefs[$ref])) {
                continue;
            }
            $mergeCell = $dom->createElementNS($docNs, 'mergeCell');
            $mergeCell->setAttribute('ref', $ref);
            $mergeCells->appendChild($mergeCell);
            $existingRefs[$ref] = true;
        }

        $mergeCellCount = $xpath->query("*[local-name()='mergeCell']", $mergeCells)->length;
        $mergeCells->setAttribute('count', (string) $mergeCellCount);

        return $dom->saveXML() ?: $worksheetXml;
    }

    private function setWorksheetEducationColumnDEFMerge(
        string $worksheetXml,
        int $educationStartRow,
        int $educationEndRow
    ): string {
        $ranges = [];
        for ($row = $educationStartRow; $row <= $educationEndRow; $row++) {
            $ranges[] = 'D'.$row.':F'.$row;
        }
        if ($ranges === []) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $worksheet = $xpath->query("//*[local-name()='worksheet']")->item(0);
        if (! $worksheet instanceof \DOMElement) {
            return $worksheetXml;
        }
        $docNs = $worksheet->namespaceURI ?: $ns;

        $mergeCells = $xpath->query(".//*[local-name()='mergeCells']", $worksheet)->item(0);
        if (! $mergeCells instanceof \DOMElement) {
            $mergeCells = $dom->createElementNS($docNs, 'mergeCells');
            $mergeCells->setAttribute('count', (string) count($ranges));
            $sheetData = $xpath->query(".//*[local-name()='sheetData']", $worksheet)->item(0);
            if ($sheetData instanceof \DOMElement && $sheetData->nextSibling !== null) {
                $worksheet->insertBefore($mergeCells, $sheetData->nextSibling);
            } else {
                $worksheet->appendChild($mergeCells);
            }
        }

        $existingRefs = $this->deduplicateMergeCells($xpath, $mergeCells);
        foreach ($ranges as $ref) {
            if (isset($existingRefs[$ref])) {
                continue;
            }
            $mergeCell = $dom->createElementNS($docNs, 'mergeCell');
            $mergeCell->setAttribute('ref', $ref);
            $mergeCells->appendChild($mergeCell);
            $existingRefs[$ref] = true;
        }

        $mergeCellCount = $xpath->query("*[local-name()='mergeCell']", $mergeCells)->length;
        $mergeCells->setAttribute('count', (string) $mergeCellCount);

        return $dom->saveXML() ?: $worksheetXml;
    }

    /**
     * Remove mergeCell entries that overlap the given row/column range so separate cells (and borders) show.
     */
    private function removeMergeCellsOverlappingRange(
        string $worksheetXml,
        int $startRow,
        int $endRow,
        string $startCol,
        string $endCol
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $colToIndex = function (string $col): int {
            $n = 0;
            for ($i = 0; $i < strlen($col); $i++) {
                $n = 26 * $n + (ord($col[$i]) & 31) - 1;
            }

            return $n;
        };

        $startColIdx = $colToIndex($startCol);
        $endColIdx = $colToIndex($endCol);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $mergeCells = $xpath->query("//*[local-name()='mergeCells']")->item(0);
        if (! $mergeCells instanceof \DOMElement) {
            return $worksheetXml;
        }

        $toRemove = [];
        foreach ($xpath->query("*[local-name()='mergeCell']", $mergeCells) as $mergeCell) {
            if (! $mergeCell instanceof \DOMElement) {
                continue;
            }
            $ref = $mergeCell->getAttribute('ref');
            if ($ref === '') {
                continue;
            }
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $ref, $m)) {
                $c1 = $colToIndex($m[1]);
                $r1 = (int) $m[2];
                $c2 = $colToIndex($m[3]);
                $r2 = (int) $m[4];
            } elseif (preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                $c1 = $c2 = $colToIndex($m[1]);
                $r1 = $r2 = (int) $m[2];
            } else {
                continue;
            }
            $overlaps = ! ($c2 < $startColIdx || $c1 > $endColIdx || $r2 < $startRow || $r1 > $endRow);
            if ($overlaps) {
                $toRemove[] = $mergeCell;
            }
        }

        foreach ($toRemove as $node) {
            $mergeCells->removeChild($node);
        }
        if (count($toRemove) > 0) {
            $newCount = $xpath->query("*[local-name()='mergeCell']", $mergeCells)->length;
            $mergeCells->setAttribute('count', (string) $newCount);
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    private function setWorksheetEducationColumnGHIMerge(
        string $worksheetXml,
        int $educationStartRow,
        int $educationEndRow
    ): string {
        $ranges = [];
        for ($row = $educationStartRow; $row <= $educationEndRow; $row++) {
            $ranges[] = 'G'.$row.':I'.$row;
        }
        if ($ranges === []) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $worksheet = $xpath->query("//*[local-name()='worksheet']")->item(0);
        if (! $worksheet instanceof \DOMElement) {
            return $worksheetXml;
        }
        $docNs = $worksheet->namespaceURI ?: $ns;

        $mergeCells = $xpath->query(".//*[local-name()='mergeCells']", $worksheet)->item(0);
        if (! $mergeCells instanceof \DOMElement) {
            $mergeCells = $dom->createElementNS($docNs, 'mergeCells');
            $mergeCells->setAttribute('count', (string) count($ranges));
            $sheetData = $xpath->query(".//*[local-name()='sheetData']", $worksheet)->item(0);
            if ($sheetData instanceof \DOMElement && $sheetData->nextSibling !== null) {
                $worksheet->insertBefore($mergeCells, $sheetData->nextSibling);
            } else {
                $worksheet->appendChild($mergeCells);
            }
        }

        $existingRefs = $this->deduplicateMergeCells($xpath, $mergeCells);
        foreach ($ranges as $ref) {
            if (isset($existingRefs[$ref])) {
                continue;
            }
            $mergeCell = $dom->createElementNS($docNs, 'mergeCell');
            $mergeCell->setAttribute('ref', $ref);
            $mergeCells->appendChild($mergeCell);
            $existingRefs[$ref] = true;
        }

        $mergeCellCount = $xpath->query("*[local-name()='mergeCell']", $mergeCells)->length;
        $mergeCells->setAttribute('count', (string) $mergeCellCount);

        return $dom->saveXML() ?: $worksheetXml;
    }

    /**
     * @return array<string, bool>
     */
    private function deduplicateMergeCells(\DOMXPath $xpath, \DOMElement $mergeCells): array
    {
        $seen = [];
        $toRemove = [];
        foreach ($xpath->query("*[local-name()='mergeCell']", $mergeCells) as $mergeCell) {
            if (! $mergeCell instanceof \DOMElement) {
                continue;
            }
            $ref = $mergeCell->getAttribute('ref');
            if ($ref === '') {
                continue;
            }
            if (isset($seen[$ref])) {
                $toRemove[] = $mergeCell;

                continue;
            }
            $seen[$ref] = true;
        }
        foreach ($toRemove as $node) {
            $mergeCells->removeChild($node);
        }

        return $seen;
    }

    private function ensureEducationStyleWithVerticalCenter(string $stylesPath, int $educationStyleIndex): int
    {
        $xml = file_get_contents($stylesPath);
        if ($xml === false) {
            return $educationStyleIndex;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($xml)) {
            return $educationStyleIndex;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $cellXfs = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
        if (! $cellXfs instanceof \DOMElement) {
            return $educationStyleIndex;
        }

        $xfList = $xpath->query('s:xf', $cellXfs);
        $educationXf = $xfList->item($educationStyleIndex);
        if (! $educationXf instanceof \DOMElement) {
            return $educationStyleIndex;
        }

        $newXf = $educationXf->cloneNode(true);
        $alignment = $xpath->query('s:alignment', $newXf)->item(0);
        if ($alignment instanceof \DOMElement) {
            $alignment->setAttribute('vertical', 'center');
        } else {
            $alignment = $dom->createElementNS($ns, 'alignment');
            $alignment->setAttribute('vertical', 'center');
            $newXf->appendChild($alignment);
        }
        $newXf->setAttribute('applyAlignment', '1');
        $cellXfs->appendChild($newXf);
        $count = $xpath->query('s:xf', $cellXfs)->length;
        $cellXfs->setAttribute('count', (string) $count);
        file_put_contents($stylesPath, $dom->saveXML() ?: $xml);

        return $count - 1;
    }

    private function ensureVerticalCenterCellStyle(string $stylesPath): int
    {
        $xml = file_get_contents($stylesPath);
        if ($xml === false) {
            return 0;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($xml)) {
            return 0;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $cellXfs = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
        if (! $cellXfs instanceof \DOMElement) {
            return 0;
        }

        $xfIndex = 0;
        foreach ($xpath->query('s:xf', $cellXfs) as $xf) {
            if (! $xf instanceof \DOMElement) {
                continue;
            }
            $alignment = $xpath->query('s:alignment', $xf)->item(0);
            if ($alignment instanceof \DOMElement && $alignment->getAttribute('vertical') === 'center') {
                return $xfIndex;
            }
            $xfIndex++;
        }

        $firstXf = $xpath->query('s:xf', $cellXfs)->item(0);
        if ($firstXf instanceof \DOMElement) {
            $newXf = $firstXf->cloneNode(true);
            $alignment = $xpath->query('s:alignment', $newXf)->item(0);
            if ($alignment instanceof \DOMElement) {
                $alignment->setAttribute('vertical', 'center');
            } else {
                $alignment = $dom->createElementNS($ns, 'alignment');
                $alignment->setAttribute('vertical', 'center');
                $newXf->appendChild($alignment);
            }
            $newXf->setAttribute('applyAlignment', '1');
            $cellXfs->appendChild($newXf);
            $count = $xpath->query('s:xf', $cellXfs)->length;
            $cellXfs->setAttribute('count', (string) $count);
            file_put_contents($stylesPath, $dom->saveXML() ?: $xml);

            return $count - 1;
        }

        return 0;
    }

    /**
     * Ensure styles.xml has a cell format with a full box border. Uses thin left/top/right; bottom is taken from
     * sourceLastRowXfIndex when provided (so it matches the table's thick bottom), otherwise thin. Returns the xf index.
     */
    private function ensureFullBoxBorderStyle(string $stylesPath, ?int $sourceLastRowXfIndex = null): int
    {
        $xml = file_get_contents($stylesPath);
        if ($xml === false) {
            return 0;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($xml)) {
            return 0;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $borders = $xpath->query('/s:styleSheet/s:borders')->item(0);
        if (! $borders instanceof \DOMElement) {
            return 0;
        }

        $newBorder = $dom->createElementNS($ns, 'border');
        $thinSides = ['left', 'right', 'top'];
        foreach ($thinSides as $side) {
            $el = $dom->createElementNS($ns, $side);
            $el->setAttribute('style', 'thin');
            $newBorder->appendChild($el);
        }
        $bottomEl = $dom->createElementNS($ns, 'bottom');
        $bottomCopied = false;
        if ($sourceLastRowXfIndex !== null) {
            $cellXfsEl = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
            $sourceXf = $cellXfsEl instanceof \DOMElement ? $xpath->query('s:xf', $cellXfsEl)->item($sourceLastRowXfIndex) : null;
            if ($sourceXf instanceof \DOMElement) {
                $sourceBorderId = (int) $sourceXf->getAttribute('borderId');
                $sourceBorder = $xpath->query('s:border', $borders)->item($sourceBorderId);
                $sourceBottom = $sourceBorder instanceof \DOMElement ? $xpath->query('s:bottom', $sourceBorder)->item(0) : null;
                if ($sourceBottom instanceof \DOMElement) {
                    foreach ($sourceBottom->attributes as $attr) {
                        $bottomEl->setAttribute($attr->name, $attr->value);
                    }
                    $bottomCopied = true;
                }
            }
        }
        if (! $bottomCopied) {
            $bottomEl->setAttribute('style', 'thin');
        }
        $newBorder->appendChild($bottomEl);

        $borders->appendChild($newBorder);
        $newBorderIndex = $xpath->query('s:border', $borders)->length - 1;
        $borders->setAttribute('count', (string) ($newBorderIndex + 1));

        $cellXfs = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
        if (! $cellXfs instanceof \DOMElement) {
            return 0;
        }

        $firstXf = $xpath->query('s:xf', $cellXfs)->item(0);
        if (! $firstXf instanceof \DOMElement) {
            return 0;
        }

        $newXf = $firstXf->cloneNode(true);
        $newXf->setAttribute('borderId', (string) $newBorderIndex);
        $newXf->setAttribute('applyBorder', '1');
        $cellXfs->appendChild($newXf);
        $newXfCount = $xpath->query('s:xf', $cellXfs)->length;
        $cellXfs->setAttribute('count', (string) $newXfCount);
        file_put_contents($stylesPath, $dom->saveXML() ?: $xml);

        return $newXfCount - 1;
    }

    /**
     * Clone base xf but use a border whose "right" is copied from source border's "left" (so D:E:F right edge = F–G line, not G–H).
     * Returns the new cellXf index.
     */
    private function cloneXfWithBorderRightFromSourceLeft(string $stylesPath, int $baseXfIndex, int $sourceXfIndex): int
    {
        $xml = file_get_contents($stylesPath);
        if ($xml === false) {
            return $baseXfIndex;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($xml)) {
            return $baseXfIndex;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $xfList = $xpath->query('/s:styleSheet/s:cellXfs/s:xf', $dom);
        $baseXf = $xfList->item($baseXfIndex);
        $sourceXf = $xfList->item($sourceXfIndex);
        if (! $baseXf instanceof \DOMElement || ! $sourceXf instanceof \DOMElement) {
            return $baseXfIndex;
        }

        $sourceBorderId = (int) ($sourceXf->getAttribute('borderId') ?? 0);
        $borders = $xpath->query('/s:styleSheet/s:borders')->item(0);
        if (! $borders instanceof \DOMElement) {
            return $baseXfIndex;
        }

        $borderList = $xpath->query('s:border', $borders);
        $sourceBorder = $borderList->item($sourceBorderId);
        if (! $sourceBorder instanceof \DOMElement) {
            return $baseXfIndex;
        }

        $leftNode = $xpath->query('s:left', $sourceBorder)->item(0);
        if (! $leftNode instanceof \DOMElement) {
            return $this->cloneXfWithBorderFrom($stylesPath, $baseXfIndex, $sourceXfIndex);
        }

        $newBorder = $sourceBorder->cloneNode(true);
        foreach ($xpath->query('s:right', $newBorder) as $existingRight) {
            $newBorder->removeChild($existingRight);
        }
        $rightEl = $dom->createElementNS($ns, 'right');
        foreach ($leftNode->attributes as $attr) {
            $rightEl->setAttribute($attr->name, $attr->value);
        }
        $newBorder->appendChild($rightEl);
        $borders->appendChild($newBorder);
        $newBorderIndex = $xpath->query('s:border', $borders)->length - 1;
        $borders->setAttribute('count', (string) ($newBorderIndex + 1));

        $cellXfs = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
        if (! $cellXfs instanceof \DOMElement) {
            return $baseXfIndex;
        }
        $newXf = $baseXf->cloneNode(true);
        $newXf->setAttribute('borderId', (string) $newBorderIndex);
        $newXf->setAttribute('applyBorder', '1');
        $cellXfs->appendChild($newXf);
        $newXfCount = $xpath->query('s:xf', $cellXfs)->length;
        $cellXfs->setAttribute('count', (string) $newXfCount);
        file_put_contents($stylesPath, $dom->saveXML() ?: $xml);

        return $newXfCount - 1;
    }

    /**
     * Clone the cell format at baseXfIndex but use the border from sourceXfIndex (for G:H:I right border = J's left).
     * Returns the new cellXf index.
     */
    private function cloneXfWithBorderFrom(string $stylesPath, int $baseXfIndex, int $sourceXfIndex): int
    {
        $xml = file_get_contents($stylesPath);
        if ($xml === false) {
            return $baseXfIndex;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($xml)) {
            return $baseXfIndex;
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        $cellXfs = $xpath->query('/s:styleSheet/s:cellXfs')->item(0);
        if (! $cellXfs instanceof \DOMElement) {
            return $baseXfIndex;
        }

        $xfList = $xpath->query('s:xf', $cellXfs);
        $baseXf = $xfList->item($baseXfIndex);
        $sourceXf = $xfList->item($sourceXfIndex);
        if (! $baseXf instanceof \DOMElement || ! $sourceXf instanceof \DOMElement) {
            return $baseXfIndex;
        }

        $borderId = $sourceXf->getAttribute('borderId');
        $applyBorder = $sourceXf->getAttribute('applyBorder');

        $newXf = $baseXf->cloneNode(true);
        if ($borderId !== '') {
            $newXf->setAttribute('borderId', $borderId);
        }
        if ($applyBorder !== '') {
            $newXf->setAttribute('applyBorder', $applyBorder);
        } else {
            $newXf->setAttribute('applyBorder', '1');
        }
        $cellXfs->appendChild($newXf);
        $newXfCount = $xpath->query('s:xf', $cellXfs)->length;
        $cellXfs->setAttribute('count', (string) $newXfCount);
        file_put_contents($stylesPath, $dom->saveXML() ?: $xml);

        return $newXfCount - 1;
    }

    /**
     * @param  array<string, array{start: int, end: int}>  $mergeGroups
     */
    private function setMergedLevelCellVerticalAlignment(
        string $worksheetXml,
        array $mergeGroups,
        int $styleIndex
    ): string {
        $cellRefs = [];
        foreach ($mergeGroups as $range) {
            if (($range['end'] ?? $range['start']) > $range['start']) {
                $cellRefs[] = 'B'.$range['start'];
            }
        }
        if ($cellRefs === []) {
            return $worksheetXml;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($worksheetXml);

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('s', $ns);

        foreach ($cellRefs as $cellRef) {
            $rowNumber = (int) preg_replace('/^[A-Z]+/', '', $cellRef);
            $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
            $rowNode = $sheetData instanceof \DOMElement
                ? $xpath->query(".//*[local-name()='row'][@r='{$rowNumber}']", $sheetData)->item(0)
                : null;
            if (! $rowNode instanceof \DOMElement) {
                continue;
            }
            $cellNode = $xpath->query("*[local-name()='c'][@r='{$cellRef}']", $rowNode)->item(0);
            if ($cellNode instanceof \DOMElement) {
                $cellNode->setAttribute('s', (string) $styleIndex);
            }
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

            if ($checkStates[$controlName]) {
                $controlPr->setAttribute('checked', 'Checked');
            } elseif ($controlPr->hasAttribute('checked')) {
                $controlPr->removeAttribute('checked');
            }
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

            if ($shouldCheck) {
                $formControl->setAttribute('checked', 'Checked');
            } elseif ($formControl->hasAttribute('checked')) {
                $formControl->removeAttribute('checked');
            }
            $ctrlOut = $ctrlPropDom->saveXML() ?: $ctrlPropXml;
            file_put_contents($ctrlPropPath, $this->ensureXmlDeclarationHasEncoding($ctrlOut));
        }
    }

    private function setCountryDropDownSelection(
        string $worksheetRelsXml,
        string $relsDirectory,
        string $worksheetXml,
        string $country
    ): void {
        $country = trim($country);
        if ($country === '') {
            return;
        }

        $rels = @simplexml_load_string($worksheetRelsXml);
        if (! $rels) {
            return;
        }
        $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $ctrlPropPathsByRid = [];
        $ctrlPropRelations = $rels->xpath("//pr:Relationship[contains(@Type, '/ctrlProp')]");
        if ($ctrlPropRelations) {
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
        }
        if ($ctrlPropPathsByRid === []) {
            return;
        }

        $sheetDom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$sheetDom->loadXML($worksheetXml)) {
            return;
        }
        $sheetXPath = new \DOMXPath($sheetDom);
        $dropControl = $sheetXPath->query('//*[local-name()="control"][@name="Drop Down 31"]')->item(0);
        if (! $dropControl instanceof \DOMElement) {
            return;
        }

        $rid = $dropControl->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
        if ($rid === '') {
            $rid = $dropControl->getAttribute('r:id');
        }
        if ($rid === '') {
            return;
        }

        $ctrlPropPath = $ctrlPropPathsByRid[$rid] ?? null;
        if (! $ctrlPropPath || ! is_file($ctrlPropPath)) {
            return;
        }

        $ctrlPropXml = file_get_contents($ctrlPropPath);
        if (! is_string($ctrlPropXml) || trim($ctrlPropXml) === '') {
            return;
        }

        $ctrlPropDom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$ctrlPropDom->loadXML($ctrlPropXml)) {
            return;
        }
        $ctrlPropXPath = new \DOMXPath($ctrlPropDom);
        $formControl = $ctrlPropXPath->query('//*[local-name()="formControlPr"]')->item(0);
        if (! $formControl instanceof \DOMElement) {
            return;
        }

        $fmlaRange = (string) $formControl->getAttribute('fmlaRange');
        if ($fmlaRange === '') {
            return;
        }

        $optionIndex = $this->findCountryDropDownOptionIndex($worksheetXml, $relsDirectory, $fmlaRange, $country);
        if ($optionIndex === null) {
            return;
        }

        $formControl->setAttribute('sel', (string) ($optionIndex + 1));
        $formControl->setAttribute('val', (string) $optionIndex);

        $ctrlOut = $ctrlPropDom->saveXML() ?: $ctrlPropXml;
        file_put_contents($ctrlPropPath, $this->ensureXmlDeclarationHasEncoding($ctrlOut));
    }

    private function findCountryDropDownOptionIndex(
        string $worksheetXml,
        string $relsDirectory,
        string $fmlaRange,
        string $country
    ): ?int {
        if (! preg_match("/^(?:'[^']+'!|[^!]+!)?\\$?([A-Z]+)\\$?(\\d+):\\$?([A-Z]+)\\$?(\\d+)$/i", $fmlaRange, $match)) {
            return null;
        }

        $column = strtoupper($match[1]);
        $startRow = (int) $match[2];
        $endRow = (int) $match[4];
        if ($startRow <= 0 || $endRow < $startRow) {
            return null;
        }

        $sheetDom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$sheetDom->loadXML($worksheetXml)) {
            return null;
        }
        $sheetXPath = new \DOMXPath($sheetDom);
        $sheetData = $sheetXPath->query("//*[local-name()='sheetData']")->item(0);
        if (! $sheetData instanceof \DOMElement) {
            return null;
        }

        $sharedStringsPath = realpath($relsDirectory.'/../sharedStrings.xml') ?: $relsDirectory.'/../sharedStrings.xml';
        $sharedStrings = [];
        if (is_file($sharedStringsPath)) {
            $sharedDom = new \DOMDocument('1.0', 'UTF-8');
            if (@$sharedDom->load($sharedStringsPath)) {
                $sharedXPath = new \DOMXPath($sharedDom);
                foreach ($sharedXPath->query("//*[local-name()='si']") as $si) {
                    $sharedStrings[] = trim((string) $si->textContent);
                }
            }
        }

        $target = strtolower(trim($country));
        $index = 0;
        for ($row = $startRow; $row <= $endRow; $row++) {
            $cellRef = $column.$row;
            $cellNode = $sheetXPath->query(".//*[local-name()='row'][@r='{$row}']/*[local-name()='c'][@r='{$cellRef}']", $sheetData)->item(0);
            $value = '';
            if ($cellNode instanceof \DOMElement) {
                $type = $cellNode->getAttribute('t');
                if ($type === 's') {
                    $vNode = $sheetXPath->query("*[local-name()='v']", $cellNode)->item(0);
                    $stringIndex = $vNode ? (int) trim((string) $vNode->textContent) : -1;
                    if ($stringIndex >= 0 && isset($sharedStrings[$stringIndex])) {
                        $value = $sharedStrings[$stringIndex];
                    }
                } elseif ($type === 'inlineStr') {
                    $value = trim((string) $sheetXPath->query("string(*[local-name()='is'])", $cellNode));
                } else {
                    $vNode = $sheetXPath->query("*[local-name()='v']", $cellNode)->item(0);
                    $value = $vNode ? trim((string) $vNode->textContent) : '';
                }
            }

            if (strtolower(trim($value)) === $target) {
                return $index;
            }
            $index++;
        }

        return null;
    }

    /**
     * @return array<string, bool>
     */
    private function buildControlCheckStates(
        string $gender,
        string $civilStatus,
        string $citizenship,
        string $dualCitizenship
    ): array {
        $checkStates = [
            'Check Box 25' => false,
            'Check Box 26' => false,
            'Check Box 34' => false,
            'Check Box 35' => false,
            'Check Box 36' => false,
            'Check Box 38' => false,
            'Check Box 37' => false,
            'Check Box 21' => false,
            'Check Box 22' => false,
            'Check Box 39' => false,
            'Check Box 40' => false,
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

        if ($this->isFilipinoCitizenship($citizenship)) {
            $checkStates['Check Box 21'] = true;
            $checkStates['Check Box 39'] = true;
        }

        $normalizedDual = strtolower(trim($dualCitizenship));
        if ($normalizedDual !== '' && $normalizedDual !== 'n/a') {
            $checkStates['Check Box 22'] = true;
        }
        $dualMode = $this->normalizeDualCitizenshipMode($dualCitizenship);
        if ($dualMode === 'by birth') {
            $checkStates['Check Box 39'] = true;
        } elseif ($dualMode === 'by naturalization') {
            $checkStates['Check Box 40'] = true;
        }

        return $checkStates;
    }

    /**
     * @return array<int, string>
     */
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
    ): string {
        $selected = [];
        $genderKey = $this->normalizeGenderToCheckbox($gender);
        if ($genderKey !== null) {
            $selected[$genderKey] = true;
        }
        $civilKey = $this->normalizeCivilStatusToCheckbox($civilStatus);
        if ($civilKey !== null) {
            $selected[$civilKey] = true;
        }
        if ($this->isFilipinoCitizenship($citizenship)) {
            $selected['filipino'] = true;
            $selected['by birth'] = true;
        }
        $normalizedDual = strtolower(trim($dualCitizenship));
        if ($normalizedDual !== '' && $normalizedDual !== 'n/a') {
            $selected['dual citizenship'] = true;
        }
        $dualMode = $this->normalizeDualCitizenshipMode($dualCitizenship);
        if ($dualMode === 'by birth') {
            $selected['by birth'] = true;
        } elseif ($dualMode === 'by naturalization') {
            $selected['by naturalization'] = true;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($vmlXml)) {
            return $vmlXml;
        }

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

        $out = $dom->saveXML() ?: $vmlXml;

        return $this->ensureXmlDeclarationHasEncoding($out);
    }

    /**
     * Ensure XML declaration includes encoding="UTF-8" so Excel 2016 opens the part without "problem with some content".
     */
    private function ensureXmlDeclarationHasEncoding(string $xml): string
    {
        if (str_starts_with($xml, '<?xml ') && ! str_contains(substr($xml, 0, 80), 'encoding=')) {
            $xml = preg_replace('/^<\?xml\s+version="1\.0"\s*\?>/', '<?xml version="1.0" encoding="UTF-8"?>', $xml, 1);
        }

        return $xml;
    }

    private function detectCheckboxLabelKey(string $label): ?string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? ''));

        $checks = [
            'dual citizenship' => 'dual citizenship',
            'by naturalization' => 'by naturalization',
            'by birth' => 'by birth',
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

        if (in_array($normalized, ['f', 'female'], true) || str_contains($normalized, 'female')) {
            return 'female';
        }
        if (in_array($normalized, ['m', 'male'], true) || str_contains($normalized, 'male')) {
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
        if (str_contains($normalized, 'annulled') || str_contains($normalized, 'divorc')) {
            return 'other/s';
        }
        if (str_contains($normalized, 'other')) {
            return 'other/s';
        }

        return null;
    }

    private function isFilipinoCitizenship(string $citizenship): bool
    {
        $normalized = strtolower(trim($citizenship));
        if ($normalized === '' || $normalized === 'n/a') {
            return false;
        }

        return str_contains($normalized, 'filipino')
            || str_contains($normalized, 'philippine')
            || str_contains($normalized, 'philippines')
            || $normalized === 'ph'
            || $normalized === 'phl';
    }

    private function normalizeDualCitizenshipMode(string $dualCitizenship): ?string
    {
        $normalized = strtolower(trim($dualCitizenship));
        if ($normalized === '' || $normalized === 'n/a') {
            return null;
        }
        if (str_contains($normalized, 'birth')) {
            return 'by birth';
        }
        if (str_contains($normalized, 'natural')) {
            return 'by naturalization';
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
