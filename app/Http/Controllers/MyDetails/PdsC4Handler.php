<?php

namespace App\Http\Controllers\MyDetails;

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
