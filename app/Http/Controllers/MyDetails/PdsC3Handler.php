<?php

namespace App\Http\Controllers\MyDetails;

/**
 * Handles C3 sheet mapping and XML writing.
 * This keeps C3 export logic isolated from PdsExportHandler for easier debugging and maintenance.
 */
class PdsC3Handler
{
    private const VOLUNTARY_WORK_START_ROW = 6;

    private const VOLUNTARY_WORK_MAX_ROWS = 12;

    private const LEARNING_AND_DEVELOPMENT_START_ROW = 18;

    private const LEARNING_AND_DEVELOPMENT_MAX_ROWS = 21;

    private const OTHER_INFORMATION_START_ROW = 42;

    private const OTHER_INFORMATION_MAX_ROWS = 7;

    /**
     * Build cell map for the C3 sheet.
     *
     * @param  iterable<int, mixed>  $voluntaryWork
     * @param  iterable<int, mixed>  $learningAndDevelopment
     * @param  iterable<int, mixed>  $expertise
     * @param  iterable<int, mixed>  $awards
     * @param  iterable<int, mixed>  $affiliation
     * @param  callable(mixed): string  $formatDate
     * @param  callable(mixed): string  $pdsValue
     * @return array<string, string>
     */
    public function buildCellMap(
        iterable $voluntaryWork,
        iterable $learningAndDevelopment,
        iterable $expertise,
        iterable $awards,
        iterable $affiliation,
        callable $formatDate,
        callable $pdsValue
    ): array {
        $map = [];

        $voluntaryRows = array_slice($this->toRows($voluntaryWork), 0, self::VOLUNTARY_WORK_MAX_ROWS);
        foreach ($voluntaryRows as $index => $row) {
            $r = self::VOLUNTARY_WORK_START_ROW + $index;
            $this->mapRowWithNaFallback($map, [
                "A{$r}" => $this->pick($row, [
                    'name_address_org',
                    'organization',
                    'org_name',
                    'name_of_organization',
                    'affiliation',
                ]),
                "E{$r}" => $this->formatDateValue($this->pick($row, [
                    'inclusive_date_from',
                    'date_from',
                    'start_date',
                ]), $formatDate),
                "F{$r}" => $this->formatDateValue($this->pick($row, [
                    'inclusive_date_to',
                    'date_to',
                    'end_date',
                ]), $formatDate),
                "G{$r}" => $this->formatHours($this->pick($row, [
                    'number_hours',
                    'hours',
                    'no_of_hours',
                ])),
                "H{$r}" => $this->pick($row, [
                    'position_nature_of_work',
                    'position',
                    'nature_of_work',
                    'position_title',
                ]),
            ], $pdsValue);
        }

        $learningRows = array_slice($this->toRows($learningAndDevelopment), 0, self::LEARNING_AND_DEVELOPMENT_MAX_ROWS);
        foreach ($learningRows as $index => $row) {
            $r = self::LEARNING_AND_DEVELOPMENT_START_ROW + $index;
            $this->mapRowWithNaFallback($map, [
                "A{$r}" => $this->pick($row, [
                    'training_title',
                    'title',
                    'title_of_learning_and_development_interventions',
                    'title_of_learning_development_interventions',
                    'title_of_training',
                ]),
                "E{$r}" => $this->formatDateValue($this->pick($row, [
                    'inclusive_date_from',
                    'date_from',
                    'start_date',
                ]), $formatDate),
                "F{$r}" => $this->formatDateValue($this->pick($row, [
                    'inclusive_date_to',
                    'date_to',
                    'end_date',
                ]), $formatDate),
                "G{$r}" => $this->formatHours($this->pick($row, [
                    'number_hours',
                    'hours',
                    'no_of_hours',
                ])),
                "H{$r}" => $this->pick($row, [
                    'type_of_ld',
                    'type_of_learning_and_development',
                    'training_type',
                    'type',
                ]),
                "I{$r}" => $this->pick($row, [
                    'conducted_sponsored_by',
                    'conducted_by',
                    'sponsored_by',
                    'training_venue',
                    'venue',
                ]),
            ], $pdsValue);
        }

        $skillsRows = array_slice($this->toRows($expertise), 0, self::OTHER_INFORMATION_MAX_ROWS);
        $awardsRows = array_slice($this->toRows($awards), 0, self::OTHER_INFORMATION_MAX_ROWS);
        $affiliationRows = array_slice($this->toRows($affiliation), 0, self::OTHER_INFORMATION_MAX_ROWS);
        for ($index = 0; $index < self::OTHER_INFORMATION_MAX_ROWS; $index++) {
            $r = self::OTHER_INFORMATION_START_ROW + $index;
            $skill = $skillsRows[$index] ?? null;
            $award = $awardsRows[$index] ?? null;
            $member = $affiliationRows[$index] ?? null;

            $this->mapRowWithNaFallback($map, [
                "A{$r}" => $this->pick($skill, ['expertise', 'skill', 'hobby', 'special_skill']),
                "C{$r}" => $this->pick($award, ['award_title', 'award', 'title', 'distinction']),
                "I{$r}" => $this->pick($member, ['affiliation', 'organization', 'org_name', 'membership']),
            ], $pdsValue);
        }

        return $map;
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @return array<int, mixed>
     */
    private function toRows(iterable $rows): array
    {
        if ($rows instanceof \Illuminate\Support\Collection) {
            return $rows->values()->all();
        }
        if (is_array($rows)) {
            return array_values($rows);
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row;
        }

        return $result;
    }

    private function pick(mixed $row, array $keys): mixed
    {
        if (! is_array($row) && ! is_object($row)) {
            return null;
        }
        foreach ($keys as $key) {
            $value = is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function formatDateValue(mixed $value, callable $formatDate): mixed
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $formatDate($value);
    }

    /**
     * @param  array<string, string>  $map
     * @param  array<string, mixed>  $rowCells
     * @param  callable(mixed): string  $pdsValue
     */
    private function mapRowWithNaFallback(array &$map, array $rowCells, callable $pdsValue): void
    {
        $hasAnyValue = false;
        foreach ($rowCells as $value) {
            if ($this->hasValue($value)) {
                $hasAnyValue = true;
                break;
            }
        }
        if (! $hasAnyValue) {
            return;
        }

        foreach ($rowCells as $cellRef => $value) {
            if (! $this->hasValue($value)) {
                $map[$cellRef] = 'N/A';

                continue;
            }

            $resolved = (string) $pdsValue($value);
            $map[$cellRef] = trim($resolved) !== '' ? $resolved : 'N/A';
        }
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function formatHours(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $hours = trim((string) $value);
        if ($hours === '') {
            return null;
        }

        $normalized = strtoupper($hours);
        if (preg_match('/\s*HRS?$/', $normalized) === 1) {
            return preg_replace('/\s*HRS?$/', ' HRS', $normalized);
        }

        return $hours.' HRS';
    }

    /**
     * Apply cell map to C3 worksheet XML (values only; preserves existing cell styles).
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
                            // Skip attributes that cause namespace errors.
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
}
