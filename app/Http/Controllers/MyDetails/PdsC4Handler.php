<?php

namespace App\Http\Controllers\MyDetails;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Handles C4 sheet mapping (questions 34-40) and checkbox states.
 */
class PdsC4Handler
{
    /**
     * Build C4 detail cell map.
     *
     * @param  callable(mixed): string  $formatDate
     * @param  callable(mixed): string  $pdsValue
     * @return array<string, string>
     */
    public function buildCellMap(mixed $personalInfo, callable $formatDate, callable $pdsValue): array
    {
        $map = [];

        $question34Details = $this->firstValue($personalInfo, [
            'q34_details',
            'q34_detail',
            'question_34_details',
            'question34_details',
            'related_details',
            'relation_details',
            'third_fourth_degree_details',
            'q34a_details',
            'q34b_details',
        ]);
        if (! $this->hasValue($question34Details)) {
            $question34Details = $this->joinValues([
                $this->firstValue($personalInfo, ['q34a_details', 'q34a_detail', 'third_degree_details']),
                $this->firstValue($personalInfo, ['q34b_details', 'q34b_detail', 'fourth_degree_details']),
            ]);
        }
        $this->mapIfPresent($map, 'G10', $question34Details, $pdsValue);

        $this->mapIfPresent($map, 'G14', $this->firstValue($personalInfo, [
            'q35a_details',
            'q35a_detail',
            'administrative_offense_details',
            'administrative_case_details',
            'administrative_details',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G19', $this->firstValue($personalInfo, [
            'q35b_details',
            'q35b_detail',
            'criminally_charged_details',
            'criminal_charge_details',
            'criminal_case_details',
        ]), $pdsValue);
        $this->mapIfPresent($map, 'H20', $this->formatDateOrRaw(
            $this->firstValue($personalInfo, [
                'q35b_date_filed',
                'date_filed',
                'criminal_case_date_filed',
                'criminally_charged_date_filed',
            ]),
            $formatDate
        ), $pdsValue);
        $this->mapIfPresent($map, 'G21', $this->firstValue($personalInfo, [
            'q35b_status',
            'status_of_case',
            'status_of_cases',
            'criminal_case_status',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G24', $this->firstValue($personalInfo, [
            'q36_details',
            'q36_detail',
            'convicted_details',
            'conviction_details',
            'crime_violation_details',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G28', $this->firstValue($personalInfo, [
            'q37_details',
            'q37_detail',
            'separated_from_service_details',
            'service_separation_details',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G32', $this->firstValue($personalInfo, [
            'q38a_details',
            'q38a_detail',
            'candidate_election_details',
            'candidate_last_election_details',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G35', $this->firstValue($personalInfo, [
            'q38b_details',
            'q38b_detail',
            'resigned_before_election_details',
            'resigned_to_campaign_details',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G38', $this->firstValue($personalInfo, [
            'q39_details',
            'q39_detail',
            'immigrant_details',
            'immigrant_country',
            'permanent_resident_country',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G44', $this->firstValue($personalInfo, [
            'q40a_details',
            'q40a_detail',
            'indigenous_group_details',
            'indigenous_group_name',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G46', $this->firstValue($personalInfo, [
            'q40b_details',
            'q40b_detail',
            'pwd_id_no',
            'pwd_id_number',
            'disability_id_no',
        ]), $pdsValue);

        $this->mapIfPresent($map, 'G48', $this->firstValue($personalInfo, [
            'q40c_details',
            'q40c_detail',
            'solo_parent_id_no',
            'solo_parent_id_number',
        ]), $pdsValue);

        return $map;
    }

    /**
     * Build C4 YES/NO checkbox states.
     * Defaults to NO for each item when no data is available.
     *
     * @return array<string, bool>
     */
    public function buildControlCheckStates(mixed $personalInfo): array
    {
        $states = [
            'Check Box 1' => false,
            'Check Box 2' => true,
            'Check Box 3' => false,
            'Check Box 4' => true,
            'Check Box 5' => false,
            'Check Box 6' => true,
            'Check Box 7' => false,
            'Check Box 8' => true,
            'Check Box 9' => false,
            'Check Box 10' => true,
            'Check Box 11' => false,
            'Check Box 12' => true,
            'Check Box 13' => false,
            'Check Box 14' => true,
            'Check Box 15' => false,
            'Check Box 18' => true,
            'Check Box 16' => false,
            'Check Box 19' => true,
            'Check Box 17' => false,
            'Check Box 20' => true,
            'Check Box 26' => false,
            'Check Box 27' => true,
            'Check Box 28' => false,
            'Check Box 29' => true,
        ];

        $q34aYes = $this->resolveYesNo(
            $personalInfo,
            ['q34a', 'q34_a', 'question34a', 'question_34_a', 'within_third_degree', 'related_within_third_degree', 'third_degree_related'],
            ['q34a_no', 'q34_a_no', 'question34a_no', 'within_third_degree_no', 'not_related_within_third_degree'],
            [$this->firstValue($personalInfo, ['q34_details', 'q34a_details', 'third_degree_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 1', 'Check Box 2', $q34aYes);

        $q34bYes = $this->resolveYesNo(
            $personalInfo,
            ['q34b', 'q34_b', 'question34b', 'question_34_b', 'within_fourth_degree', 'related_within_fourth_degree', 'fourth_degree_related'],
            ['q34b_no', 'q34_b_no', 'question34b_no', 'within_fourth_degree_no', 'not_related_within_fourth_degree'],
            [$this->firstValue($personalInfo, ['q34_details', 'q34b_details', 'fourth_degree_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 3', 'Check Box 4', $q34bYes);

        $q35aYes = $this->resolveYesNo(
            $personalInfo,
            ['q35a', 'q35_a', 'question35a', 'question_35_a', 'administrative_offense', 'found_guilty_administrative_offense'],
            ['q35a_no', 'q35_a_no', 'question35a_no', 'administrative_offense_no', 'not_guilty_administrative_offense'],
            [$this->firstValue($personalInfo, ['q35a_details', 'administrative_offense_details', 'administrative_case_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 5', 'Check Box 6', $q35aYes);

        $q35bYes = $this->resolveYesNo(
            $personalInfo,
            ['q35b', 'q35_b', 'question35b', 'question_35_b', 'criminally_charged', 'criminal_case', 'has_criminal_case'],
            ['q35b_no', 'q35_b_no', 'question35b_no', 'criminally_charged_no', 'criminal_case_no'],
            [
                $this->firstValue($personalInfo, ['q35b_details', 'criminal_charge_details', 'criminal_case_details']),
                $this->firstValue($personalInfo, ['q35b_date_filed', 'date_filed', 'criminal_case_date_filed']),
                $this->firstValue($personalInfo, ['q35b_status', 'status_of_case', 'criminal_case_status']),
            ]
        );
        $this->applyBinaryState($states, 'Check Box 7', 'Check Box 8', $q35bYes);

        $q36Yes = $this->resolveYesNo(
            $personalInfo,
            ['q36', 'question36', 'convicted', 'convicted_crime', 'convicted_of_crime'],
            ['q36_no', 'question36_no', 'convicted_no', 'not_convicted'],
            [$this->firstValue($personalInfo, ['q36_details', 'convicted_details', 'conviction_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 9', 'Check Box 10', $q36Yes);

        $q37Yes = $this->resolveYesNo(
            $personalInfo,
            ['q37', 'question37', 'separated_from_service', 'service_separation'],
            ['q37_no', 'question37_no', 'separated_from_service_no'],
            [$this->firstValue($personalInfo, ['q37_details', 'separated_from_service_details', 'service_separation_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 11', 'Check Box 12', $q37Yes);

        $q38aYes = $this->resolveYesNo(
            $personalInfo,
            ['q38a', 'q38_a', 'question38a', 'question_38_a', 'candidate_last_election', 'candidate_in_election'],
            ['q38a_no', 'q38_a_no', 'question38a_no', 'candidate_last_election_no'],
            [$this->firstValue($personalInfo, ['q38a_details', 'candidate_election_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 26', 'Check Box 27', $q38aYes);

        $q38bYes = $this->resolveYesNo(
            $personalInfo,
            ['q38b', 'q38_b', 'question38b', 'question_38_b', 'resigned_before_election', 'resigned_to_campaign'],
            ['q38b_no', 'q38_b_no', 'question38b_no', 'resigned_before_election_no'],
            [$this->firstValue($personalInfo, ['q38b_details', 'resigned_before_election_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 28', 'Check Box 29', $q38bYes);

        $q39Yes = $this->resolveYesNo(
            $personalInfo,
            ['q39', 'question39', 'immigrant', 'immigrant_status', 'permanent_resident'],
            ['q39_no', 'question39_no', 'immigrant_no', 'not_immigrant'],
            [$this->firstValue($personalInfo, ['q39_details', 'immigrant_country', 'permanent_resident_country'])]
        );
        $this->applyBinaryState($states, 'Check Box 13', 'Check Box 14', $q39Yes);

        $q40aYes = $this->resolveYesNo(
            $personalInfo,
            ['q40a', 'q40_a', 'question40a', 'question_40_a', 'indigenous_group_member', 'is_indigenous_member'],
            ['q40a_no', 'q40_a_no', 'question40a_no', 'indigenous_group_member_no'],
            [$this->firstValue($personalInfo, ['q40a_details', 'indigenous_group_name', 'indigenous_group_details'])]
        );
        $this->applyBinaryState($states, 'Check Box 15', 'Check Box 18', $q40aYes);

        $q40bYes = $this->resolveYesNo(
            $personalInfo,
            ['q40b', 'q40_b', 'question40b', 'question_40_b', 'pwd', 'person_with_disability', 'with_disability'],
            ['q40b_no', 'q40_b_no', 'question40b_no', 'pwd_no', 'person_with_disability_no'],
            [$this->firstValue($personalInfo, ['q40b_details', 'pwd_id_no', 'pwd_id_number', 'disability_id_no'])]
        );
        $this->applyBinaryState($states, 'Check Box 16', 'Check Box 19', $q40bYes);

        $q40cYes = $this->resolveYesNo(
            $personalInfo,
            ['q40c', 'q40_c', 'question40c', 'question_40_c', 'solo_parent', 'is_solo_parent'],
            ['q40c_no', 'q40_c_no', 'question40c_no', 'solo_parent_no'],
            [$this->firstValue($personalInfo, ['q40c_details', 'solo_parent_id_no', 'solo_parent_id_number'])]
        );
        $this->applyBinaryState($states, 'Check Box 17', 'Check Box 20', $q40cYes);

        return $states;
    }

    public function resolvePdsPhotoPath(mixed $hrid, ?object $dbProfile, ?object $officialInfo): ?string
    {
        $photoPath = $this->resolveStoredAssetPath($dbProfile?->avatar ?? null);
        if ($photoPath === null && $officialInfo && isset($officialInfo->avatar)) {
            $photoPath = $this->resolveStoredAssetPath($officialInfo->avatar);
        }

        if ($photoPath === null && $hrid !== null && $hrid !== '') {
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $candidate = public_path('uploads/'.$hrid.'/'.$hrid.'.'.$ext);
                if (is_file($candidate)) {
                    $photoPath = $candidate;
                    break;
                }
            }
        }

        return $photoPath;
    }

    public function resolvePdsSignaturePath(mixed $hrid, ?string $email): ?string
    {
        $fileKey = (string) ($hrid ?? '');
        if ($fileKey !== '') {
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $candidate = public_path('asset/uploads/print_id/sign/'.$fileKey.'.'.$ext);
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        if (! Schema::hasTable('tbl_printingid_depaide')) {
            return null;
        }
        if (($hrid === null || $hrid === '') && ($email === null || $email === '')) {
            return null;
        }

        $row = DB::table('tbl_printingid_depaide')
            ->where(function ($q) use ($hrid, $email) {
                $hasAny = false;
                if ($hrid !== null && $hrid !== '') {
                    $q->where('hr_id', (string) $hrid);
                    $hasAny = true;
                }
                if ($email !== null && $email !== '') {
                    if ($hasAny) {
                        $q->orWhere('email', $email);
                    } else {
                        $q->where('email', $email);
                    }
                }
            })
            ->orderByDesc('id')
            ->first();

        $sign = isset($row?->sign) ? trim((string) $row->sign) : '';
        if ($sign === '') {
            return null;
        }

        return $this->resolveStoredAssetPath($sign) ?? (is_file($sign) ? $sign : null);
    }

    public function insertC4Images(
        string $extractRoot,
        string $workbookXml,
        string $relsXml,
        ?string $passportPhotoPath,
        ?string $signaturePath,
        string $tempRoot
    ): void {
        $sheet4EntryPath = $this->resolveWorksheetEntryPath($workbookXml, $relsXml, 'C4');
        if ($sheet4EntryPath === null) {
            return;
        }

        $sheet4Path = $extractRoot.'/'.$sheet4EntryPath;
        $sheet4RelsPath = dirname($sheet4Path).'/_rels/'.basename($sheet4Path).'.rels';
        if (! is_file($sheet4Path) || ! is_file($sheet4RelsPath)) {
            return;
        }

        $drawingPath = $this->resolveDrawingPathFromWorksheetRels($sheet4RelsPath, dirname($sheet4Path));
        if ($drawingPath === null || ! is_file($drawingPath)) {
            return;
        }

        $drawingRelsPath = dirname($drawingPath).'/_rels/'.basename($drawingPath).'.rels';
        if (! is_dir(dirname($drawingRelsPath))) {
            @mkdir(dirname($drawingRelsPath), 0777, true);
        }
        if (! is_file($drawingRelsPath)) {
            file_put_contents($drawingRelsPath, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
        }

        $mediaDir = $extractRoot.'/xl/media';
        if (! is_dir($mediaDir)) {
            @mkdir($mediaDir, 0777, true);
        }

        $drawingXml = file_get_contents($drawingPath) ?: '';
        $drawingRelsXml = file_get_contents($drawingRelsPath) ?: '';
        $contentTypesPath = $extractRoot.'/[Content_Types].xml';
        $contentTypesXml = is_file($contentTypesPath) ? (file_get_contents($contentTypesPath) ?: '') : '';

        $hasPhoto = $passportPhotoPath && is_file($passportPhotoPath);
        $hasSignature = $signaturePath && is_file($signaturePath);
        $photoAnchor = $this->findAnchorForShapeName($drawingXml, 'Text Box 100');

        // When eSignature is included, clear signature-related instruction text
        // (both worksheet cells and drawing text) to avoid overlap.
        if ($hasSignature) {
            $sheet4Xml = file_get_contents($sheet4Path) ?: '';
            if ($sheet4Xml !== '') {
                $sharedStringsPath = $extractRoot.'/xl/sharedStrings.xml';
                $sharedStrings = is_file($sharedStringsPath) ? $this->loadSharedStrings($sharedStringsPath) : [];
                $sheet4Xml = $this->removeInstructionTextFromWorksheet($sheet4Xml, $sharedStrings);
                file_put_contents($sheet4Path, $sheet4Xml);
            }

            $drawingXml = $this->removeSignatureInstructionTextFromDrawing($drawingXml);
        }

        // If both toggles are off, strip all pictures from this sheet's drawing.
        if (! $hasPhoto && ! $hasSignature) {
            $drawingXml = $this->removeAllPicturesFromDrawing($drawingXml);
        }

        // Remove template/old photo images in the photo box region.
        // Only remove the placeholder text box when we are inserting an actual photo.
        if ($hasPhoto) {
            $drawingXml = $this->removeDrawingTextBoxByName($drawingXml, 'Text Box 100');
        }
        $drawingXml = $this->removeDrawingPictureByName($drawingXml, 'PDS Passport Photo');
        $drawingXml = $this->removeDrawingPicturesInAnchorBox($drawingXml, 9, 12, 49, 54);

        // Remove template/old signature images in the signature box region.
        $drawingXml = $this->removeDrawingPictureByName($drawingXml, 'PDS Signature');
        $drawingXml = $this->removeDrawingPicturesInAnchorBox($drawingXml, 5, 8, 59, 61);

        $updated = false;
        if ($hasPhoto) {
            $prepared = $this->preparePassportPhoto($passportPhotoPath, $tempRoot);
            if ($prepared !== null) {
                $mediaName = $this->nextMediaFilename($mediaDir, 'png');
                if (@copy($prepared, $mediaDir.'/'.$mediaName)) {
                    $rid = $this->appendDrawingRelationship($drawingRelsXml, '../media/'.$mediaName, 'image');
                    $drawingXml = $this->appendPictureToDrawing(
                        $drawingXml,
                        $rid,
                        'PDS Passport Photo',
                        $photoAnchor['from'] ?? ['col' => 9, 'colOff' => 0, 'row' => 49, 'rowOff' => 0],
                        $photoAnchor['to'] ?? ['col' => 12, 'colOff' => 0, 'row' => 54, 'rowOff' => 0]
                    );
                    $contentTypesXml = $this->ensureContentTypeForExtension($contentTypesXml, 'png', 'image/png');
                    $updated = true;
                }
            }
        }

        if ($hasSignature) {
            $prepared = $this->prepareSignatureImage($signaturePath, $tempRoot);
            if ($prepared !== null) {
                $mediaName = $this->nextMediaFilename($mediaDir, 'png');
                if (@copy($prepared, $mediaDir.'/'.$mediaName)) {
                    $rid = $this->appendDrawingRelationship($drawingRelsXml, '../media/'.$mediaName, 'image');
                    $drawingXml = $this->appendPictureToDrawing(
                        $drawingXml,
                        $rid,
                        'PDS Signature',
                        ['col' => 5, 'colOff' => 0, 'row' => 59, 'rowOff' => 0],
                        // Enlarged to approximately 3.07" (W) x 0.85" (H)
                        // from approximately 2.83" (W) x 0.73" (H).
                        ['col' => 8, 'colOff' => 219456, 'row' => 61, 'rowOff' => 109728]
                    );
                    $contentTypesXml = $this->ensureContentTypeForExtension($contentTypesXml, 'png', 'image/png');
                    $updated = true;
                }
            }
        }

        file_put_contents($drawingPath, $drawingXml);
        if ($updated) {
            file_put_contents($drawingRelsPath, $drawingRelsXml);
            if ($contentTypesXml !== '' && is_file($contentTypesPath)) {
                file_put_contents($contentTypesPath, $contentTypesXml);
            }
        }
    }

    private function resolveStoredAssetPath(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//')) {
            return null;
        }

        $normalized = ltrim(parse_url($raw, PHP_URL_PATH) ?? $raw, '/');
        if ($normalized === '') {
            return null;
        }

        $candidates = [
            public_path($normalized),
            base_path('public/'.$normalized),
            storage_path('app/public/'.$normalized),
        ];

        if (! str_contains($normalized, '/')) {
            $candidates[] = Storage::path('public/avatars/'.$normalized);
            $candidates[] = public_path('storage/avatars/'.$normalized);
            $candidates[] = public_path('images/'.$normalized);
            $candidates[] = storage_path('app/public/avatars/'.$normalized);
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
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

    private function resolveDrawingPathFromWorksheetRels(string $relsPath, string $worksheetDir): ?string
    {
        $relsXml = file_get_contents($relsPath) ?: '';
        $rels = @simplexml_load_string($relsXml);
        if (! $rels) {
            return null;
        }
        $rels->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $matches = $rels->xpath("//pr:Relationship[contains(@Type, '/drawing')]");
        if (! $matches || ! isset($matches[0])) {
            return null;
        }
        $target = (string) ($matches[0]['Target'] ?? '');
        if ($target === '') {
            return null;
        }
        $target = str_replace('\\', '/', $target);
        if (str_starts_with($target, '/')) {
            $target = ltrim($target, '/');

            return dirname($worksheetDir).'/'.$target;
        }

        return $worksheetDir.'/'.$target;
    }

    private function nextMediaFilename(string $mediaDir, string $extension): string
    {
        $max = 0;
        foreach (glob($mediaDir.'/image*.'.$extension) ?: [] as $file) {
            if (preg_match('/image(\\d+)\\.'.preg_quote($extension, '/').'$/', basename($file), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'image'.($max + 1).'.'.$extension;
    }

    private function appendDrawingRelationship(string &$relsXml, string $target, string $type): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $relsXml)) {
            return '';
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $maxId = 0;
        foreach ($xpath->query('//pr:Relationship') as $rel) {
            if (! $rel instanceof \DOMElement) {
                continue;
            }
            $id = $rel->getAttribute('Id');
            if (preg_match('/^rId(\\d+)$/', $id, $m)) {
                $maxId = max($maxId, (int) $m[1]);
            }
        }
        $newId = 'rId'.($maxId + 1);

        $relsRoot = $dom->documentElement;
        if (! $relsRoot) {
            return '';
        }

        $relNode = $dom->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $relNode->setAttribute('Id', $newId);
        $relNode->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/'.$type);
        $relNode->setAttribute('Target', $target);
        $relsRoot->appendChild($relNode);

        $relsXml = $dom->saveXML() ?: $relsXml;

        return $newId;
    }

    private function appendPictureToDrawing(
        string $drawingXml,
        string $rid,
        string $name,
        array $fromAnchor,
        array $toAnchor
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }

        $root = $dom->documentElement;
        if (! $root) {
            return $drawingXml;
        }
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

        $maxId = 0;
        foreach ($xpath->query('//xdr:cNvPr') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }
            $id = (int) $node->getAttribute('id');
            if ($id > $maxId) {
                $maxId = $id;
            }
        }
        $picId = $maxId + 1;

        $twoCell = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing', 'xdr:twoCellAnchor');

        $from = $dom->createElementNS($twoCell->namespaceURI, 'xdr:from');
        $from->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:col', (string) ($fromAnchor['col'] ?? 0)));
        $from->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:colOff', (string) ($fromAnchor['colOff'] ?? 0)));
        $from->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:row', (string) ($fromAnchor['row'] ?? 0)));
        $from->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:rowOff', (string) ($fromAnchor['rowOff'] ?? 0)));
        $twoCell->appendChild($from);

        $toNode = $dom->createElementNS($twoCell->namespaceURI, 'xdr:to');
        $toNode->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:col', (string) ($toAnchor['col'] ?? 0)));
        $toNode->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:colOff', (string) ($toAnchor['colOff'] ?? 0)));
        $toNode->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:row', (string) ($toAnchor['row'] ?? 0)));
        $toNode->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:rowOff', (string) ($toAnchor['rowOff'] ?? 0)));
        $twoCell->appendChild($toNode);

        $pic = $dom->createElementNS($twoCell->namespaceURI, 'xdr:pic');
        $nvPicPr = $dom->createElementNS($twoCell->namespaceURI, 'xdr:nvPicPr');
        $cNvPr = $dom->createElementNS($twoCell->namespaceURI, 'xdr:cNvPr');
        $cNvPr->setAttribute('id', (string) $picId);
        $cNvPr->setAttribute('name', $name);
        $nvPicPr->appendChild($cNvPr);
        $nvPicPr->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:cNvPicPr'));
        $pic->appendChild($nvPicPr);

        $blipFill = $dom->createElementNS($twoCell->namespaceURI, 'xdr:blipFill');
        $blip = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:blip');
        $blip->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:embed', $rid);
        $blipFill->appendChild($blip);
        $stretch = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:stretch');
        $stretch->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:fillRect'));
        $blipFill->appendChild($stretch);
        $pic->appendChild($blipFill);

        $spPr = $dom->createElementNS($twoCell->namespaceURI, 'xdr:spPr');
        $xfrm = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:xfrm');
        $xfrm->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:off'))
            ->setAttribute('x', '0');
        $xfrm->lastChild?->setAttribute('y', '0');
        $xfrm->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:ext'))
            ->setAttribute('cx', '0');
        $xfrm->lastChild?->setAttribute('cy', '0');
        $spPr->appendChild($xfrm);
        $prst = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:prstGeom');
        $prst->setAttribute('prst', 'rect');
        $prst->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:avLst'));
        $spPr->appendChild($prst);
        if ($name === 'PDS Passport Photo') {
            $ln = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:ln');
            $ln->setAttribute('w', '25400');
            $solidFill = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:solidFill');
            $srgb = $dom->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:srgbClr');
            $srgb->setAttribute('val', '000000');
            $solidFill->appendChild($srgb);
            $ln->appendChild($solidFill);
            $spPr->appendChild($ln);
        }
        $pic->appendChild($spPr);

        $twoCell->appendChild($pic);
        $twoCell->appendChild($dom->createElementNS($twoCell->namespaceURI, 'xdr:clientData'));

        $root->appendChild($twoCell);

        return $dom->saveXML() ?: $drawingXml;
    }

    private function findAnchorForShapeName(string $drawingXml, string $name): ?array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $node = $xpath->query("//xdr:twoCellAnchor[xdr:sp/xdr:nvSpPr/xdr:cNvPr[@name='{$name}']]")->item(0);
        if (! $node instanceof \DOMElement) {
            return null;
        }

        $from = $xpath->query('xdr:from', $node)->item(0);
        $to = $xpath->query('xdr:to', $node)->item(0);
        if (! $from instanceof \DOMElement || ! $to instanceof \DOMElement) {
            return null;
        }

        $readAnchor = function (\DOMElement $anchor) use ($xpath): array {
            $get = function (string $tag) use ($xpath, $anchor): int {
                $node = $xpath->query('xdr:'.$tag, $anchor)->item(0);

                return $node ? (int) $node->nodeValue : 0;
            };

            return [
                'col' => $get('col'),
                'colOff' => $get('colOff'),
                'row' => $get('row'),
                'rowOff' => $get('rowOff'),
            ];
        };

        return [
            'from' => $readAnchor($from),
            'to' => $readAnchor($to),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function loadSharedStrings(string $sharedStringsPath): array
    {
        $sharedStrings = [];
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$dom->load($sharedStringsPath)) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query("//*[local-name()='si']") as $si) {
            $sharedStrings[] = trim((string) $si->textContent);
        }

        return $sharedStrings;
    }

    private function isSignatureInstructionText(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        $phrases = [
            'wet signature',
            'e-signature',
            'esignature',
            're-signature',
            'digital certificate',
            'notary public',
            'signa',
        ];

        foreach ($phrases as $phrase) {
            if (str_contains($t, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function isPhotoPlaceholderText(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        return str_contains($t, 'passport-sized')
            || str_contains($t, 'unfiltered')
            || str_contains($t, 'digital picture')
            || str_contains($t, 'taken within')
            || str_contains($t, '4.5 cm')
            || str_contains($t, '3.5 cm');
    }

    private function isInstructionText(string $text): bool
    {
        return $this->isSignatureInstructionText($text) || $this->isPhotoPlaceholderText($text);
    }

    /**
     * Blank any worksheet cells that contain signature instruction text.
     *
     * @param  array<int, string>  $sharedStrings
     */
    private function removeInstructionTextFromWorksheet(string $worksheetXml, array $sharedStrings): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$dom->loadXML($worksheetXml)) {
            return $worksheetXml;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query("//*[local-name()='sheetData']//*[local-name()='c']") as $cell) {
            if (! $cell instanceof \DOMElement) {
                continue;
            }

            $type = $cell->getAttribute('t');
            $value = '';

            if ($type === 's') {
                $vNode = $xpath->query("*[local-name()='v']", $cell)->item(0);
                $stringIndex = $vNode ? (int) trim((string) $vNode->textContent) : -1;
                $value = ($stringIndex >= 0 && isset($sharedStrings[$stringIndex])) ? $sharedStrings[$stringIndex] : '';
            } elseif ($type === 'inlineStr') {
                $value = trim((string) $xpath->query("string(*[local-name()='is'])", $cell));
            } else {
                $vNode = $xpath->query("*[local-name()='v']", $cell)->item(0);
                $value = $vNode ? trim((string) $vNode->textContent) : '';
            }

            if (! $this->isSignatureInstructionText($value)) {
                continue;
            }

            foreach ($xpath->query("*[local-name()='v' or local-name()='is']", $cell) as $child) {
                if ($child instanceof \DOMNode) {
                    $cell->removeChild($child);
                }
            }
            $cell->removeAttribute('t');
        }

        return $dom->saveXML() ?: $worksheetXml;
    }

    private function blankSharedStringsByMatcher(string $sharedStringsPath): void
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (! @$dom->load($sharedStringsPath)) {
            return;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query("//*[local-name()='si']") as $si) {
            if (! $si instanceof \DOMElement) {
                continue;
            }

            $text = trim((string) $si->textContent);
            if (! $this->isSignatureInstructionText($text)) {
                continue;
            }

            foreach ($xpath->query(".//*[local-name()='t']", $si) as $tNode) {
                if ($tNode instanceof \DOMNode) {
                    $tNode->textContent = '';
                }
            }
        }

        file_put_contents($sharedStringsPath, $dom->saveXML() ?: '');
    }

    private function removeSignatureInstructionTextFromDrawing(string $drawingXml): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $txBodies = $xpath->query('//xdr:txBody');
        if ($txBodies === false) {
            return $dom->saveXML() ?: $drawingXml;
        }
        foreach ($txBodies as $txBody) {
            if (! $txBody instanceof \DOMElement) {
                continue;
            }
            $text = trim((string) $txBody->textContent);
            if (! $this->isInstructionText($text)) {
                continue;
            }
            foreach ($xpath->query('.//a:t', $txBody) as $tNode) {
                if ($tNode instanceof \DOMNode) {
                    $tNode->textContent = '';
                }
            }
        }

        return $dom->saveXML() ?: $drawingXml;
    }

    private function removeDrawingTextBoxByName(string $drawingXml, string $name): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $nodes = $xpath->query("//xdr:twoCellAnchor[xdr:sp/xdr:nvSpPr/xdr:cNvPr[@name='{$name}']]");
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof \DOMNode) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        return $dom->saveXML() ?: $drawingXml;
    }

    private function removeDrawingPictureByName(string $drawingXml, string $name): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $nodes = $xpath->query("//xdr:twoCellAnchor[xdr:pic/xdr:nvPicPr/xdr:cNvPr[@name='{$name}']]");
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof \DOMNode) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        return $dom->saveXML() ?: $drawingXml;
    }

    private function removeAllPicturesFromDrawing(string $drawingXml): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $nodes = $xpath->query('//xdr:twoCellAnchor[xdr:pic]');
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof \DOMNode) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        return $dom->saveXML() ?: $drawingXml;
    }

    /**
     * Remove any picture anchors that overlap a given cell box.
     */
    private function removeDrawingPicturesInAnchorBox(
        string $drawingXml,
        int $fromCol,
        int $toCol,
        int $fromRow,
        int $toRow
    ): string {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $drawingXml)) {
            return $drawingXml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');

        $anchors = $xpath->query('//xdr:twoCellAnchor[xdr:pic]');
        if ($anchors === false) {
            return $dom->saveXML() ?: $drawingXml;
        }

        $toInt = fn (?string $v): int => is_string($v) ? (int) trim($v) : 0;

        foreach (iterator_to_array($anchors) as $anchor) {
            if (! $anchor instanceof \DOMElement) {
                continue;
            }

            $from = $xpath->query('xdr:from', $anchor)->item(0);
            $to = $xpath->query('xdr:to', $anchor)->item(0);
            if (! $from instanceof \DOMElement || ! $to instanceof \DOMElement) {
                continue;
            }

            $aFromCol = $toInt($xpath->query('xdr:col', $from)->item(0)?->nodeValue);
            $aFromRow = $toInt($xpath->query('xdr:row', $from)->item(0)?->nodeValue);
            $aToCol = $toInt($xpath->query('xdr:col', $to)->item(0)?->nodeValue);
            $aToRow = $toInt($xpath->query('xdr:row', $to)->item(0)?->nodeValue);

            $overlaps = ! (
                $aToCol < $fromCol
                || $aFromCol > $toCol
                || $aToRow < $fromRow
                || $aFromRow > $toRow
            );

            if ($overlaps) {
                $anchor->parentNode?->removeChild($anchor);
            }
        }

        return $dom->saveXML() ?: $drawingXml;
    }

    private function ensureContentTypeForExtension(string $xml, string $extension, string $contentType): string
    {
        if ($xml === '') {
            return $xml;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (! $this->safelyLoadXml($dom, $xml)) {
            return $xml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
        $existing = $xpath->query("//ct:Default[@Extension='{$extension}']");
        if ($existing !== false && $existing->length > 0) {
            return $xml;
        }

        $root = $dom->documentElement;
        if ($root) {
            $node = $dom->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default');
            $node->setAttribute('Extension', $extension);
            $node->setAttribute('ContentType', $contentType);
            $root->appendChild($node);
        }

        return $dom->saveXML() ?: $xml;
    }

    private function preparePassportPhoto(string $sourcePath, string $tempRoot): ?string
    {
        if (! $this->canProcessImages()) {
            return $sourcePath;
        }

        $targetWidth = 413;
        $targetHeight = 531;
        $image = $this->loadImageResource($sourcePath);
        if (! $image) {
            return null;
        }

        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($image);

            return null;
        }

        $targetRatio = $targetWidth / $targetHeight;
        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            $newWidth = (int) floor($srcHeight * $targetRatio);
            $newHeight = $srcHeight;
            $srcX = (int) floor(($srcWidth - $newWidth) / 2);
            $srcY = 0;
        } else {
            $newWidth = $srcWidth;
            $newHeight = (int) floor($srcWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int) floor(($srcHeight - $newHeight) / 2);
        }

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($dst === false) {
            imagedestroy($image);

            return null;
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled(
            $dst,
            $image,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $newWidth,
            $newHeight
        );

        $outputPath = $tempRoot.'/passport_photo_'.uniqid().'.png';
        imagepng($dst, $outputPath);
        imagedestroy($dst);
        imagedestroy($image);

        return is_file($outputPath) ? $outputPath : null;
    }

    private function prepareSignatureImage(string $sourcePath, string $tempRoot): ?string
    {
        if (! $this->canProcessImages()) {
            return $sourcePath;
        }

        $maxWidth = 260;
        $maxHeight = 90;
        $image = $this->loadImageResource($sourcePath);
        if (! $image) {
            return null;
        }

        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($image);

            return null;
        }

        $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight, 1);
        $targetWidth = (int) max(1, floor($srcWidth * $scale));
        $targetHeight = (int) max(1, floor($srcHeight * $scale));

        $dst = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($dst === false) {
            imagedestroy($image);

            return null;
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled(
            $dst,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $srcWidth,
            $srcHeight
        );

        $outputPath = $tempRoot.'/signature_'.uniqid().'.png';
        imagepng($dst, $outputPath);
        imagedestroy($dst);
        imagedestroy($image);

        return is_file($outputPath) ? $outputPath : null;
    }

    private function canProcessImages(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagepng');
    }

    private function loadImageResource(string $path): mixed
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            'gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : null,
            default => null,
        };
    }

    private function safelyLoadXml(\DOMDocument $dom, string $xml): bool
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $loaded = $dom->loadXML($xml);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded;
    }

    /**
     * Apply cell map to C4 worksheet XML (values only; preserves styles).
     *
     * @param  array<string, string>  $cellMap
     */
    public function applyCellMapToWorksheetXml(string $worksheetXml, array $cellMap): string
    {
        if ($cellMap === []) {
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
                            // Skip problematic namespaced attributes.
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
     * @param  array<string, bool>  $states
     */
    private function applyBinaryState(array &$states, string $yesControl, string $noControl, bool $isYes): void
    {
        $states[$yesControl] = $isYes;
        $states[$noControl] = ! $isYes;
    }

    /**
     * @param  array<int, string>  $yesKeys
     * @param  array<int, string>  $noKeys
     * @param  array<int, mixed>  $detailValues
     */
    private function resolveYesNo(mixed $row, array $yesKeys, array $noKeys, array $detailValues = []): bool
    {
        $yesValue = $this->firstExistingValue($row, $yesKeys);
        if ($yesValue['found']) {
            $normalized = $this->normalizeBoolean($yesValue['value']);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        $noValue = $this->firstExistingValue($row, $noKeys);
        if ($noValue['found']) {
            $normalized = $this->normalizeBoolean($noValue['value']);
            if ($normalized !== null) {
                return ! $normalized;
            }
        }

        foreach ($detailValues as $detailValue) {
            if ($this->hasValue($detailValue)) {
                return true;
            }
        }

        return false;
    }

    private function mapIfPresent(array &$map, string $cellRef, mixed $value, callable $pdsValue): void
    {
        if (! $this->hasValue($value)) {
            return;
        }

        $resolved = (string) $pdsValue($value);
        if (trim($resolved) === '') {
            return;
        }

        $map[$cellRef] = $resolved;
    }

    private function formatDateOrRaw(mixed $value, callable $formatDate): mixed
    {
        if (! $this->hasValue($value)) {
            return null;
        }

        return $formatDate($value);
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function joinValues(array $values): ?string
    {
        $clean = [];
        foreach ($values as $value) {
            if (! $this->hasValue($value)) {
                continue;
            }
            $clean[] = trim((string) $value);
        }

        if ($clean === []) {
            return null;
        }

        return implode(' / ', $clean);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array{found: bool, value: mixed}
     */
    private function firstExistingValue(mixed $row, array $keys): array
    {
        if (! is_array($row) && ! is_object($row)) {
            return ['found' => false, 'value' => null];
        }

        foreach ($keys as $key) {
            if (is_array($row) && array_key_exists($key, $row)) {
                return ['found' => true, 'value' => $row[$key]];
            }

            if (is_object($row) && property_exists($row, $key)) {
                return ['found' => true, 'value' => $row->{$key}];
            }
        }

        return ['found' => false, 'value' => null];
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function firstValue(mixed $row, array $keys): mixed
    {
        if (! is_array($row) && ! is_object($row)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
            if ($this->hasValue($value)) {
                return $value;
            }
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            if ((float) $value === 1.0) {
                return true;
            }
            if ((float) $value === 0.0) {
                return false;
            }
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'checked', 'on', 'x'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'unchecked', 'off', 'na', 'n/a', 'none'], true)) {
            return false;
        }

        return null;
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '' || $normalized === 'n/a' || $normalized === 'na') {
            return false;
        }

        return true;
    }

    private function sanitizeForXml(string $s): string
    {
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
}
